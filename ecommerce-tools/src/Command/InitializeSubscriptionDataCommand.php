<?php

namespace App\Command;

use App\Entity\PackageAddOn;
use App\Entity\SubscriptionPlan;
use App\Repository\PackageAddOnRepository;
use App\Repository\SubscriptionPlanRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:initialize-subscription-data',
    description: 'Initialize the subscription plans and add-on packages',
)]
class InitializeSubscriptionDataCommand extends Command
{
    private $entityManager;
    private $subscriptionPlanRepository;
    private $packageAddOnRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        SubscriptionPlanRepository $subscriptionPlanRepository,
        PackageAddOnRepository $packageAddOnRepository
    ) {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->subscriptionPlanRepository = $subscriptionPlanRepository;
        $this->packageAddOnRepository = $packageAddOnRepository;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Create subscription plans
        $subscriptionPlans = [
            [
                'name' => 'Micro',
                'identifier' => 'micro',
                'price' => 9.00,
                'credits' => 10,
                'term' => 'monthly',
                'features' => [
                    'product_descriptions' => true,
                    'meta_descriptions' => true,
                    'image_alt_text' => true,
                    'seo_keywords' => false,
                    'premium_ai' => false,
                ],
                'display_order' => 1,
                'is_active' => true,
                'is_featured' => false,
            ],
            [
                'name' => 'Starter',
                'identifier' => 'starter',
                'price' => 29.00,
                'credits' => 50,
                'term' => 'monthly',
                'features' => [
                    'product_descriptions' => true,
                    'meta_descriptions' => true,
                    'image_alt_text' => true,
                    'seo_keywords' => false,
                    'premium_ai' => false,
                ],
                'display_order' => 2,
                'is_active' => true,
                'is_featured' => false,
            ],
            [
                'name' => 'Growth',
                'identifier' => 'growth',
                'price' => 79.00,
                'credits' => 250,
                'term' => 'monthly',
                'features' => [
                    'product_descriptions' => true,
                    'meta_descriptions' => true,
                    'image_alt_text' => true,
                    'seo_keywords' => true,
                    'premium_ai' => false,
                ],
                'display_order' => 3,
                'is_active' => true,
                'is_featured' => true,
            ],
            [
                'name' => 'Pro',
                'identifier' => 'pro',
                'price' => 149.00,
                'credits' => 750,
                'term' => 'monthly',
                'features' => [
                    'product_descriptions' => true,
                    'meta_descriptions' => true,
                    'image_alt_text' => true,
                    'seo_keywords' => true,
                    'premium_ai' => true,
                ],
                'display_order' => 4,
                'is_active' => true,
                'is_featured' => false,
            ],
            [
                'name' => 'Enterprise',
                'identifier' => 'enterprise',
                'price' => 249.00,
                'credits' => 2000,
                'term' => 'monthly',
                'features' => [
                    'product_descriptions' => true,
                    'meta_descriptions' => true,
                    'image_alt_text' => true,
                    'seo_keywords' => true,
                    'premium_ai' => true,
                ],
                'display_order' => 5,
                'is_active' => true,
                'is_featured' => false,
            ],
            [
                'name' => 'Ultimate',
                'identifier' => 'ultimate',
                'price' => 399.00,
                'credits' => 5000,
                'term' => 'monthly',
                'features' => [
                    'product_descriptions' => true,
                    'meta_descriptions' => true,
                    'image_alt_text' => true,
                    'seo_keywords' => true,
                    'premium_ai' => true,
                ],
                'display_order' => 6,
                'is_active' => true,
                'is_featured' => false,
            ],
            [
                'name' => 'Mega',
                'identifier' => 'mega',
                'price' => 699.00,
                'credits' => 10000,
                'term' => 'monthly',
                'features' => [
                    'product_descriptions' => true,
                    'meta_descriptions' => true,
                    'image_alt_text' => true,
                    'seo_keywords' => true,
                    'premium_ai' => true,
                ],
                'display_order' => 7,
                'is_active' => true,
                'is_featured' => false,
            ],
        ];

        $createdPlans = 0;
        $updatedPlans = 0;

        foreach ($subscriptionPlans as $planData) {
            $existingPlan = $this->subscriptionPlanRepository->findOneBy(['identifier' => $planData['identifier']]);

            if (!$existingPlan) {
                $plan = new SubscriptionPlan();
                $plan->setName($planData['name']);
                $plan->setIdentifier($planData['identifier']);
                $plan->setPrice($planData['price']);
                $plan->setCredits($planData['credits']);
                $plan->setTerm($planData['term']);
                $plan->setFeatures($planData['features']);
                $plan->setDisplayOrder($planData['display_order']);
                $plan->setIsActive($planData['is_active']);
                $plan->setIsFeatured($planData['is_featured']);
                $plan->setCreatedAt(new \DateTimeImmutable());
                $plan->setUpdatedAt(new \DateTimeImmutable());

                $this->entityManager->persist($plan);
                $createdPlans++;
            } else {
                // Update existing plan
                $existingPlan->setName($planData['name']);
                $existingPlan->setPrice($planData['price']);
                $existingPlan->setCredits($planData['credits']);
                $existingPlan->setTerm($planData['term']);
                $existingPlan->setFeatures($planData['features']);
                $existingPlan->setDisplayOrder($planData['display_order']);
                $existingPlan->setIsActive($planData['is_active']);
                $existingPlan->setIsFeatured($planData['is_featured']);
                $existingPlan->setUpdatedAt(new \DateTimeImmutable());

                $updatedPlans++;
            }
        }

        // Create package add-ons
        $packageAddOns = [
            [
                'name' => 'Micro',
                'identifier' => 'micro_addon',
                'price_standard' => 19.00,
                'price_premium' => 29.00,
                'credits' => 10,
                'display_order' => 1,
                'is_active' => true,
                'is_featured' => false,
            ],
            [
                'name' => 'Starter',
                'identifier' => 'starter_addon',
                'price_standard' => 99.00,
                'price_premium' => 149.00,
                'credits' => 100,
                'display_order' => 2,
                'is_active' => true,
                'is_featured' => true,
            ],
            [
                'name' => 'Growth',
                'identifier' => 'growth_addon',
                'price_standard' => 399.00,
                'price_premium' => 599.00,
                'credits' => 500,
                'display_order' => 3,
                'is_active' => true,
                'is_featured' => false,
            ],
            [
                'name' => 'Pro',
                'identifier' => 'pro_addon',
                'price_standard' => 999.00,
                'price_premium' => 1499.00,
                'credits' => 1500,
                'display_order' => 4,
                'is_active' => true,
                'is_featured' => false,
            ],
            [
                'name' => 'Enterprise',
                'identifier' => 'enterprise_addon',
                'price_standard' => 1799.00,
                'price_premium' => 2699.00,
                'credits' => 3000,
                'display_order' => 5,
                'is_active' => true,
                'is_featured' => false,
            ],
            [
                'name' => 'Ultimate',
                'identifier' => 'ultimate_addon',
                'price_standard' => 2999.00,
                'price_premium' => 4499.00,
                'credits' => 5000,
                'display_order' => 6,
                'is_active' => true,
                'is_featured' => false,
            ],
            [
                'name' => 'Mega',
                'identifier' => 'mega_addon',
                'price_standard' => 4999.00,
                'price_premium' => 7499.00,
                'credits' => 10000,
                'display_order' => 7,
                'is_active' => true,
                'is_featured' => false,
            ],
        ];

        $createdPackages = 0;
        $updatedPackages = 0;

        foreach ($packageAddOns as $packageData) {
            $existingPackage = $this->packageAddOnRepository->findOneBy(['identifier' => $packageData['identifier']]);

            if (!$existingPackage) {
                $package = new PackageAddOn();
                $package->setName($packageData['name']);
                $package->setIdentifier($packageData['identifier']);
                $package->setPriceStandard($packageData['price_standard']);
                $package->setPricePremium($packageData['price_premium']);
                $package->setCredits($packageData['credits']);
                $package->setDisplayOrder($packageData['display_order']);
                $package->setIsActive($packageData['is_active']);
                $package->setIsFeatured($packageData['is_featured']);
                $package->setCreatedAt(new \DateTimeImmutable());
                $package->setUpdatedAt(new \DateTimeImmutable());

                $this->entityManager->persist($package);
                $createdPackages++;
            } else {
                // Update existing package
                $existingPackage->setName($packageData['name']);
                $existingPackage->setPriceStandard($packageData['price_standard']);
                $existingPackage->setPricePremium($packageData['price_premium']);
                $existingPackage->setCredits($packageData['credits']);
                $existingPackage->setDisplayOrder($packageData['display_order']);
                $existingPackage->setIsActive($packageData['is_active']);
                $existingPackage->setIsFeatured($packageData['is_featured']);
                $existingPackage->setUpdatedAt(new \DateTimeImmutable());

                $updatedPackages++;
            }
        }

        $this->entityManager->flush();

        $io->success(sprintf(
            'Successfully initialized subscription data: %d subscription plan(s) created, %d updated; %d package(s) created, %d updated',
            $createdPlans,
            $updatedPlans,
            $createdPackages,
            $updatedPackages
        ));

        return Command::SUCCESS;
    }
}