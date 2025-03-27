<?php

namespace App\MessageHandler;

use App\Message\ProcessProductMessage;
use App\Repository\WooCommerceProductRepository;
use App\Repository\UserRepository;
use App\Service\AIService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Psr\Log\LoggerInterface;

#[AsMessageHandler]
class ProcessProductMessageHandler
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private WooCommerceProductRepository $productRepository,
        private UserRepository $userRepository,
        private AIService $aiService,
        private LoggerInterface $logger
    ) {}

    public function __invoke(ProcessProductMessage $message)
    {
        $this->logger->info('Starting product processing', [
            'product_id' => $message->getProductId(),
            'user_id' => $message->getUserId()
        ]);

        try {
            // Get fresh entities from database
            $product = $this->productRepository->find($message->getProductId());
            $user = $this->userRepository->find($message->getUserId());

            if (!$product || !$user) {
                throw new \Exception('Product or user not found');
            }

            // Create temporary Product entity for AI service
            $tempProduct = new \App\Entity\Product();
            $tempProduct->setName($product->getName());
            $tempProduct->setDescription($product->getDescription());
            $tempProduct->setShortDescription($product->getShortDescription());
            if ($product->getImageUrl()) {
                $tempProduct->setImageUrl($product->getImageUrl());
            }
            $tempProduct->setOwner($user);

            // Start transaction
            $this->entityManager->beginTransaction();

            try {
                // Generate AI content
                $generatedContent = $this->aiService->generateContent($tempProduct, $message->getOptions());

                // Apply the generated content
                if (!empty($generatedContent['description'])) {
                    $product->setDescription($generatedContent['description']);
                }
                if (!empty($generatedContent['shortDescription'])) {
                    $product->setShortDescription($generatedContent['shortDescription']);
                }
                if (!empty($generatedContent['metaDescription'])) {
                    $product->setMetaDescription($generatedContent['metaDescription']);
                }
                if (!empty($generatedContent['imageAltText'])) {
                    $product->setImageAltText($generatedContent['imageAltText']);
                }

                // Update product status
                $product->setStatus('ai_processed');

                // Deduct credits
                $creditCost = isset($message->getOptions()['isPremiumProcessing']) && 
                             $message->getOptions()['isPremiumProcessing'] ? 1.0 : 0.5;
                $user->setCredits($user->getCredits() - $creditCost);

                // Save changes
                $this->entityManager->flush();
                $this->entityManager->commit();

                $this->logger->info('Product processed successfully', [
                    'product_id' => $message->getProductId(),
                    'user_id' => $message->getUserId()
                ]);
            } catch (\Exception $e) {
                $this->entityManager->rollback();
                throw $e;
            }
        } catch (\Exception $e) {
            $this->logger->error('Error processing product', [
                'product_id' => $message->getProductId(),
                'user_id' => $message->getUserId(),
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
} 