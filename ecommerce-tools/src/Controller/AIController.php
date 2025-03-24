<?php

namespace App\Controller;

use App\Entity\Product;
use App\Entity\User;
use App\Service\AIService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/ai')]
#[IsGranted('ROLE_USER')]
class AIController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private AIService $aiService;

    public function __construct(
        EntityManagerInterface $entityManager,
        AIService $aiService
    ) {
        $this->entityManager = $entityManager;
        $this->aiService = $aiService;
    }

    /**
     * AI dashboard/landing page 
     */
    #[Route('/', name: 'app_ai_dashboard')]
    public function index(): Response
    {
        $user = $this->getTypedUser();
        
        // Check if user has enough credits
        if ($user->getCredits() <= 0) {
            return $this->render('ai/insufficient_credits.html.twig');
        }
        
        // Get recent products
        $products = $this->entityManager->getRepository(Product::class)
            ->findBy(['owner' => $user], ['updatedAt' => 'DESC'], 10);
        
        return $this->render('ai/dashboard.html.twig', [
            'products' => $products,
            'credits' => $user->getCredits()
        ]);
    }
    
    private function getTypedUser(): User
    {
        /** @var User $user */
        $user = $this->getUser();
        return $user;
    }

    #[Route('/generate/{id}', name: 'app_ai_generate')]
    public function generateContent(Product $product): Response
    {
        $user = $this->getTypedUser();

        // Check if user has enough credits
        if ($user->getCredits() <= 0) {
            return $this->render('ai/insufficient_credits.html.twig', [
                'product' => $product
            ]);
        }

        return $this->render('ai/generate.html.twig', [
            'product' => $product
        ]);
    }

    #[Route('/process/{id}', name: 'app_ai_process_content', methods: ['POST'])]
    public function processContent(Request $request, Product $product): JsonResponse
    {
        $user = $this->getTypedUser();

        // Check if user has enough credits
        if ($user->getCredits() <= 0) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Insufficient credits'
            ], Response::HTTP_PAYMENT_REQUIRED);
        }
        
        // Get request data
        $requestData = json_decode($request->getContent(), true) ?? [];
        $usePremiumFeatures = $requestData['usePremiumFeatures'] ?? false;
        $processingMode = $requestData['processingMode'] ?? 'premium'; // Default to premium processing
        
        // Check if premium features are requested but user doesn't have premium role
        if ($usePremiumFeatures && !$this->isGranted('ROLE_PREMIUM')) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Premium subscription required for this feature'
            ], Response::HTTP_FORBIDDEN);
        }

        try {
            // Set options based on processing mode and premium features
            $aiOptions = [];
            
            // Only pass premium feature options if the user has premium role and enabled them
            if ($this->isGranted('ROLE_PREMIUM') && $usePremiumFeatures) {
                $aiOptions = [
                    'targetKeywords' => $requestData['targetKeywords'] ?? '',
                    'competitorUrls' => $requestData['competitorUrls'] ?? '',
                    'tone' => $requestData['tone'] ?? 'professional',
                    'platform' => $requestData['platform'] ?? 'Instagram'
                ];
            }
            
            // Set isPremiumProcessing flag based on selected processing mode
            // This determines which AI model is used (GPT-4 Turbo vs GPT-3.5 Turbo)
            $aiOptions['isPremiumProcessing'] = ($processingMode === 'premium');
            
            // Generate content using OpenAI with the appropriate options
            $content = $this->aiService->generateContent($product, $aiOptions);

            // Update product with generated content
            $product->setAiGeneratedContent($content);
            
            // Determine credit cost based on processing mode
            $creditCost = ($processingMode === 'premium') ? 1.0 : 0.5; // 1 credit for premium, 0.5 for standard
            
            // Deduct credits
            $user->setCredits($user->getCredits() - $creditCost);
            
            $this->entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'content' => $content
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Failed to generate content: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/apply/{id}', name: 'app_ai_apply_content', methods: ['POST'])]
    public function applyContent(Request $request, Product $product): JsonResponse
    {
        try {
            $content = json_decode($request->getContent(), true);
            $currentAiContent = $product->getAiGeneratedContent() ?? [];

            // Apply the AI-generated content to the product
            $product->setDescription($content['description'] ?? $product->getDescription());
            $product->setShortDescription($content['shortDescription'] ?? $product->getShortDescription());
            $product->setMetaDescription($content['metaDescription'] ?? $product->getMetaDescription());
            $product->setImageAltText($content['imageAltText'] ?? $product->getImageAltText());
            
            // Set the new targetKeyphrase and metaTitle fields
            $product->setTargetKeyphrase($content['targetKeyphrase'] ?? $product->getTargetKeyphrase());
            $product->setMetaTitle($content['metaTitle'] ?? $product->getMetaTitle());
            
            // Update the stored AI-generated content with all fields from the request
            $updatedAiContent = array_merge($currentAiContent, $content);
            $product->setAiGeneratedContent($updatedAiContent);

            // Set status to indicate AI processing is complete
            if ($product->getStatus() === 'draft') {
                $product->setStatus('ai_processed');
            }

            $this->entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'message' => 'Content applied successfully'
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Failed to apply content: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}