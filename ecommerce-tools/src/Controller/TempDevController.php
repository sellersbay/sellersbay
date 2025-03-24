<?php
/**
 * TEMPORARY DEVELOPMENT CONTROLLER - REMOVE BEFORE PRODUCTION
 * 
 * This controller exists solely to provide authentication-free access to protected pages
 * during development. It creates a security vulnerability and MUST be removed before
 * deploying to production.
 */

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class TempDevController extends AbstractController
{
    private $userRepository;
    private $tokenStorage;

    public function __construct(UserRepository $userRepository, TokenStorageInterface $tokenStorage)
    {
        $this->userRepository = $userRepository;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * TEMPORARY DEVELOPMENT ROUTE - REMOVE BEFORE PRODUCTION
     * This route bypasses normal security to allow access to the dashboard without login.
     */
    #[Route('/dev/dashboard', name: 'dev_dashboard')]
    public function devDashboard(): Response
    {
        // Create a fake user session - this is a temporary development solution
        // For development, we'll use the first admin user in the database
        $user = $this->userRepository->findOneBy([]) ?? (new User())->setEmail('dev@example.com')->setRoles(['ROLE_ADMIN']);
        
        // Log the user in programmatically
        $token = new UsernamePasswordToken(
            $user, 'main', $user->getRoles()
        );
        $this->tokenStorage->setToken($token);
        
        // Forward to the real dashboard template with all necessary variables
        return $this->render('dashboard/index.html.twig', [
            'user' => $user,
            'products_count' => 13,
            'woocommerce_products_count' => 13,
            'available_credits' => 95,
            'generated_content' => 26,
            // Add missing required variables
            'recent_products' => [], // Empty array but satisfies the template requirement
            'product_categories' => [],
            'monthly_activity' => [],
            'content_stats' => [
                'descriptions' => 13, // Direct key as expected by template
                'short_descriptions' => 13,
                'meta_descriptions' => 2,
                'image_alt_text' => 2  // Note the correct key name (singular, not plural)
            ],
            'last_updated' => new \DateTime(),
        ]);
    }

    /**
     * TEMPORARY DEVELOPMENT ROUTE - REMOVE BEFORE PRODUCTION
     * This route bypasses normal security to allow access to the AI dashboard without login.
     */
    #[Route('/dev/ai-dashboard', name: 'dev_ai_dashboard')]
    public function devAiDashboard(): Response
    {
        // Create a fake user session - this is a temporary development solution
        $user = $this->userRepository->findOneBy([]) ?? (new User())->setEmail('dev@example.com')->setRoles(['ROLE_ADMIN']);
        
        // Log the user in programmatically
        $token = new UsernamePasswordToken(
            $user, 'main', $user->getRoles()
        );
        $this->tokenStorage->setToken($token);
        
        // Forward to the real AI dashboard template
        return $this->render('ai/dashboard.html.twig');
    }
}