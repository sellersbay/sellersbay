<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\WooCommerceProductRepository;
use App\Service\IntegrationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard')]
    #[IsGranted('ROLE_USER')]
    public function index(
        WooCommerceProductRepository $woocommerceProductRepository,
        IntegrationService $integrationService,
        CacheInterface $cache = null
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        
        // Cache key based on user ID
        $cacheKey = 'dashboard_data_' . $user->getId();
        
        // Try to get data from cache if available
        $dashboardData = $cache 
            ? $cache->get($cacheKey, function (ItemInterface $item) use ($user, $woocommerceProductRepository, $integrationService) {
                $item->expiresAfter(300); // Cache for 5 minutes
                return $this->prepareDashboardData($user, $woocommerceProductRepository, $integrationService);
            })
            : $this->prepareDashboardData($user, $woocommerceProductRepository, $integrationService);

        return $this->render('dashboard/index.html.twig', $dashboardData);
    }
    
    /**
     * AJAX endpoint for refreshing recent activity
     */
    #[Route('/dashboard/recent-activity', name: 'app_dashboard_recent_activity')]
    #[IsGranted('ROLE_USER')]
    public function recentActivity(WooCommerceProductRepository $woocommerceProductRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $recentProducts = $woocommerceProductRepository->findRecentProductsByUser($user);
        
        return $this->render('dashboard/_recent_activity_list.html.twig', [
            'recent_products' => $recentProducts
        ]);
    }
    
    /**
     * Prepare all data needed for the dashboard
     */
    private function prepareDashboardData(User $user, WooCommerceProductRepository $woocommerceProductRepository, IntegrationService $integrationService): array
    {
        // Basic dashboard data
        $woocommerceProductCount = $woocommerceProductRepository->countWooCommerceProductsByUser($user);
        $totalProductCount = $woocommerceProductRepository->count(['owner' => $user]);
        $recentProducts = $woocommerceProductRepository->findRecentProductsByUser($user);
        
        // Get integration statuses
        $integrationStatuses = $integrationService->getIntegrationStatuses($user);
        
        // Enhanced dashboard data
        $productCategories = $woocommerceProductRepository->getProductCountsByCategory();
        $monthlyActivity = $woocommerceProductRepository->getAIProcessedProductsByMonth(6); // Last 6 months
        $contentStats = $woocommerceProductRepository->getContentGenerationStats();
        
        return [
            'user' => $user,
            'products_count' => $totalProductCount,
            'woocommerce_products_count' => $woocommerceProductCount,
            'recent_products' => $recentProducts,
            'available_credits' => $user->getCredits(),
            'product_categories' => $productCategories,
            'monthly_activity' => $monthlyActivity,
            'content_stats' => $contentStats,
            'integration_statuses' => $integrationStatuses,
            'last_updated' => new \DateTime(),
        ];
    }
}