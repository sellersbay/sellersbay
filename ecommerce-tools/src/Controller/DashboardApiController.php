<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\WooCommerceProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * API endpoints for dashboard AJAX requests
 */
#[Route('/api/dashboard', name: 'api_dashboard_')]
#[IsGranted('ROLE_USER')]
class DashboardApiController extends AbstractController
{
    /**
     * Get charts data for dashboard visualizations
     */
    #[Route('/charts-data', name: 'charts_data', methods: ['GET'])]
    public function getChartsData(
        Request $request,
        WooCommerceProductRepository $productRepository
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();
        
        // Get time range from request (default to 6 months)
        $range = $request->query->getInt('range', 6);
        
        // Limit range to reasonable values
        $range = min(max($range, 1), 24);
        
        // Get chart data
        $productCategories = $productRepository->getProductCountsByCategory();
        $monthlyActivity = $productRepository->getAIProcessedProductsByMonth($range);
        
        return $this->json([
            'product_categories' => $productCategories,
            'monthly_activity' => $monthlyActivity,
            'time_range' => $range
        ]);
    }
    
    /**
     * Get recent activity data
     */
    #[Route('/recent-activity', name: 'recent_activity', methods: ['GET'])]
    public function getRecentActivity(
        Request $request, 
        WooCommerceProductRepository $productRepository
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();
        
        // Get limit from request (default to 5)
        $limit = $request->query->getInt('limit', 5);
        
        // Limit to reasonable values
        $limit = min(max($limit, 1), 20);
        
        // Get recent products
        $recentProducts = $productRepository->findRecentProductsByUser($user, $limit);
        
        // Format data for JSON response
        $formattedProducts = [];
        foreach ($recentProducts as $product) {
            $formattedProducts[] = [
                'id' => $product->getId(),
                'name' => $product->getName(),
                'updatedAt' => $product->getUpdatedAt()->format('Y-m-d H:i'),
                'shortDescription' => $product->getShortDescription(),
                'status' => $product->getStatus(),
                'imageUrl' => $product->getImageUrl()
            ];
        }
        
        return $this->json([
            'products' => $formattedProducts,
            'count' => count($formattedProducts),
            'timestamp' => (new \DateTime())->format('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * Get content statistics
     */
    #[Route('/content-stats', name: 'content_stats', methods: ['GET'])]
    public function getContentStats(WooCommerceProductRepository $productRepository): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        
        // Get content generation statistics
        $contentStats = $productRepository->getContentGenerationStats();
        
        // Get product count to calculate percentages
        $totalProductCount = $productRepository->count(['owner' => $user]);
        
        // Calculate percentages if there are products
        $percentages = [];
        if ($totalProductCount > 0) {
            foreach ($contentStats as $key => $value) {
                $percentages[$key] = round(($value / $totalProductCount) * 100);
            }
        }
        
        return $this->json([
            'content_stats' => $contentStats,
            'percentages' => $percentages,
            'total_products' => $totalProductCount
        ]);
    }
    
    /**
     * Get user credits and subscription info
     */
    #[Route('/user-credits', name: 'user_credits', methods: ['GET'])]
    public function getUserCredits(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        
        return $this->json([
            'credits' => $user->getCredits(),
            'subscription_tier' => $user->getSubscriptionTier(),
            'user_name' => $user->getFirstName() . ' ' . $user->getLastName(),
            'last_updated' => (new \DateTime())->format('Y-m-d H:i:s')
        ]);
    }
}