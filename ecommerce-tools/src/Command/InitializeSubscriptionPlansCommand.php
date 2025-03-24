<?php

namespace App\Command;

use App\Entity\SubscriptionPlan;
use App\Repository\SubscriptionPlanRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:initialize-subscription-plans',
    description: 'Initialize subscription plans in the database',
)]
class InitializeSubscriptionPlansCommand extends Command
{
    private EntityManagerInterface $entityManager;
    private SubscriptionPlanRepository $subscriptionPlanRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        SubscriptionPlanRepository $subscriptionPlanRepository
    ) {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->subscriptionPlanRepository = $subscriptionPlanRepository;
    }

    protected function configure(): void
    {
        $this->setHelp('This command creates the initial subscription plans in the database.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Define default plans
        $plans = [
            [
                'name' => 'Basic Plan',
                'identifier' => 'basic',
                'price' => 19.99,
                'credits' => 50,
                'description' => 'Entry-level plan for small businesses',
                'features' => [
                    'product_descriptions' => true,
                    'meta_descriptions' => true,
                    'image_alt_text' => true,
                    'seo_keywords' => false,
                    'premium_ai' => false
                ],
                'feature_descriptions' => [
                    'Access to AI content generation',
                    'Product descriptions',
                    'Meta descriptions'
                ],
                'is_featured' => false,
                'display_order' => 1
            ],
            [
                'name' => 'Pro Plan',
                'identifier' => 'pro',
                'price' => 39.99,
                'discount' => 15,
                'credits' => 100,
                'description' => 'Professional plan with additional features',
                'features' => [
                    'product_descriptions' => true,
                    'meta_descriptions' => true,
                    'image_alt_text' => true,
                    'seo_keywords' => true,
                    'premium_ai' => false
                ],
                'feature_descriptions' => [
                    'Access to AI content generation',
                    'Product descriptions',
                    'Meta descriptions',
                    'SEO keywords'
                ],
                'is_featured' => true,
                'display_order' => 2
            ],
            [
                'name' => 'Business Plan',
                'identifier' => 'business',
                'price' => 79.99,
                'credits' => 250,
                'description' => 'Enterprise-grade plan with premium features',
                'features' => [
                    'product_descriptions' => true,
                    'meta_descriptions' => true,
                    'image_alt_text' => true,
                    'seo_keywords' => true,
                    'premium_ai' => true
                ],
                'feature_descriptions' => [
                    'Access to AI content generation',
                    'Product descriptions',
                    'Meta descriptions',
                    'SEO keywords',
                    'Premium AI model (GPT-4)'
                ],
                'is_featured' => false,
                'display_order' => 3
            ]
        ];

        $count = 0;
        foreach ($plans as $planData) {
            // Check if plan already exists
            $existingPlan = $this->subscriptionPlanRepository->findByIdentifier($planData['identifier']);
            
            if (!$existingPlan) {
                $plan = new SubscriptionPlan();
                $plan->setName($planData['name'])
                    ->setIdentifier($planData['identifier'])
                    ->setPrice($planData['price'])
                    ->setCredits($planData['credits'])
                    ->setDescription($planData['description'])
                    ->setFeatures($planData['features'])
                    ->setFeatureDescriptions($planData['feature_descriptions'])
                    ->setIsFeatured($planData['is_featured'])
                    ->setDisplayOrder($planData['display_order']);
                
                if (isset($planData['discount'])) {
                    $plan->setDiscount($planData['discount']);
                }
                
                $this->entityManager->persist($plan);
                $count++;
            }
        }
        
        if ($count > 0) {
            $this->entityManager->flush();
            $io->success("Created $count subscription plans.");
        } else {
            $io->info('All subscription plans already exist.');
        }

        return Command::SUCCESS;
    }
}