<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\PackageAddOnRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Dedicated controller for credit packages path
 * This controller serves the specific path that's being requested without an admin prefix
 */
class CreditPackagesController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private PackageAddOnRepository $packageAddOnRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        PackageAddOnRepository $packageAddOnRepository
    ) {
        $this->entityManager = $entityManager;
        $this->packageAddOnRepository = $packageAddOnRepository;
    }

    /**
     * Route specifically for the direct public path
     */
    #[Route('/sellersbay/ecommerce-tools/public/credit-packages', name: 'app_credit_packages_direct')]
    #[Route('/credit-packages', name: 'app_credit_packages_short')]
    public function creditPackages(): Response
    {
        // Get add-on packages or use default ones, same as in AdminController
        try {
            $addOns = $this->packageAddOnRepository->findAll();
            
            // Convert add-ons to the format expected by the template
            $packageAddOns = [];
            foreach ($addOns as $addOn) {
                $packageData = $addOn->toArray();
                // Add mock sales counts
                $packageData['sales'] = random_int(5, 100);
                $packageAddOns[] = $packageData;
            }
        } catch (\Exception $e) {
            // Log the error but don't crash the page
            error_log('Error loading package add-ons: ' . $e->getMessage());
            
            // Initialize with empty array to use default packages
            $packageAddOns = [];
        }
        
        // If no add-on packages exist or there was a database error, initialize with default packages
        if (empty($packageAddOns)) {
            $packageAddOns = [
                [
                    'id' => 'micro',
                    'name' => 'Micro',
                    'credits' => 10,
                    'price_standard' => 19.00,
                    'price_premium' => 29.00,
                    'per_credit_price_standard' => 1.90,
                    'per_credit_price_premium' => 2.90,
                    'active' => true,
                    'featured' => false,
                    'sales' => 25,
                    'discount' => null
                ],
                [
                    'id' => 'starter',
                    'name' => 'Starter',
                    'credits' => 100,
                    'price_standard' => 99.00,
                    'price_premium' => 149.00,
                    'per_credit_price_standard' => 0.99,
                    'per_credit_price_premium' => 1.49,
                    'active' => true,
                    'featured' => true,
                    'sales' => 78,
                    'discount' => null
                ],
                [
                    'id' => 'growth',
                    'name' => 'Growth',
                    'credits' => 500,
                    'price_standard' => 399.00,
                    'price_premium' => 599.00,
                    'per_credit_price_standard' => 0.80,
                    'per_credit_price_premium' => 1.20,
                    'active' => true,
                    'featured' => false,
                    'sales' => 42,
                    'discount' => null
                ],
                [
                    'id' => 'pro',
                    'name' => 'Pro',
                    'credits' => 1500,
                    'price_standard' => 999.00,
                    'price_premium' => 1499.00,
                    'per_credit_price_standard' => 0.67,
                    'per_credit_price_premium' => 1.00,
                    'active' => true,
                    'featured' => false,
                    'sales' => 18,
                    'discount' => null
                ],
                [
                    'id' => 'enterprise',
                    'name' => 'Enterprise',
                    'credits' => 3000,
                    'price_standard' => 1799.00,
                    'price_premium' => 2699.00,
                    'per_credit_price_standard' => 0.60,
                    'per_credit_price_premium' => 0.90,
                    'active' => true,
                    'featured' => false,
                    'sales' => 6,
                    'discount' => null
                ]
            ];
        }
        
        // Get recent package sales (in a real app, would fetch from the database)
        $recentSales = [
            [
                'user' => [
                    'id' => 1,
                    'name' => 'John Smith',
                    'email' => 'john.smith@example.com'
                ],
                'package' => 'Pro Package',
                'purchase_date' => new \DateTime('2023-12-10'),
                'price' => 99.00,
                'credits' => 100
            ],
            [
                'user' => [
                    'id' => 2,
                    'name' => 'Sarah Johnson',
                    'email' => 'sarah.j@example.com'
                ],
                'package' => 'Starter Package',
                'purchase_date' => new \DateTime('2023-12-05'),
                'price' => 49.00,
                'credits' => 50
            ],
            [
                'user' => [
                    'id' => 3,
                    'name' => 'Robert Brown',
                    'email' => 'r.brown@example.com'
                ],
                'package' => 'Large Package',
                'purchase_date' => new \DateTime('2023-12-01'),
                'price' => 199.00,
                'credits' => 250
            ]
        ];
        
        return $this->render('admin/credit_packages.html.twig', [
            'packageAddOns' => $packageAddOns,
            'stats' => [
                'active_packages' => count(array_filter($packageAddOns, function($package) { return $package['active']; })),
                'total_sales' => array_reduce($packageAddOns, function($sum, $package) { return $sum + ($package['sales'] ?? 0); }, 0),
                'total_revenue' => array_reduce($packageAddOns, function($sum, $package) { 
                    return $sum + (($package['price_standard'] ?? 0) * ($package['sales'] ?? 0)); 
                }, 0)
            ],
            'recentSales' => $recentSales
        ]);
    }

    /**
     * API endpoint for getting package data
     */
    #[Route('/sellersbay/ecommerce-tools/public/admin/package-addons/{id}/get', name: 'app_credit_packages_get_addon_direct')]
    #[Route('/sellersbay/ecommerce-tools/public/package-addons/{id}/get', name: 'app_credit_packages_get_addon_public')]
    #[Route('/admin/package-addons/{id}/get', name: 'app_credit_packages_get_addon_admin')]
    public function getPackageAddOn(string $id): Response
    {
        try {
            // First try to find by numeric ID
            $packageId = (int)$id;
            $packageAddOn = $this->packageAddOnRepository->find($packageId);
            
            // If not found and we have a findByIdentifier method, try that
            if (!$packageAddOn && method_exists($this->packageAddOnRepository, 'findByIdentifier')) {
                $packageAddOn = $this->packageAddOnRepository->findByIdentifier($id);
            }

            if (!$packageAddOn) {
                return $this->json(['error' => 'Package not found'], 404);
            }

            // Get full data from the package object
            $data = $packageAddOn->toArray();
            
            // Add identifier and ID explicitly to ensure they're included
            $data['id'] = $packageAddOn->getId();
            if (method_exists($packageAddOn, 'getIdentifier')) {
                $data['identifier'] = $packageAddOn->getIdentifier();
            }
            
            // Add fields that may be expected by the JavaScript
            if (method_exists($packageAddOn, 'getDisplayOrder')) {
                $data['displayOrder'] = $packageAddOn->getDisplayOrder();
            }
            
            return $this->json($data);
        } catch (\Exception $e) {
            // Log the error
            error_log('Error retrieving package add-on: ' . $e->getMessage());
            return $this->json(['error' => 'Failed to retrieve package data'], 500);
        }
    }
}