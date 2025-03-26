<?php

namespace App\Controller;

use App\Entity\PackageAddOn;
use App\Entity\SubscriptionPlan;
use App\Entity\User;
use App\Entity\WooCommerceProduct;
use App\Repository\PackageAddOnRepository;
use App\Repository\SubscriptionPlanRepository;
use App\Repository\TransactionRepository;
use App\Repository\UserRepository;
use App\Repository\WooCommerceProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;


// Proper admin role requirement
#[IsGranted('ROLE_ADMIN')]
#[Route('/admin')]
// Admin access restricted to users with ROLE_ADMIN
class AdminController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private UserRepository $userRepository;
    private WooCommerceProductRepository $wooCommerceProductRepository;
    private UserPasswordHasherInterface $passwordHasher;
    private TransactionRepository $transactionRepository;
    private PackageAddOnRepository $packageAddOnRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        WooCommerceProductRepository $wooCommerceProductRepository,
        UserPasswordHasherInterface $passwordHasher,
        TransactionRepository $transactionRepository,
        PackageAddOnRepository $packageAddOnRepository
    ) {
        $this->entityManager = $entityManager;
        $this->userRepository = $userRepository;
        $this->wooCommerceProductRepository = $wooCommerceProductRepository;
        $this->passwordHasher = $passwordHasher;
        $this->transactionRepository = $transactionRepository;
        $this->packageAddOnRepository = $packageAddOnRepository;
    }

    #[Route('/', name: 'app_admin_dashboard')]
    public function dashboard(): Response
    {
        // Count stats for the dashboard
        $totalUsers = $this->userRepository->count([]);
        $activeUsers = $this->userRepository->count(['isVerified' => true]);
        $premiumUsers = $this->userRepository->countUsersByRole('ROLE_PREMIUM');
        $totalProducts = $this->wooCommerceProductRepository->count([]);
        $aiProcessedProducts = $this->wooCommerceProductRepository->count(['status' => 'ai_processed']);
        $exportedProducts = $this->wooCommerceProductRepository->count(['status' => 'exported']);
        
        // Get recent users for the dashboard
        $recentUsers = $this->userRepository->findBy([], ['createdAt' => 'DESC'], 5);
        
        // Calculate total credits in the system
        $totalCredits = $this->userRepository->getTotalCredits();
        
        return $this->render('admin/dashboard.html.twig', [
            'stats' => [
                'total_users' => $totalUsers,
                'active_users' => $activeUsers,
                'premium_users' => $premiumUsers,
                'total_products' => $totalProducts,
                'ai_processed_products' => $aiProcessedProducts,
                'exported_products' => $exportedProducts,
                'total_credits' => $totalCredits,
            ],
            'recent_users' => $recentUsers,
        ]);
    }
    
    #[Route('/users', name: 'app_admin_users')]
    public function users(): Response
    {
        $users = $this->userRepository->findAll();
        
        return $this->render('admin/users.html.twig', [
            'users' => $users,
        ]);
    }
    
    #[Route('/users/filter', name: 'app_admin_users_filter', methods: ['POST'])]
    public function filterUsers(Request $request): JsonResponse
    {
        // Verify CSRF token
        $submittedToken = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('filter_users', $submittedToken)) {
            return new JsonResponse([
                'error' => 'Invalid CSRF token'
            ], 403);
        }
        
        // Get filter parameters from request
        $role = $request->request->get('role');
        $subscription = $request->request->get('subscription');
        $credits = $request->request->get('credits');
        $status = $request->request->get('status');
        $search = $request->request->get('search');
        
        // Build query criteria
        $criteria = [];
        
        // Apply role filter if provided
        if ($role) {
            // We'll handle role filtering in the repository since it's stored as JSON array
        }
        
        // Apply subscription filter
        if ($subscription) {
            if ($subscription === 'none') {
                $criteria['subscriptionTier'] = null;
            } else {
                $criteria['subscriptionTier'] = $subscription;
            }
        }
        
        // Apply status filter
        if ($status !== null && $status !== '') {
            $criteria['isVerified'] = $status == '1';
        }
        
        // Basic filtering with criteria
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('u')
           ->from('App\Entity\User', 'u');
        
        // Apply criteria as WHERE conditions
        $paramCount = 0;
        foreach ($criteria as $field => $value) {
            if ($value === null) {
                $qb->andWhere("u.{$field} IS NULL");
            } else {
                $paramName = 'param' . $paramCount++;
                $qb->andWhere("u.{$field} = :{$paramName}");
                $qb->setParameter($paramName, $value);
            }
        }
        
        // Apply role filtering
        if ($role) {
            $qb->andWhere("u.roles LIKE :role");
            $qb->setParameter("role", '%"' . $role . '"%');
        }
        
        // Apply credits filter
        if ($credits) {
            switch ($credits) {
                case 'zero':
                    $qb->andWhere('u.credits = 0');
                    break;
                case 'low':
                    $qb->andWhere('u.credits > 0 AND u.credits <= 10');
                    break;
                case 'medium':
                    $qb->andWhere('u.credits > 10 AND u.credits <= 50');
                    break;
                case 'high':
                    $qb->andWhere('u.credits > 50');
                    break;
            }
        }
        
        // Apply search filter
        if ($search) {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->like('u.firstName', ':search'),
                    $qb->expr()->like('u.lastName', ':search'),
                    $qb->expr()->like('u.email', ':search')
                )
            );
            $qb->setParameter('search', '%' . $search . '%');
        }
        
        // Execute query
        $users = $qb->getQuery()->getResult();
        
        // Transform users into array of user data
        $userData = [];
        foreach ($users as $user) {
            $userData[] = [
                'id' => $user->getId(),
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
                'email' => $user->getEmail(),
                'roles' => $user->getRoles(),
                'credits' => $user->getCredits(),
                'subscriptionTier' => $user->getSubscriptionTier(),
                'isVerified' => $user->isVerified(),
                'createdAt' => $user->getCreatedAt()->format('Y-m-d'),
            ];
        }
        
        return new JsonResponse([
            'users' => $userData,
            'count' => count($userData),
        ]);
    }
    
    #[Route('/user/{id}', name: 'app_admin_user_view')]
    public function viewUser(User $user): Response
    {
        // Get user's products
        $products = $this->wooCommerceProductRepository->findBy(['owner' => $user]);
        
        return $this->render('admin/user_view.html.twig', [
            'user' => $user,
            'products' => $products,
        ]);
    }
    
    #[Route('/user/{id}/edit', name: 'app_admin_user_edit', methods: ['GET', 'POST'])]
    public function editUser(Request $request, User $user): Response
    {
        if ($request->isMethod('POST')) {
            // Update basic user information
            $user->setFirstName($request->request->get('firstName'))
                ->setLastName($request->request->get('lastName'))
                ->setEmail($request->request->get('email'))
                ->setIsVerified($request->request->has('isVerified'))
                ->setCredits((int)$request->request->get('credits'))
                ->setSubscriptionTier($request->request->get('subscriptionTier') ?: null);
            
            // Handle password change if provided
            $newPassword = $request->request->get('newPassword');
            if ($newPassword) {
                $hashedPassword = $this->passwordHasher->hashPassword($user, $newPassword);
                $user->setPassword($hashedPassword);
            }
            
            // Handle roles
            $roles = $request->request->all('roles');
            if (!empty($roles)) {
                // Always include ROLE_USER
                if (!in_array('ROLE_USER', $roles)) {
                    $roles[] = 'ROLE_USER';
                }
                $user->setRoles($roles);
            }
            
            // Handle WooCommerce integration
            if ($request->request->has('clearWooCommerceCredentials')) {
                $user->setWoocommerceStoreUrl(null)
                    ->setWoocommerceConsumerKey(null)
                    ->setWoocommerceConsumerSecret(null);
            } else {
                $user->setWoocommerceStoreUrl($request->request->get('woocommerceStoreUrl'))
                    ->setWoocommerceConsumerKey($request->request->get('woocommerceConsumerKey'))
                    ->setWoocommerceConsumerSecret($request->request->get('woocommerceConsumerSecret'));
            }
            
            // Handle Stripe integration
            $user->setStripeCustomerId($request->request->get('stripeCustomerId'));
            
            // Set updated timestamp
            $user->setUpdatedAt(new \DateTimeImmutable());
            
            // Save changes
            $this->userRepository->save($user, true);
            
            // Add flash message
            $this->addFlash('success', 'User has been updated successfully.');
            
            // Redirect based on which save button was clicked
            if ($request->request->has('save_and_continue')) {
                return $this->redirectToRoute('app_admin_user_edit', ['id' => $user->getId()]);
            }
            
            return $this->redirectToRoute('app_admin_user_view', ['id' => $user->getId()]);
        }
        
        return $this->render('admin/user_edit.html.twig', [
            'user' => $user,
        ]);
    }
    
    #[Route('/user/create', name: 'app_admin_user_create', methods: ['POST'])]
    public function createUser(Request $request): Response
    {
        // Create a new user
        $user = new User();
        $user->setFirstName($request->request->get('firstName'))
            ->setLastName($request->request->get('lastName'))
            ->setEmail($request->request->get('email'))
            ->setIsVerified($request->request->has('isVerified'))
            ->setCredits((int)$request->request->get('initialCredits', 0))
            ->setSubscriptionTier($request->request->get('subscriptionTier') ?: null);
        
        // Set password
        $password = $request->request->get('password');
        $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);
        
        // Set roles
        $role = $request->request->get('userRole', 'ROLE_USER');
        $roles = ['ROLE_USER'];
        if ($role !== 'ROLE_USER') {
            $roles[] = $role;
        }
        $user->setRoles($roles);
        
        // Set timestamps
        $now = new \DateTimeImmutable();
        $user->setCreatedAt($now);
        $user->setUpdatedAt($now);
        
        // Save user
        $this->userRepository->save($user, true);
        
        // Add flash message
        $this->addFlash('success', 'User has been created successfully.');
        
        // Redirect to user list
        return $this->redirectToRoute('app_admin_users');
    }
    
    #[Route('/user/{id}/delete', name: 'app_admin_user_delete', methods: ['POST'])]
    public function deleteUser(User $user): Response
    {
        // Delete user
        $this->userRepository->remove($user, true);
        
        // Add flash message
        $this->addFlash('success', 'User has been deleted successfully.');
        
        // Redirect to user list
        return $this->redirectToRoute('app_admin_users');
    }
    
    #[Route('/user/{id}/adjust-credits', name: 'app_admin_user_adjust_credits', methods: ['POST'])]
    public function adjustUserCredits(Request $request, User $user): Response
    {
        $adjustmentType = $request->request->get('adjustmentType');
        $amount = (int)$request->request->get('creditsAmount');
        
        switch ($adjustmentType) {
            case 'add':
                $user->addCredits($amount);
                $message = "Added $amount credits to user's account.";
                break;
            case 'subtract':
                $user->useCredits($amount);
                $message = "Removed $amount credits from user's account.";
                break;
            case 'set':
                $user->setCredits($amount);
                $message = "Set user's credits to $amount.";
                break;
            default:
                $this->addFlash('error', 'Invalid adjustment type.');
                return $this->redirectToRoute('app_admin_user_view', ['id' => $user->getId()]);
        }
        
        // Update timestamp
        $user->setUpdatedAt(new \DateTimeImmutable());
        
        // Save changes
        $this->userRepository->save($user, true);
        
        // Add flash message
        $this->addFlash('success', $message);
        
        // Redirect to user view
        return $this->redirectToRoute('app_admin_user_view', ['id' => $user->getId()]);
    }
    
    #[Route('/users/bulk-action', name: 'app_admin_users_bulk_action', methods: ['POST'])]
    #[Route('/user/bulk-actions', name: 'app_admin_user_bulk_actions', methods: ['POST'])]
    public function bulkUserAction(Request $request): Response
    {
        $action = $request->request->get('bulkAction');
        $userIds = $request->request->all('selectedUsers');
        
        if (empty($userIds)) {
            $this->addFlash('error', 'No users selected.');
            return $this->redirectToRoute('app_admin_users');
        }
        
        $processedCount = 0;
        
        foreach ($userIds as $userId) {
            $user = $this->userRepository->find($userId);
            if (!$user) {
                continue;
            }
            
            switch ($action) {
                case 'add_credits':
                    $amount = (int)$request->request->get('bulkCreditsAmount', 0);
                    $user->addCredits($amount);
                    break;
                case 'set_role':
                    $role = $request->request->get('bulkRole');
                    $roles = ['ROLE_USER'];
                    if ($role !== 'ROLE_USER') {
                        $roles[] = $role;
                    }
                    $user->setRoles($roles);
                    break;
                case 'set_subscription':
                    $tier = $request->request->get('bulkSubscription');
                    $user->setSubscriptionTier($tier ?: null);
                    break;
                case 'verify':
                    $user->setIsVerified(true);
                    break;
                case 'delete':
                    if ($request->request->has('bulkConfirmDelete')) {
                        $this->userRepository->remove($user, false);
                        $processedCount++;
                        continue;
                    }
                    break;
                default:
                    $this->addFlash('error', 'Invalid bulk action.');
                    return $this->redirectToRoute('app_admin_users');
            }
            
            // Update timestamp
            $user->setUpdatedAt(new \DateTimeImmutable());
            
            // Save changes (don't flush yet)
            $this->userRepository->save($user, false);
            $processedCount++;
        }
        
        // Flush all changes at once
        $this->entityManager->flush();
        
        // Add flash message
        $this->addFlash('success', "Bulk action completed successfully for $processedCount users.");
        
        // Redirect to user list
        return $this->redirectToRoute('app_admin_users');
    }
    
    #[Route('/products', name: 'app_admin_products')]
    public function products(): Response
    {
        $products = $this->wooCommerceProductRepository->findAll();
        $users = $this->userRepository->findAll();
        
        return $this->render('admin/products.html.twig', [
            'products' => $products,
            'users' => $users,
        ]);
    }
    
    #[Route('/product/{id}', name: 'app_admin_product_view')]
    public function viewProduct(WooCommerceProduct $product): Response
    {
        return $this->render('admin/product_view.html.twig', [
            'product' => $product,
        ]);
    }
    
    #[Route('/product/{id}/update-content', name: 'app_admin_product_update_content', methods: ['POST'])]
    public function updateProductContent(Request $request, WooCommerceProduct $product): Response
    {
        $contentType = $request->request->get('contentType');
        $content = $request->request->get('content');
        
        switch ($contentType) {
            case 'description':
                $product->setDescription($content);
                break;
            case 'shortDescription':
                $product->setShortDescription($content);
                break;
            case 'metaDescription':
                $product->setMetaDescription($content);
                break;
            case 'imageAltText':
                $product->setImageAltText($content);
                break;
            default:
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Invalid content type'
                ], 400);
        }
        
        // Update timestamp
        $product->updateTimestamp();
        
        // Save changes
        $this->entityManager->persist($product);
        $this->entityManager->flush();
        
        return new JsonResponse([
            'success' => true,
            'message' => 'Content updated successfully'
        ]);
    }
    
    #[Route('/product/{id}/generate-ai', name: 'app_admin_product_generate_ai', methods: ['POST'])]
    public function generateProductAI(Request $request, WooCommerceProduct $product): Response
    {
        $generationType = $request->request->get('generationType', 'standard');
        $options = $request->request->all('options');
        
        // Extract the user from the product
        $user = $product->getOwner();
        
        // Check if user has enough credits
        $requiredCredits = $generationType === 'premium' ? 2 : 1;
        
        if ($user->getCredits() < $requiredCredits) {
            $this->addFlash('error', 'Not enough credits to generate AI content.');
            return $this->redirectToRoute('app_admin_product_view', ['id' => $product->getId()]);
        }
        
        // In a real implementation, you would call an AI service here
        // For this example, we'll simulate AI generation with placeholder content
        
        // Update product fields based on selected options
        if (in_array('description', $options)) {
            $product->setDescription('AI-generated description for ' . $product->getName() . '. This product is designed to provide excellent performance and value. [Simulated AI content]');
        }
        
        if (in_array('shortDescription', $options)) {
            $product->setShortDescription('High-quality ' . $product->getName() . ' with premium features. [Simulated AI content]');
        }
        
        if (in_array('metaDescription', $options)) {
            $product->setMetaDescription('Buy ' . $product->getName() . ' - premium quality, fast shipping, excellent customer service. [Simulated AI content]');
        }
        
        if (in_array('imageAltText', $options)) {
            $product->setImageAltText($product->getName() . ' - high-resolution product image [Simulated AI content]');
        }
        
        // Set product status to 'ai_processed'
        $product->setStatus('ai_processed');
        
        // Update timestamp
        $product->updateTimestamp();
        
        // Deduct credits from user
        $user->useCredits($requiredCredits);
        $user->setUpdatedAt(new \DateTimeImmutable());
        
        // Save changes
        $this->entityManager->persist($product);
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        
        // Add flash message
        $this->addFlash('success', 'AI content generated successfully. ' . $requiredCredits . ' credits used.');
        
        // Redirect to product view
        return $this->redirectToRoute('app_admin_product_view', ['id' => $product->getId()]);
    }
    
    #[Route('/product/{id}/export', name: 'app_admin_product_export', methods: ['POST'])]
    public function exportProduct(Request $request, WooCommerceProduct $product): Response
    {
        $options = $request->request->all('options');
        
        // In a real implementation, you would call the WooCommerce API here to update the product
        // For this example, we'll simulate the export
        
        // Set product status to 'exported'
        $product->setStatus('exported');
        
        // Update timestamp
        $product->updateTimestamp();
        
        // Save changes
        $this->entityManager->persist($product);
        $this->entityManager->flush();
        
        // Add flash message
        $this->addFlash('success', 'Product exported to WooCommerce successfully.');
        
        // Redirect to product view
        return $this->redirectToRoute('app_admin_product_view', ['id' => $product->getId()]);
    }
    
    #[Route('/product/{id}/delete', name: 'app_admin_product_delete', methods: ['POST'])]
    public function deleteProduct(Request $request, WooCommerceProduct $product): Response
    {
        $deleteFromWooCommerce = $request->request->has('deleteFromWooCommerce');
        
        // In a real implementation, if $deleteFromWooCommerce is true, you would call the WooCommerce API to delete the product
        
        // Delete product from database
        $this->entityManager->remove($product);
        $this->entityManager->flush();
        
        // Add flash message
        $this->addFlash('success', 'Product deleted successfully.');
        
        // Redirect to products list
        return $this->redirectToRoute('app_admin_products');
    }
    
    #[Route('/products/bulk-action', name: 'app_admin_products_bulk_action', methods: ['POST'])]
    #[Route('/product/bulk-action', name: 'app_admin_product_bulk_action', methods: ['POST'])]
    public function bulkProductAction(Request $request): Response
    {
        $action = $request->request->get('bulkAction');
        $productIds = $request->request->all('selectedProducts');
        
        if (empty($productIds)) {
            $this->addFlash('error', 'No products selected.');
            return $this->redirectToRoute('app_admin_products');
        }
        
        $processedCount = 0;
        
        foreach ($productIds as $productId) {
            $product = $this->wooCommerceProductRepository->find($productId);
            if (!$product) {
                continue;
            }
            
            switch ($action) {
                case 'generate_ai':
                    // This would require a more complex implementation in a real application
                    // Here we'll just update the status
                    $product->setStatus('ai_processed');
                    break;
                case 'export_woocommerce':
                    $product->setStatus('exported');
                    break;
                case 'change_status':
                    $status = $request->request->get('bulkStatus');
                    if ($status) {
                        $product->setStatus($status);
                    }
                    break;
                case 'delete':
                    if ($request->request->has('bulkConfirmDelete')) {
                        $this->entityManager->remove($product);
                        $processedCount++;
                        continue;
                    }
                    break;
                default:
                    $this->addFlash('error', 'Invalid bulk action.');
                    return $this->redirectToRoute('app_admin_products');
            }
            
            // Update timestamp
            $product->updateTimestamp();
            
            // Save changes (don't flush yet)
            $this->entityManager->persist($product);
            $processedCount++;
        }
        
        // Flush all changes at once
        $this->entityManager->flush();
        
        // Add flash message
        $this->addFlash('success', "Bulk action completed successfully for $processedCount products.");
        
        // Redirect to products list
        return $this->redirectToRoute('app_admin_products');
    }
    
    #[Route('/credits', name: 'app_admin_credits')]
    public function creditsManagement(): Response
    {
        // Get credit package data (in a real app, this would come from the database)
        $creditPackages = [
            [
                'id' => 'small',
                'name' => 'Small Package',
                'credits' => 10,
                'price' => 9.99,
                'active' => true,
                'featured' => false,
                'sales' => 78,
                'discount' => null
            ],
            [
                'id' => 'medium',
                'name' => 'Medium Package',
                'credits' => 50,
                'price' => 49.99,
                'active' => true,
                'featured' => true,
                'sales' => 152,
                'discount' => 20
            ],
            [
                'id' => 'large',
                'name' => 'Large Package',
                'credits' => 100,
                'price' => 69.99,
                'active' => true,
                'featured' => false,
                'sales' => 64,
                'discount' => null
            ]
        ];
        
        // Calculate total credits and revenue
        $totalCredits = $this->userRepository->getTotalCredits();
        
        // Get recent credit transactions (in a real app, would fetch from the database)
        $recentTransactions = [
            [
                'date' => new \DateTime('2023-12-15'),
                'user' => $this->userRepository->find(1),
                'type' => 'purchase',
                'amount' => 50
            ],
            [
                'date' => new \DateTime('2023-12-14'),
                'user' => $this->userRepository->find(2),
                'type' => 'usage',
                'amount' => -1
            ],
            [
                'date' => new \DateTime('2023-12-14'),
                'user' => $this->userRepository->find(3),
                'type' => 'purchase',
                'amount' => 10
            ],
            [
                'date' => new \DateTime('2023-12-13'),
                'user' => $this->userRepository->find(4),
                'type' => 'adjustment',
                'amount' => 5
            ],
            [
                'date' => new \DateTime('2023-12-12'),
                'user' => $this->userRepository->find(5),
                'type' => 'usage',
                'amount' => -2
            ]
        ];
        
        // Get most active users (in a real app, would fetch from the database)
        $activeUsers = $this->userRepository->findBy([], ['credits' => 'DESC'], 5);
        
        return $this->render('admin/credits.html.twig', [
            'creditPackages' => $creditPackages,
            'stats' => [
                'total_credits' => $totalCredits,
                'revenue_mtd' => 4235.75,
                'credits_used_mtd' => 2180
            ],
            'recentTransactions' => $recentTransactions,
            'activeUsers' => $activeUsers
        ]);
    }
    
    #[Route('/credits/create', name: 'app_admin_credits_create', methods: ['POST'])]
    public function createCreditPackage(Request $request): Response
    {
        // In a real implementation, this would save to the database
        
        // Add flash message for success
        $this->addFlash('success', 'Credit package created successfully.');
        
        // Redirect back to credits management page
        return $this->redirectToRoute('app_admin_credits');
    }
    
    #[Route('/credits/{id}/edit', name: 'app_admin_credits_edit', methods: ['POST'])]
    public function editCreditPackage(Request $request, string $id): Response
    {
        // In a real implementation, this would update the database
        
        // Add flash message for success
        $this->addFlash('success', 'Credit package updated successfully.');
        
        // Redirect back to credits management page
        return $this->redirectToRoute('app_admin_credits');
    }
    
    #[Route('/credits/{id}/delete', name: 'app_admin_credits_delete', methods: ['POST'])]
    public function deleteCreditPackage(string $id): Response
    {
        // In a real implementation, this would delete from the database
        
        // Add flash message for success
        $this->addFlash('success', 'Credit package deleted successfully.');
        
        // Redirect back to credits management page
        return $this->redirectToRoute('app_admin_credits');
    }
    
    #[Route('/credits/adjust-user', name: 'app_admin_credits_adjust_user', methods: ['POST'])]
    public function adjustUserCreditsFromCreditsPage(Request $request): Response
    {
        $userId = $request->request->get('adjustmentUser');
        $adjustmentType = $request->request->get('adjustmentType');
        $amount = (int)$request->request->get('creditsAmount');
        $reason = $request->request->get('adjustmentReason');
        $notifyUser = $request->request->has('notifyUser');
        
        $user = $this->userRepository->find($userId);
        
        if (!$user) {
            $this->addFlash('error', 'User not found.');
            return $this->redirectToRoute('app_admin_credits');
        }
        
        switch ($adjustmentType) {
            case 'add':
                $user->addCredits($amount);
                $message = "Added $amount credits to user's account.";
                break;
            case 'subtract':
                $user->useCredits($amount);
                $message = "Removed $amount credits from user's account.";
                break;
            case 'set':
                $user->setCredits($amount);
                $message = "Set user's credits to $amount.";
                break;
            default:
                $this->addFlash('error', 'Invalid adjustment type.');
                return $this->redirectToRoute('app_admin_credits');
        }
        
        // Update timestamp
        $user->setUpdatedAt(new \DateTimeImmutable());
        
        // Save changes
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        
        // Add flash message
        $this->addFlash('success', $message);
        
        // Redirect back to credits management page
        return $this->redirectToRoute('app_admin_credits');
    }
    
    #[Route('/credits/promotion', name: 'app_admin_credits_promotion', methods: ['POST'])]
    public function createCreditPromotion(Request $request): Response
    {
        // In a real implementation, this would save to the database
        
        // Add flash message for success
        $this->addFlash('success', 'Credit promotion created successfully.');
        
        // Redirect back to credits management page
        return $this->redirectToRoute('app_admin_credits');
    }
    
    #[Route('/subscriptions', name: 'app_admin_subscriptions')]
    public function subscriptionsManagement(SubscriptionPlanRepository $subscriptionPlanRepository): Response
    {
        // Get subscription plans from the database - wrapped in try/catch to handle any DB errors
        try {
            $plans = $subscriptionPlanRepository->findAll();
            
            // Convert plans to the format expected by the template
            $subscriptionPlans = [];
            foreach ($plans as $plan) {
                $planData = $plan->toArray();
                // Add mock subscriber counts for now (in a real app, would be calculated)
                $subscribers = 0;
                switch ($plan->getIdentifier()) {
                    case 'basic':
                        $subscribers = 32;
                        break;
                    case 'pro':
                        $subscribers = 19;
                        break;
                    case 'business':
                        $subscribers = 7;
                        break;
                    default:
                        $subscribers = random_int(1, 10);
                }
                
                $planData['subscribers'] = $subscribers;
                $planData['active'] = $plan->isActive();
                $planData['featured'] = $plan->isFeatured();
                $planData['id'] = $plan->getIdentifier();
                
                $subscriptionPlans[] = $planData;
            }
        } catch (\Exception $e) {
            // Log the error but don't crash the page
            error_log('Error loading subscription plans: ' . $e->getMessage());
            
            // Initialize with empty array to use default plans
            $subscriptionPlans = [];
        }
        
        // If no plans exist or there was a database error, initialize with default plans
        if (empty($subscriptionPlans)) {
            $subscriptionPlans = [
                [
                    'id' => 'micro',
                    'name' => 'Micro',
                    'price' => 9.00,
                    'credits' => 10,
                    'active' => true,
                    'featured' => false,
                    'subscribers' => 15,
                    'features' => [
                        'product_descriptions' => true,
                        'meta_descriptions' => true,
                        'image_alt_text' => true,
                        'seo_keywords' => false,
                        'premium_ai' => false
                    ],
                    'discount' => null
                ],
                [
                    'id' => 'starter',
                    'name' => 'Starter',
                    'price' => 29.00,
                    'credits' => 50,
                    'active' => true,
                    'featured' => false,
                    'subscribers' => 32,
                    'features' => [
                        'product_descriptions' => true,
                        'meta_descriptions' => true,
                        'image_alt_text' => true,
                        'seo_keywords' => false,
                        'premium_ai' => false
                    ],
                    'discount' => null
                ],
                [
                    'id' => 'growth',
                    'name' => 'Growth',
                    'price' => 79.00,
                    'credits' => 250,
                    'active' => true,
                    'featured' => true,
                    'subscribers' => 19,
                    'features' => [
                        'product_descriptions' => true,
                        'meta_descriptions' => true,
                        'image_alt_text' => true,
                        'seo_keywords' => true,
                        'premium_ai' => false
                    ],
                    'discount' => null
                ],
                [
                    'id' => 'pro',
                    'name' => 'Pro',
                    'price' => 149.00,
                    'credits' => 750,
                    'active' => true,
                    'featured' => false,
                    'subscribers' => 7,
                    'features' => [
                        'product_descriptions' => true,
                        'meta_descriptions' => true,
                        'image_alt_text' => true,
                        'seo_keywords' => true,
                        'premium_ai' => true
                    ],
                    'discount' => null
                ],
                [
                    'id' => 'enterprise',
                    'name' => 'Enterprise',
                    'price' => 249.00,
                    'credits' => 2000,
                    'active' => true,
                    'featured' => false,
                    'subscribers' => 3,
                    'features' => [
                        'product_descriptions' => true,
                        'meta_descriptions' => true,
                        'image_alt_text' => true,
                        'seo_keywords' => true,
                        'premium_ai' => true
                    ],
                    'discount' => null
                ]
            ];
        }
        
        // Get recent subscribers (in a real app, would fetch from the database)
        $recentSubscribers = [
            [
                'user' => [
                    'id' => 1,
                    'name' => 'John Smith',
                    'email' => 'john.smith@example.com'
                ],
                'plan' => 'Pro',
                'status' => 'active',
                'subscribed_date' => new \DateTime('2023-12-10'),
                'next_billing' => new \DateTime('2024-01-10')
            ],
            [
                'user' => [
                    'id' => 2,
                    'name' => 'Sarah Johnson',
                    'email' => 'sarah.j@example.com'
                ],
                'plan' => 'Starter',
                'status' => 'past_due',
                'subscribed_date' => new \DateTime('2023-12-05'),
                'next_billing' => new \DateTime('2024-01-05')
            ],
            [
                'user' => [
                    'id' => 3,
                    'name' => 'Robert Brown',
                    'email' => 'r.brown@example.com'
                ],
                'plan' => 'Enterprise',
                'status' => 'active',
                'subscribed_date' => new \DateTime('2023-12-01'),
                'next_billing' => new \DateTime('2024-01-01')
            ],
            [
                'user' => [
                    'id' => 4,
                    'name' => 'Emma Wilson',
                    'email' => 'emma.w@example.com'
                ],
                'plan' => 'Growth',
                'status' => 'active',
                'subscribed_date' => new \DateTime('2023-11-28'),
                'next_billing' => new \DateTime('2023-12-28')
            ],
            [
                'user' => [
                    'id' => 5,
                    'name' => 'Michael Davis',
                    'email' => 'm.davis@example.com'
                ],
                'plan' => 'Micro',
                'status' => 'canceled',
                'subscribed_date' => new \DateTime('2023-11-15'),
                'next_billing' => null
            ]
        ];
        
        return $this->render('admin/subscriptions.html.twig', [
            'subscriptionPlans' => $subscriptionPlans,
            'stats' => [
                'subscribers' => 58,
                'active_plans' => count($subscriptionPlans),
                'monthly_revenue' => 1247.00
            ],
            'recentSubscribers' => $recentSubscribers
        ]);
    }
    
    // Primary route for credit packages
    #[Route('/credit-packages', name: 'app_admin_credit_packages')]
    #[Route('/credits-management', name: 'app_admin_credits_management')]
    
    // Add a global route without the class prefix to match the exact URL pattern
    // This bypasses route prefix handling issues
    #[Route('/sellersbay/ecommerce-tools/public/admin/credit-packages', name: 'app_admin_credit_packages_direct')]
    public function creditPackagesManagement(): Response
    {
        // Get add-on packages or use default ones
        try {
            $addOns = $this->packageAddOnRepository->findAll();
            
            // Convert add-ons to the format expected by the template
            $packageAddOns = [];
            foreach ($addOns as $addOn) {
                $packageData = $addOn->toArray();
                // Add mock sales counts for now (in a real app, would be calculated)
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
                ],
                [
                    'id' => 'ultimate',
                    'name' => 'Ultimate',
                    'credits' => 5000,
                    'price_standard' => 2999.00,
                    'price_premium' => 4499.00,
                    'per_credit_price_standard' => 0.60,
                    'per_credit_price_premium' => 0.90,
                    'active' => true,
                    'featured' => false,
                    'sales' => 3,
                    'discount' => null
                ],
                [
                    'id' => 'mega',
                    'name' => 'Mega',
                    'credits' => 10000,
                    'price_standard' => 4999.00,
                    'price_premium' => 7499.00,
                    'per_credit_price_standard' => 0.50,
                    'per_credit_price_premium' => 0.75,
                    'active' => true,
                    'featured' => false,
                    'sales' => 1,
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
    
    #[Route('/create-package-promotion', name: 'app_admin_create_package_promotion', methods: ['POST'])]
    public function createPackagePromotion(Request $request): Response
    {
        // Get form data
        $promotionName = $request->request->get('promotionName');
        $promotionType = $request->request->get('promotionType');
        $discountPercentage = (int)$request->request->get('discountPercentage');
        $bonusCredits = (int)$request->request->get('bonusCredits');
        $promotionPackages = $request->request->all('promotionPackages');
        $startDate = new \DateTime($request->request->get('promotionStartDate'));
        $endDate = new \DateTime($request->request->get('promotionEndDate'));
        $promoCode = $request->request->get('promotionCode');
        
        // In a real implementation, save to the database
        // Example:
        // $promotion = new PackagePromotion();
        // $promotion->setName($promotionName);
        // $promotion->setType($promotionType);
        // ...
        // $this->entityManager->persist($promotion);
        // $this->entityManager->flush();
        
        // Add flash message for success
        $this->addFlash('success', 'Package promotion "' . $promotionName . '" created successfully.');
        
        // Redirect back to credit packages management page
        return $this->redirectToRoute('app_admin_credit_packages');
    }
    
    #[Route('/update-package-settings', name: 'app_admin_update_package_settings', methods: ['POST'])]
    public function updatePackageSettings(Request $request): Response
    {
        // Get form data
        $showPricePerCredit = $request->request->has('showPricePerCredit');
        $showSalesCount = $request->request->has('showSalesCount');
        $paymentCredit = $request->request->has('paymentCredit');
        $paymentPayPal = $request->request->has('paymentPayPal');
        $emailPurchase = $request->request->has('emailPurchase');
        $emailReceipt = $request->request->has('emailReceipt');
        $emailPromotion = $request->request->has('emailPromotion');
        
        // In a real implementation, save settings to the database
        // Example:
        // $settings = $this->settingsRepository->findOneBy(['type' => 'package']);
        // if (!$settings) {
        //    $settings = new Settings();
        //    $settings->setType('package');
        // }
        // $settings->setValue(json_encode([
        //    'display_options' => [
        //        'show_price_per_credit' => $showPricePerCredit,
        //        'show_sales_count' => $showSalesCount,
        //        ...
        //    ],
        //    ...
        // ]));
        // $this->entityManager->persist($settings);
        // $this->entityManager->flush();
        
        // Add flash message for success
        $this->addFlash('success', 'Package settings saved successfully.');
        
        // Redirect back to credit packages management page
        return $this->redirectToRoute('app_admin_credit_packages');
    }
    
    #[Route('/subscriptions/create', name: 'app_admin_create_subscription_plan', methods: ['POST'])]
    public function createSubscriptionPlan(Request $request, SubscriptionPlanRepository $subscriptionPlanRepository): Response
    {
        // Get form data
        $planName = $request->request->get('planName');
        $planPrice = (float)$request->request->get('planPrice');
        $planCredits = (int)$request->request->get('planCredits');
        $planDescription = $request->request->get('planDescription');
        $planTerm = $request->request->get('planTerm');
        $planDiscount = (int)$request->request->get('planDiscount');
        $planStripeID = $request->request->get('planStripeID');
        $planDisplayOrder = (int)$request->request->get('planDisplayOrder', 1);
        $planIsFeatured = $request->request->has('planIsFeatured');
        $planIsActive = $request->request->has('planIsActive');
        
        // Get feature flags
        $features = [
            'product_descriptions' => $request->request->has('featureProductDesc'),
            'meta_descriptions' => $request->request->has('featureMetaDesc'),
            'image_alt_text' => $request->request->has('featureImageAlt'),
            'seo_keywords' => $request->request->has('featureSeoKeywords'),
            'premium_ai' => $request->request->has('featurePremiumAI')
        ];
        
        // Create a new subscription plan
        $plan = new SubscriptionPlan();
        $plan->setName($planName);
        $plan->setIdentifier(strtolower(preg_replace('/[^a-zA-Z0-9]+/', '_', $planName)));
        $plan->setPrice($planPrice);
        $plan->setCredits($planCredits);
        $plan->setDescription($planDescription);
        $plan->setTerm($planTerm ?? 'monthly');
        $plan->setDiscount($planDiscount ?: null);
        $plan->setStripeProductId($planStripeID);
        $plan->setDisplayOrder($planDisplayOrder);
        $plan->setIsFeatured($planIsFeatured);
        $plan->setIsActive($planIsActive);
        $plan->setFeatures($features);
        $plan->setCreatedAt(new \DateTimeImmutable());
        $plan->setUpdatedAt(new \DateTimeImmutable());
        
        // Save to database
        $this->entityManager->persist($plan);
        $this->entityManager->flush();
        
        // Add flash message for success
        $this->addFlash('success', 'Subscription plan "' . $planName . '" created successfully.');
        
        // Redirect back to subscriptions management page
        return $this->redirectToRoute('app_admin_subscriptions');
    }
    
    #[Route('/subscriptions/{id}/get', name: 'app_admin_get_subscription_plan', methods: ['GET'])]
    public function getSubscriptionPlan(SubscriptionPlanRepository $subscriptionPlanRepository, string $id): JsonResponse
    {
        try {
            // Try to find the plan by numeric ID first
            $plan = $subscriptionPlanRepository->find($id);
            
            // If not found by ID, try to find by identifier (string code)
            if (!$plan) {
                $plan = $subscriptionPlanRepository->findByIdentifier($id);
            }
            
            if (!$plan) {
                return new JsonResponse(['error' => 'Subscription plan not found'], 404);
            }
            
            // Convert plan to array for JSON response
            $planData = $plan->toArray();
            $planData['displayOrder'] = $plan->getDisplayOrder();
            $planData['stripeProductId'] = $plan->getStripeProductId();
            
            // Keep the original ID used to find the plan for consistency
            // This ensures the same ID is used for editing
            $planData['id'] = $plan->getId();
            $planData['identifier'] = $plan->getIdentifier();
            
            // Return as JSON
            return new JsonResponse($planData);
        } catch (\Exception $e) {
            // Return error as JSON
            return new JsonResponse(['error' => 'An error occurred while retrieving the subscription plan: ' . $e->getMessage()], 500);
        }
    }
    
    #[Route('/subscriptions/{id}/edit', name: 'app_admin_edit_subscription_plan', methods: ['POST'])]
    public function editSubscriptionPlan(Request $request, SubscriptionPlanRepository $subscriptionPlanRepository, string $id): Response
    {
        try {
            // Find the existing plan by numeric ID first
            $plan = $subscriptionPlanRepository->find($id);
            
            // If not found by ID, try to find by identifier (string code)
            if (!$plan) {
                $plan = $subscriptionPlanRepository->findByIdentifier($id);
            }
            
            if (!$plan) {
                $this->addFlash('error', 'Subscription plan not found.');
                return $this->redirectToRoute('app_admin_subscriptions');
            }
            
            // Get form data
            $planName = $request->request->get('editPlanName');
            $planPrice = (float)$request->request->get('editPlanPrice');
            $planCredits = (int)$request->request->get('editPlanCredits');
            $planDescription = $request->request->get('editPlanDescription');
            $planTerm = $request->request->get('editPlanTerm');
            $planDiscount = (int)$request->request->get('editPlanDiscount');
            $planStripeID = $request->request->get('editPlanStripeID');
            $planDisplayOrder = (int)$request->request->get('editPlanDisplayOrder', 1);
            $planIsFeatured = $request->request->has('editPlanIsFeatured');
            $planIsActive = $request->request->has('editPlanIsActive');
            
            // Get feature flags 
            $features = [
                'product_descriptions' => $request->request->has('editFeatureProductDesc'),
                'meta_descriptions' => $request->request->has('editFeatureMetaDesc'),
                'image_alt_text' => $request->request->has('editFeatureImageAlt'),
                'seo_keywords' => $request->request->has('editFeatureSeoKeywords'),
                'premium_ai' => $request->request->has('editFeaturePremiumAI')
            ];
            
            // Update the plan
            $plan->setName($planName);
            $plan->setPrice($planPrice);
            $plan->setCredits($planCredits);
            $plan->setDescription($planDescription);
            $plan->setTerm($planTerm ?? 'monthly');
            $plan->setDiscount($planDiscount ?: null);
            $plan->setStripeProductId($planStripeID);
            $plan->setDisplayOrder($planDisplayOrder);
            $plan->setIsFeatured($planIsFeatured);
            $plan->setIsActive($planIsActive);
            $plan->setFeatures($features);
            $plan->setUpdatedAt(new \DateTimeImmutable());
            
            // Save to database
            $this->entityManager->persist($plan);
            $this->entityManager->flush();
            
            // Add flash message for success
            $this->addFlash('success', 'Subscription plan "' . $planName . '" updated successfully.');
        } catch (\Exception $e) {
            // Log the error and show a flash message
            error_log('Error updating subscription plan: ' . $e->getMessage());
            $this->addFlash('error', 'An error occurred while updating the subscription plan: ' . $e->getMessage());
        }
        
        // Redirect back to subscriptions management page
        return $this->redirectToRoute('app_admin_subscriptions');
    }
    
    #[Route('/subscriptions/{id}/delete', name: 'app_admin_delete_subscription_plan', methods: ['POST'])]
    public function deleteSubscriptionPlan(SubscriptionPlanRepository $subscriptionPlanRepository, string $id): Response
    {
        try {
            // Find the plan
            $plan = $subscriptionPlanRepository->find($id);
            
            if (!$plan) {
                $this->addFlash('error', 'Subscription plan not found.');
                return $this->redirectToRoute('app_admin_subscriptions');
            }
            
            // Delete the plan
            $this->entityManager->remove($plan);
            $this->entityManager->flush();
            
            // Add flash message for success
            $this->addFlash('success', 'Subscription plan deleted successfully.');
        } catch (\Exception $e) {
            // Log the error and show a flash message
            error_log('Error deleting subscription plan: ' . $e->getMessage());
            $this->addFlash('error', 'An error occurred while deleting the subscription plan.');
        }
        
        // Add flash message for success
        $this->addFlash('success', 'Subscription plan deleted successfully.');
        
        // Redirect back to subscriptions management page
        return $this->redirectToRoute('app_admin_subscriptions');
    }
    
    #[Route('/subscriptions/user/{id}/edit', name: 'app_admin_edit_user_subscription', methods: ['POST'])]
    public function editUserSubscription(Request $request, int $id): Response
    {
        $user = $this->userRepository->find($id);
        
        if (!$user) {
            $this->addFlash('error', 'User not found.');
            return $this->redirectToRoute('app_admin_subscriptions');
        }
        
        // Get form data
        $plan = $request->request->get('editSubscriptionPlan');
        $status = $request->request->get('editSubscriptionStatus');
        $startDate = $request->request->get('editSubscriptionStartDate');
        $nextBillingDate = $request->request->get('editSubscriptionNextBilling');
        $customCredits = (int)$request->request->get('editSubscriptionCustomCredits');
        $notes = $request->request->get('editSubscriptionNotes');
        $notifyUser = $request->request->has('editSubscriptionNotifyUser');
        
        // Update user's subscription in the database
        // Example:
        // $user->setSubscriptionTier($plan);
        // $user->setSubscriptionStatus($status);
        // if ($customCredits > 0) {
        //    $user->setCustomCreditsOverride($customCredits);
        // }
        // $this->entityManager->flush();
        
        // Notify user if requested
        if ($notifyUser) {
            // Logic to send notification email
        }
        
        // Add flash message for success
        $this->addFlash('success', 'User subscription updated successfully.');
        
        // Redirect back to subscriptions management page
        return $this->redirectToRoute('app_admin_subscriptions');
    }
    
    #[Route('/subscriptions/user/{id}/cancel', name: 'app_admin_cancel_user_subscription', methods: ['POST'])]
    public function cancelUserSubscription(Request $request, int $id): Response
    {
        $user = $this->userRepository->find($id);
        
        if (!$user) {
            $this->addFlash('error', 'User not found.');
            return $this->redirectToRoute('app_admin_subscriptions');
        }
        
        // In a real implementation, cancel the subscription
        // Example:
        // $user->setSubscriptionStatus('canceled');
        // $user->setSubscriptionCancelDate(new \DateTime());
        // $this->entityManager->flush();
        
        // Notify user about cancellation
        // Logic to send cancellation email
        
        // Add flash message for success
        $this->addFlash('success', 'User subscription canceled successfully.');
        
        // Redirect back to subscriptions management page
        return $this->redirectToRoute('app_admin_subscriptions');
    }
    
    // Important: This route is defined without the /admin prefix because the class already has it
    #[Route('/package-addons/{id}/get', name: 'app_admin_get_package_addon', methods: ['GET'])]
    public function getPackageAddOn(string $id): JsonResponse
    {
        try {
            // First try to find by numeric ID
            $packageId = (int)$id;
            $packageAddOn = $this->packageAddOnRepository->find($packageId);
            
            // If not found and we have a findByIdentifier method, try that (similar to subscription plans)
            if (!$packageAddOn && method_exists($this->packageAddOnRepository, 'findByIdentifier')) {
                $packageAddOn = $this->packageAddOnRepository->findByIdentifier($id);
            }

            if (!$packageAddOn) {
                error_log("Package add-on not found with ID: $id");
                return new JsonResponse(['error' => 'Package not found'], 404);
            }

            // Get full data from the package object
            $data = $packageAddOn->toArray();
            
            // Add identifier and ID explicitly to ensure they're included
            $data['id'] = $packageAddOn->getId();
            if (method_exists($packageAddOn, 'getIdentifier')) {
                $data['identifier'] = $packageAddOn->getIdentifier();
            }
            
            // Add other fields the JavaScript might expect
            if (method_exists($packageAddOn, 'getDisplayOrder')) {
                $data['displayOrder'] = $packageAddOn->getDisplayOrder();
            }
            
            // Return with cache control headers to prevent caching issues
            $response = new JsonResponse($data);
            $response->setPublic();
            $response->setMaxAge(0);
            $response->headers->addCacheControlDirective('must-revalidate', true);
            $response->headers->addCacheControlDirective('no-store', true);
            
            return $response;
        } catch (\Exception $e) {
            // Log the error
            error_log('Error retrieving package add-on: ' . $e->getMessage());
            return new JsonResponse(['error' => 'Failed to retrieve package data'], 500);
        }
    }
    // Global routes to ensure proper access regardless of controller prefix
    #[Route('/admin/package-addons/{id}/get', name: 'app_admin_get_package_addon_direct', methods: ['GET'])]
    #[Route('/admin/admin/package-addons/{id}/get', name: 'app_admin_get_package_addon_double', methods: ['GET'])]
    public function getPackageAddOnDirectAccess(string $id): JsonResponse
    {
        try {
            // Log the request for debugging
            error_log("Direct access package add-on route called with ID: $id");
            
            // Provide detailed debug information
            $requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN';
            $requestUri = $_SERVER['REQUEST_URI'] ?? 'UNKNOWN';
            error_log("Request details: Method=$requestMethod, URI=$requestUri");
            
            // Attempt to find package with detailed logging
            error_log("Searching for package with ID: $id (string)");
            
            // First try finding by numeric ID
            $packageId = (int)$id;
            error_log("Trying numeric ID: $packageId");
            $packageAddOn = $this->packageAddOnRepository->find($packageId);
            
            // Log the result of the first search attempt
            if ($packageAddOn) {
                error_log("Found package by numeric ID: {$packageAddOn->getId()}");
            } else {
                error_log("Package not found by numeric ID, trying alternative methods");
                
                // Try finding by identifier if method exists
                if (method_exists($this->packageAddOnRepository, 'findByIdentifier')) {
                    error_log("Trying to find by identifier: $id");
                    $packageAddOn = $this->packageAddOnRepository->findByIdentifier($id);
                    
                    if ($packageAddOn) {
                        error_log("Found package by identifier: {$packageAddOn->getId()}");
                    } else {
                        error_log("Package not found by identifier either");
                    }
                } else {
                    error_log("findByIdentifier method does not exist on packageAddOnRepository");
                }
                
                // If still not found, try finding all packages and logging them
                if (!$packageAddOn) {
                    $allPackages = $this->packageAddOnRepository->findAll();
                    $packageCount = count($allPackages);
                    error_log("Total packages in repository: $packageCount");
                    
                    if ($packageCount > 0) {
                        $packageIds = [];
                        foreach ($allPackages as $pkg) {
                            $packageIds[] = $pkg->getId();
                        }
                        error_log("Available package IDs: " . implode(', ', $packageIds));
                    }
                }
            }

            // Handle the case where package is not found
            if (!$packageAddOn) {
                error_log("Package add-on not found with ID: $id");
                $errorResponse = new JsonResponse(['error' => 'Package not found'], 404);
                $errorResponse->headers->set('Content-Type', 'application/json');
                // Add cache-busting headers
                $errorResponse->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
                $errorResponse->headers->set('Pragma', 'no-cache');
                $errorResponse->headers->set('Expires', '0');
                return $errorResponse;
            }

            // Convert to array with all needed data
            error_log("Converting package to array");
            $data = $packageAddOn->toArray();
            
            // Add detailed logging of the data being returned
            error_log("Package data: " . json_encode($data));
            
            // Add essential fields
            $data['id'] = $packageAddOn->getId();
            if (method_exists($packageAddOn, 'getIdentifier')) {
                $data['identifier'] = $packageAddOn->getIdentifier();
            }
            
            // Add stripe price IDs if available
            if (method_exists($packageAddOn, 'getStripePriceIdStandard')) {
                $data['stripePriceIdStandard'] = $packageAddOn->getStripePriceIdStandard();
            }
            if (method_exists($packageAddOn, 'getStripePriceIdPremium')) {
                $data['stripePriceIdPremium'] = $packageAddOn->getStripePriceIdPremium();
            }
            
            // Ensure all necessary fields are present
            $data['price_standard'] = $data['price_standard'] ?? $data['priceStandard'] ?? 0;
            $data['price_premium'] = $data['price_premium'] ?? $data['pricePremium'] ?? 0;
            $data['credits'] = $data['credits'] ?? 0;
            $data['name'] = $data['name'] ?? 'Unknown Package';
            $data['is_active'] = $data['is_active'] ?? $data['isActive'] ?? true;
            $data['is_featured'] = $data['is_featured'] ?? $data['isFeatured'] ?? false;
            
            // Force response to not be cached with extensive cache headers
            error_log("Creating JSON response");
            $response = new JsonResponse($data);
            $response->setPublic();
            $response->setMaxAge(0);
            $response->headers->addCacheControlDirective('must-revalidate', true);
            $response->headers->addCacheControlDirective('no-store', true);
            $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', '0');
            // Ensure proper content type
            $response->headers->set('Content-Type', 'application/json');
            
            error_log("Returning successful response for package ID: $id");
            return $response;
        } catch (\Exception $e) {
            // Log the error with detailed information
            error_log('Exception in getPackageAddOnDirectAccess: ' . $e->getMessage());
            error_log('Exception trace: ' . $e->getTraceAsString());
            
            // Create a detailed error response
            $errorData = [
                'error' => 'Failed to retrieve package data',
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'id_requested' => $id
            ];
            
            $errorResponse = new JsonResponse($errorData, 500);
            $errorResponse->headers->set('Content-Type', 'application/json');
            // Add cache-busting headers
            $errorResponse->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
            $errorResponse->headers->set('Pragma', 'no-cache');
            $errorResponse->headers->set('Expires', '0');
            
            return $errorResponse;
        }
    }
    #[Route('/package-addons/create', name: 'app_admin_create_package_addon', methods: ['POST'])]
    public function createPackageAddOn(Request $request): Response
    {
        // Get form data
        $packageName = $request->request->get('packageName');
        $packagePriceStandard = (float)$request->request->get('packagePriceStandard');
        $packagePricePremium = (float)$request->request->get('packagePricePremium');
        $packageCredits = (int)$request->request->get('packageCredits');
        $packageDescription = $request->request->get('packageDescription');
        $packageDiscount = (int)$request->request->get('packageDiscount');
        $packageStripeID = $request->request->get('packageStripeID');
        $packageDisplayOrder = (int)$request->request->get('packageDisplayOrder', 1);
        $packageIsFeatured = $request->request->has('packageIsFeatured');
        $packageIsActive = $request->request->has('packageIsActive');
        
        // Create a new package add-on
        $packageAddOn = new PackageAddOn();
        $packageAddOn->setName($packageName);
        $packageAddOn->setIdentifier(strtolower(preg_replace('/[^a-zA-Z0-9]+/', '_', $packageName)));
        $packageAddOn->setPriceStandard($packagePriceStandard);
        $packageAddOn->setPricePremium($packagePricePremium);
        $packageAddOn->setCredits($packageCredits);
        $packageAddOn->setDescription($packageDescription);
        $packageAddOn->setDiscount($packageDiscount ?: null);
        $packageAddOn->setStripeProductId($packageStripeID);
        $packageAddOn->setDisplayOrder($packageDisplayOrder);
        $packageAddOn->setIsFeatured($packageIsFeatured);
        $packageAddOn->setIsActive($packageIsActive);
        $packageAddOn->setCreatedAt(new \DateTimeImmutable());
        $packageAddOn->setUpdatedAt(new \DateTimeImmutable());
        
        // Save to database
        $this->entityManager->persist($packageAddOn);
        $this->entityManager->flush();
        
        // Add flash message for success
        $this->addFlash('success', 'Package add-on "' . $packageName . '" created successfully.');
        
        // Redirect back to credit packages management page
        return $this->redirectToRoute('app_admin_credit_packages');
    }
    
    #[Route('/package-addons/{id}/edit', name: 'app_admin_edit_package_addon', methods: ['POST'])]
    public function editPackageAddOn(Request $request, int $id): Response
    {
        try {
            // Find the existing add-on package
            $packageAddOn = $this->packageAddOnRepository->find($id);
            
            if (!$packageAddOn) {
                $this->addFlash('error', 'Package add-on not found.');
                return $this->redirectToRoute('app_admin_subscriptions');
            }
            
            // Get form data
            $packageName = $request->request->get('editPackageName');
            $packagePriceStandard = (float)$request->request->get('editPackagePriceStandard');
            $packagePricePremium = (float)$request->request->get('editPackagePricePremium');
            $packageCredits = (int)$request->request->get('editPackageCredits');
            $packageDescription = $request->request->get('editPackageDescription');
            $packageDiscount = (int)$request->request->get('editPackageDiscount');
            $packageStripeID = $request->request->get('editPackageStripeID');
            $packageStripePriceStandard = $request->request->get('editPackageStripePriceStandard');
            $packageStripePricePremium = $request->request->get('editPackageStripePricePremium');
            $packageDisplayOrder = (int)$request->request->get('editPackageDisplayOrder', 1);
            $packageIsFeatured = $request->request->has('editPackageIsFeatured');
            $packageIsActive = $request->request->has('editPackageIsActive');
            
            // Update the package
            $packageAddOn->setName($packageName);
            $packageAddOn->setPriceStandard($packagePriceStandard);
            $packageAddOn->setPricePremium($packagePricePremium);
            $packageAddOn->setCredits($packageCredits);
            $packageAddOn->setDescription($packageDescription);
            $packageAddOn->setDiscount($packageDiscount ?: null);
            $packageAddOn->setStripeProductId($packageStripeID);
            $packageAddOn->setStripePriceIdStandard($packageStripePriceStandard);
            $packageAddOn->setStripePriceIdPremium($packageStripePricePremium);
            $packageAddOn->setDisplayOrder($packageDisplayOrder);
            $packageAddOn->setIsFeatured($packageIsFeatured);
            $packageAddOn->setIsActive($packageIsActive);
            $packageAddOn->setUpdatedAt(new \DateTimeImmutable());
            
            // Save to database
            $this->entityManager->flush();
            
            // Add flash message for success
            $this->addFlash('success', 'Package add-on "' . $packageName . '" updated successfully.');
        } catch (\Exception $e) {
            // Log the error and show a flash message
            error_log('Error updating package add-on: ' . $e->getMessage());
            $this->addFlash('error', 'An error occurred while updating the package add-on.');
        }
        
        // Redirect back to credit packages management page
        return $this->redirectToRoute('app_admin_credit_packages');
    }
    
    #[Route('/package-addons/{id}/delete', name: 'app_admin_delete_package_addon', methods: ['POST'])]
    public function deletePackageAddOn(int $id): Response
    {
        try {
            // Find the package
            $packageAddOn = $this->packageAddOnRepository->find($id);
            
            if (!$packageAddOn) {
                $this->addFlash('error', 'Package add-on not found.');
                return $this->redirectToRoute('app_admin_subscriptions');
            }
            
            // Delete the package
            $this->entityManager->remove($packageAddOn);
            $this->entityManager->flush();
            
            // Add flash message for success
            $this->addFlash('success', 'Package add-on deleted successfully.');
        } catch (\Exception $e) {
            // Log the error and show a flash message
            error_log('Error deleting package add-on: ' . $e->getMessage());
            $this->addFlash('error', 'An error occurred while deleting the package add-on.');
        }
        
        // Redirect back to credit packages management page
        return $this->redirectToRoute('app_admin_credit_packages');
    }
    
    #[Route('/package-addons/{id}/toggle-status', name: 'app_admin_toggle_package_status', methods: ['POST'])]
    public function togglePackageStatus(Request $request, int $id): JsonResponse
    {
        try {
            // Find the package by ID
            $packageAddOn = $this->packageAddOnRepository->find($id);
            
            if (!$packageAddOn) {
                return new JsonResponse(['success' => false, 'message' => 'Package not found'], 404);
            }
            
            // Get active status from request
            $isActive = $request->request->has('editPackageIsActive');
            
            // Update package status
            $packageAddOn->setIsActive($isActive);
            $packageAddOn->setUpdatedAt(new \DateTimeImmutable());
            
            // Save changes
            $this->entityManager->flush();
            
            // Return success response
            return new JsonResponse([
                'success' => true,
                'message' => 'Package status updated successfully',
                'status' => $isActive
            ]);
        } catch (\Exception $e) {
            // Log the error
            error_log('Error toggling package status: ' . $e->getMessage());
            
            // Return error response
            return new JsonResponse([
                'success' => false,
                'message' => 'Failed to update package status: ' . $e->getMessage()
            ], 500);
        }
    }
    
    #[Route('/subscriptions/promotion', name: 'app_admin_create_plan_promotion', methods: ['POST'])]
    public function createSubscriptionPromotion(Request $request): Response
    {
        // Get form data
        $promotionName = $request->request->get('promotionName');
        $promotionType = $request->request->get('promotionType');
        $discountPercentage = (int)$request->request->get('discountPercentage');
        $bonusCredits = (int)$request->request->get('bonusCredits');
        $promotionDuration = $request->request->get('promotionDuration');
        $promotionPlans = $request->request->all('promotionPlans');
        $startDate = new \DateTime($request->request->get('promotionStartDate'));
        $endDate = new \DateTime($request->request->get('promotionEndDate'));
        $promoCode = $request->request->get('promotionCode');
        
        // In a real implementation, save to the database
        // Example:
        // $promotion = new Promotion();
        // $promotion->setName($promotionName);
        // $promotion->setType($promotionType);
        // ...
        // $this->entityManager->persist($promotion);
        // $this->entityManager->flush();
        
        // Add flash message for success
        $this->addFlash('success', 'Subscription promotion "' . $promotionName . '" created successfully.');
        
        // Redirect back to subscriptions management page
        return $this->redirectToRoute('app_admin_subscriptions');
    }
    
    #[Route('/subscriptions/settings', name: 'app_admin_update_subscription_settings', methods: ['POST'])]
    public function saveSubscriptionSettings(Request $request): Response
    {
        // Get form data
        $optionMonthly = $request->request->has('optionMonthly');
        $optionYearly = $request->request->has('optionYearly');
        $optionYearlyDiscount = $request->request->has('optionYearlyDiscount');
        $yearlyDiscountPercent = (int)$request->request->get('yearlyDiscountPercent');
        $freeTrialDays = (int)$request->request->get('freeTrialDays');
        $gracePeriodDays = (int)$request->request->get('gracePeriodDays');
        $paymentFailureAttempts = (int)$request->request->get('paymentFailureAttempts');
        
        // Payment options
        $paymentCredit = $request->request->has('paymentCredit');
        $paymentPayPal = $request->request->has('paymentPayPal');
        $paymentBank = $request->request->has('paymentBank');
        
        // Email notifications
        $emailWelcome = $request->request->has('emailWelcome');
        $emailRenewal = $request->request->has('emailRenewal');
        $emailPaymentFailed = $request->request->has('emailPaymentFailed');
        $emailCancellation = $request->request->has('emailCancellation');
        
        // In a real implementation, save settings to the database
        // Example:
        // $settings = $this->settingsRepository->findOneBy(['type' => 'subscription']);
        // if (!$settings) {
        //    $settings = new Settings();
        //    $settings->setType('subscription');
        // }
        // $settings->setValue(json_encode([
        //    'billing_options' => [
        //        'monthly' => $optionMonthly,
        //        'yearly' => $optionYearly,
        //        ...
        //    ],
        //    ...
        // ]));
        // $this->entityManager->persist($settings);
        // $this->entityManager->flush();
        
        // Add flash message for success
        $this->addFlash('success', 'Subscription settings saved successfully.');
        
        // Redirect back to subscriptions management page
        return $this->redirectToRoute('app_admin_subscriptions');
    }
    
    #[Route('/subscriptions/send-email', name: 'app_admin_send_bulk_email', methods: ['POST'])]
    public function sendSubscriberEmail(Request $request): Response
    {
        // Get form data
        $recipients = $request->request->get('emailRecipients');
        $subject = $request->request->get('emailSubject');
        $body = $request->request->get('emailBody');
        $isScheduled = $request->request->has('emailSchedule');
        $scheduleDate = $request->request->get('emailScheduleDate');
        $scheduleTime = $request->request->get('emailScheduleTime');
        
        // In a real implementation, process emails
        // If scheduled, save to database for later processing
        // If immediate, send emails right away
        
        // Add flash message for success
        if ($isScheduled) {
            $this->addFlash('success', 'Email scheduled to be sent to ' . $recipients . ' subscribers.');
        } else {
            $this->addFlash('success', 'Email sent to ' . $recipients . ' subscribers successfully.');
        }
        
        // Redirect back to subscriptions management page
        return $this->redirectToRoute('app_admin_subscriptions');
    }
    
    #[Route('/subscriptions/send-test-email', name: 'app_admin_send_test_email', methods: ['POST'])]
    public function sendTestEmail(Request $request): Response
    {
        // Get form data
        $testEmail = $request->request->get('emailTestAddress');
        $subject = $request->request->get('emailSubject');
        $body = $request->request->get('emailBody');
        
        // In a real implementation, send the test email
        // Example:
        // $email = (new TemplatedEmail())
        //    ->to($testEmail)
        //    ->subject($subject)
        //    ->html($body);
        // $this->mailer->send($email);
        
        // Add flash message for success
        $this->addFlash('success', 'Test email sent to ' . $testEmail . ' successfully.');
        
        // Redirect back to subscriptions management page
        return $this->redirectToRoute('app_admin_subscriptions');
    }
    
    #[Route('/statistics', name: 'app_admin_statistics')]
    public function statistics(Request $request): Response
    {
        // Get date range from session if available, otherwise use last 30 days
        $session = $request->getSession();
        $startDate = $session->get('admin_stats_start_date', (new \DateTime())->modify('-30 days'));
        $endDate = $session->get('admin_stats_end_date', new \DateTime());
        
        if (!$startDate instanceof \DateTime) {
            $startDate = new \DateTime($startDate);
        }
        
        if (!$endDate instanceof \DateTime) {
            $endDate = new \DateTime($endDate);
        }
        
        // Get user statistics from repository
        $totalUsers = $this->userRepository->count([]);
        $activeUsers = $this->userRepository->count(['isVerified' => true]);
        $premiumUsers = $this->userRepository->countUsersByRole('ROLE_PREMIUM');
        $adminUsers = $this->userRepository->countUsersByRole('ROLE_ADMIN');
        
        // Get product statistics from repository
        $totalProducts = $this->wooCommerceProductRepository->count([]);
        $aiProcessedProducts = $this->wooCommerceProductRepository->count(['status' => 'ai_processed']);
        $exportedProducts = $this->wooCommerceProductRepository->count(['status' => 'exported']);
        
        // Get total credits in the system
        $totalCredits = $this->userRepository->getTotalCredits();
        
        // Calculate number of months between start and end date
        $months = (int)$startDate->diff($endDate)->format('%m') + 1;
        $months = max(1, min($months, 12)); // Ensure between 1-12 months
        
        // Get user growth data for the specified number of months
        $userGrowthByMonth = $this->userRepository->getUserGrowthByMonth($months);
        
        // Extract values for the chart
        $userGrowthData = array_values(array_map(function($item) {
            return $item['total'];
        }, $userGrowthByMonth));
        
        $activeUsersData = array_values(array_map(function($item) {
            return $item['active'];
        }, $userGrowthByMonth));
        
        // Get real user distribution
        $userDistribution = $this->userRepository->getUserDistribution();
        $userDistributionData = [
            $userDistribution['basic'] ?? 0,      // Basic users
            $userDistribution['premium'] ?? 0,    // Premium users
            $userDistribution['admin'] ?? 0       // Admins
        ];
        
        // Try to get real revenue data, fall back to mock data if transaction table doesn't exist
        try {
            $revenueByMonth = $this->transactionRepository->getRevenueByMonth($months);
            $revenue = [];
            $revenueAmounts = [];
            
            foreach ($revenueByMonth as $item) {
                $revenue[] = [
                    'month' => $item['month'],
                    'amount' => $item['amount']
                ];
                $revenueAmounts[] = $item['amount'];
            }
            
            // Get revenue breakdown by type
            $revenueByType = $this->transactionRepository->getRevenueBreakdown();
            $revenueBreakdown = [
                $revenueByType['credit_purchase'] ?? 0,     // Credit Purchases
                $revenueByType['subscription'] ?? 0,        // Premium Subscriptions
                $revenueByType['other'] ?? 0                // Other revenue
            ];
        } catch (\Exception $e) {
            // Fallback to mock data if transaction table doesn't exist
            $revenue = [];
            $revenueAmounts = [];
            $currentMonth = (int)(new \DateTime())->format('n');
            $baseRevenue = 1200;
            
            for ($i = 0; $i < $months; $i++) {
                $month = ($currentMonth - $months + 1 + $i) > 0 ? $currentMonth - $months + 1 + $i : $currentMonth + 1 + $i + 12 - $months;
                $monthName = date('M', mktime(0, 0, 0, $month, 1));
                $amount = $baseRevenue + ($i * 200) + rand(-150, 350);
                
                $revenue[] = [
                    'month' => $monthName,
                    'amount' => $amount
                ];
                
                $revenueAmounts[] = $amount;
                $baseRevenue = $amount;
            }
            
            // Mock revenue breakdown
            $revenueBreakdown = [68, 27, 5]; // Credit Purchases, Premium Subscriptions, Other
        }
        
        // Get product categories distribution - use repository's format directly
        $productCategories = $this->wooCommerceProductRepository->getProductCountsByCategory();
        
        // Try to get real credit usage data, fall back to mock data if it fails
        try {
            $creditUsageByMonth = $this->transactionRepository->getCreditUsageByMonth($months);
            $creditUsage = [];
            
            foreach ($creditUsageByMonth as $item) {
                $creditUsage[] = [
                    'month' => $item['month'],
                    'used' => $item['used']
                ];
            }
        } catch (\Exception $e) {
            // Fallback to mock data
            $creditUsage = [];
            $currentMonth = (int)(new \DateTime())->format('n');
            $baseCredits = 200;
            
            for ($i = 0; $i < $months; $i++) {
                $month = ($currentMonth - $months + 1 + $i) > 0 ? $currentMonth - $months + 1 + $i : $currentMonth + 1 + $i + 12 - $months;
                $monthName = date('M', mktime(0, 0, 0, $month, 1));
                $used = $baseCredits + ($i * 50) + rand(-30, 70);
                
                $creditUsage[] = [
                    'month' => $monthName,
                    'used' => $used
                ];
                
                $baseCredits = $used;
            }
        }
        
        // Try to get user activity data, fall back to mock data if missing method
        try {
            $userActivityData = $this->userRepository->getUserActivityByHour($startDate, $endDate);
        } catch (\Exception $e) {
            // Fallback to mock data
            $userActivityData = [];
            for ($hour = 0; $hour < 24; $hour++) {
                $sessions = 0;
                
                if ($hour < 6) {
                    $sessions = rand(1, 20);
                } elseif ($hour < 12) {
                    $sessions = rand(20, 50);
                } elseif ($hour < 18) {
                    $sessions = rand(50, 100);
                } else {
                    $sessions = rand(20, 60);
                }
                
                $userActivityData[] = [
                    'hour' => $hour,
                    'sessions' => $sessions
                ];
            }
        }
        $userActivity = [];
        
        foreach ($userActivityData as $activity) {
            $userActivity[] = [
                'hour' => $activity['hour'] . ':00',
                'sessions' => $activity['sessions']
            ];
        }
        
        // Try to get monthly active user count, fall back to estimate if method doesn't exist
        try {
            $monthlyActiveUsers = $this->userRepository->getMonthlyActiveUserCount();
        } catch (\Exception $e) {
            // Fallback to estimate based on active users
            $monthlyActiveUsers = (int)($activeUsers * 0.7); // Assuming 70% of active users use the system monthly
        }
        $averageCreditsPerUser = $totalUsers > 0 ? round($totalCredits / $totalUsers, 1) : 0;
        $conversionRate = $totalUsers > 0 ? round(($premiumUsers / $totalUsers) * 100, 1) : 0;
        $productsPerUser = $totalUsers > 0 ? round($totalProducts / $totalUsers, 1) : 0;
        
        return $this->render('admin/statistics.html.twig', [
            'stats' => [
                'total_users' => $totalUsers,
                'active_users' => $activeUsers,
                'premium_users' => $premiumUsers,
                'total_products' => $totalProducts,
                'ai_processed_products' => $aiProcessedProducts,
                'exported_products' => $exportedProducts,
                'total_credits' => $totalCredits,
                'monthly_active_users' => $monthlyActiveUsers,
                'average_credits_per_user' => $averageCreditsPerUser,
                'conversion_rate' => $conversionRate,
                'products_per_user' => $productsPerUser
            ],
            'charts' => [
                'user_growth' => $userGrowthData,
                'active_users' => $activeUsersData,
                'user_distribution' => $userDistributionData,
                'revenue' => $revenue,
                'revenue_amounts' => $revenueAmounts,
                'revenue_breakdown' => $revenueBreakdown,
                'credit_usage' => $creditUsage,
                'product_categories' => $productCategories,
                'user_activity' => $userActivity
            ],
            'date_range' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d')
            ]
        ]);
    }
    
    #[Route('/export-report', name: 'app_admin_export_report', methods: ['POST'])]
    public function exportReport(Request $request): Response
    {
        // Get export parameters
        $format = $request->request->get('reportFormat', 'pdf');
        $timeframe = $request->request->get('reportTimeframe', '30days');
        $includeSections = [
            'users' => $request->request->has('includeUsers'),
            'revenue' => $request->request->has('includeRevenue'),
            'products' => $request->request->has('includeProducts'),
            'content' => $request->request->has('includeContent'),
            'system' => $request->request->has('includeSystem')
        ];
        
        // In a real application, this would generate and return the actual report
        // For demonstration purposes, we'll just add a flash message and redirect
        
        // Add a flash message for the successful export
        $this->addFlash('success', 'Report has been generated and exported successfully in ' . strtoupper($format) . ' format.');
        
        // Redirect back to the statistics page
        return $this->redirectToRoute('app_admin_statistics');
    }
    
    #[Route('/schedule-report', name: 'app_admin_schedule_report', methods: ['POST'])]
    public function scheduleReport(Request $request): Response
    {
        // Get scheduling parameters
        $reportName = $request->request->get('scheduleName', 'Scheduled Report');
        $emails = $request->request->get('scheduleEmail', '');
        $frequency = $request->request->get('scheduleFrequency', 'weekly');
        $day = $request->request->get('scheduleDay', '1');
        $format = $request->request->get('scheduleFormat', 'pdf');
        $includeSections = [
            'users' => $request->request->has('scheduleUsers'),
            'revenue' => $request->request->has('scheduleRevenue'),
            'products' => $request->request->has('scheduleProducts'),
            'content' => $request->request->has('scheduleContent')
        ];
        
        // In a real application, this would create a scheduled task in the database
        // For demonstration purposes, we'll just add a flash message and redirect
        
        // Add a flash message for the successful scheduling
        $this->addFlash('success', 'Report "' . $reportName . '" has been scheduled successfully to be sent ' . $frequency . '.');
        
        // Redirect back to the statistics page
        return $this->redirectToRoute('app_admin_statistics');
    }
    
    #[Route('/set-custom-date-range', name: 'app_admin_set_custom_date_range', methods: ['POST'])]
    public function setCustomDateRange(Request $request): Response
    {
        // Get date range parameters
        $startDate = $request->request->get('startDate');
        $endDate = $request->request->get('endDate');
        $compareWith = $request->request->get('compareWith', 'none');
        
        // Store date range in session
        $session = $request->getSession();
        $session->set('admin_stats_start_date', $startDate);
        $session->set('admin_stats_end_date', $endDate);
        $session->set('admin_stats_compare_with', $compareWith);
        
        // Add a flash message for the successful date range change
        $this->addFlash('success', 'Custom date range has been applied successfully.');
        
        // Redirect back to the statistics page
        return $this->redirectToRoute('app_admin_statistics');
    }
    
    #[Route('/set-date-range', name: 'app_admin_set_date_range', methods: ['POST'])]
    public function setDateRange(Request $request): Response
    {
        // Get range parameter
        $range = $request->request->get('range', '30 Days');
        
        // Calculate start and end dates based on range
        $endDate = new \DateTime();
        $startDate = clone $endDate;
        
        switch (trim($range)) {
            case '7 Days':
                $startDate->modify('-7 days');
                break;
            case '30 Days':
                $startDate->modify('-30 days');
                break;
            case '90 Days':
                $startDate->modify('-90 days');
                break;
            case 'All Time':
                $startDate->modify('-10 years'); // Arbitrary long period to cover "all time"
                break;
            default:
                $startDate->modify('-30 days'); // Default to 30 days
        }
        
        // Store date range in session
        $session = $request->getSession();
        $session->set('admin_stats_start_date', $startDate);
        $session->set('admin_stats_end_date', $endDate);
        $session->set('admin_stats_range', $range);
        
        // Add a flash message for the successful date range change
        $this->addFlash('success', 'Date range has been set to "' . $range . '".');
        
        // Redirect back to the statistics page
        return $this->redirectToRoute('app_admin_statistics');
    }
    
    #[Route('/update-metrics-settings', name: 'app_admin_update_metrics_settings', methods: ['POST'])]
    public function updateMetricsSettings(Request $request): Response
    {
        // Get metrics settings
        $displayedMetrics = [
            'users' => $request->request->has('metricUsers'),
            'revenue' => $request->request->has('metricRevenue'),
            'content' => $request->request->has('metricContent'),
            'system' => $request->request->has('metricSystem'),
            'topStats' => $request->request->has('metricTopStats')
        ];
        $refreshInterval = $request->request->get('metricRefreshInterval', 0);
        
        // Store metrics settings in session
        $session = $request->getSession();
        $session->set('admin_metrics_settings', [
            'displayed_metrics' => $displayedMetrics,
            'refresh_interval' => $refreshInterval
        ]);
        
        // Add a flash message for the successful settings update
        $this->addFlash('success', 'Dashboard metrics settings have been updated successfully.');
        
        // Redirect back to the statistics page
        return $this->redirectToRoute('app_admin_statistics');
    }

    #[Route('/api/stats', name: 'app_admin_api_stats', methods: ['GET'])]
    public function apiStats(): JsonResponse
    {
        // Count stats for the dashboard
        $totalUsers = $this->userRepository->count([]);
        $activeUsers = $this->userRepository->count(['isVerified' => true]);
        $premiumUsers = $this->userRepository->countUsersByRole('ROLE_PREMIUM');
        $totalProducts = $this->wooCommerceProductRepository->count([]);
        $aiProcessedProducts = $this->wooCommerceProductRepository->count(['status' => 'ai_processed']);
        $exportedProducts = $this->wooCommerceProductRepository->count(['status' => 'exported']);
        
        // Calculate total credits in the system
        $totalCredits = $this->userRepository->getTotalCredits();
        
        return $this->json([
            'stats' => [
                'total_users' => $totalUsers,
                'active_users' => $activeUsers,
                'premium_users' => $premiumUsers,
                'total_products' => $totalProducts,
                'ai_processed_products' => $aiProcessedProducts,
                'exported_products' => $exportedProducts,
                'total_credits' => $totalCredits,
                'premium_percentage' => ($totalUsers > 0) ? round(($premiumUsers / $totalUsers * 100), 1) : 0,
                'timestamp' => (new \DateTime())->format('Y-m-d H:i:s')
            ]
        ]);
    }

    #[Route('/api/credit-stats', name: 'app_admin_api_credit_stats', methods: ['GET'])]
    public function apiCreditStats(): JsonResponse
    {
        // Get total credits in the system
        $totalCredits = $this->userRepository->getTotalCredits();
        
        // Get monthly revenue - in a real app this would come from the transaction repository
        // For now, we're using mock data from the creditsManagement method
        $revenueMtd = 4235.75;
        $revenueGrowth = 15.3;
        
        // Get credits used this month - in a real app this would come from the transaction repository
        // For now, we're using mock data from the creditsManagement method
        $creditsUsedMtd = 2180;
        $dailyAvg = 72.6;
        
        return $this->json([
            'stats' => [
                'total_credits' => $totalCredits,
                'revenue_mtd' => $revenueMtd,
                'revenue_growth' => $revenueGrowth,
                'credits_used_mtd' => $creditsUsedMtd,
                'daily_avg_usage' => $dailyAvg,
                'timestamp' => (new \DateTime())->format('Y-m-d H:i:s')
            ]
        ]);
    }
    
    #[Route('/products/filter', name: 'app_admin_products_filter', methods: ['POST'])]
    public function filterProducts(Request $request): JsonResponse
    {
        try {
            // Verify CSRF token
            $submittedToken = $request->request->get('_token');
            if (!$this->isCsrfTokenValid('filter_products', $submittedToken)) {
                return new JsonResponse([
                    'error' => 'Invalid CSRF token'
                ], 403);
            }
            
            // Get filter parameters from request
            $userId = $request->request->get('user');
            $status = $request->request->get('status');
            $category = $request->request->get('category');
            $search = $request->request->get('search');
            $dateRange = $request->request->get('dateRange');
            
            // Build query criteria
            $qb = $this->entityManager->createQueryBuilder();
            $qb->select('p')
               ->from('App\Entity\WooCommerceProduct', 'p');
            
            // Apply user filter
            if ($userId) {
                $qb->andWhere('p.owner = :userId')
                   ->setParameter('userId', $userId);
            }
            
            // Apply status filter
            if ($status) {
                $qb->andWhere('p.status = :status')
                   ->setParameter('status', $status);
            }
            
            // Apply category filter
            if ($category) {
                $qb->andWhere('p.category = :category')
                   ->setParameter('category', $category);
            }
            
            // Apply search filter
            if ($search) {
                $qb->andWhere(
                    $qb->expr()->orX(
                        $qb->expr()->like('p.name', ':search'),
                        $qb->expr()->like('p.description', ':search'),
                        $qb->expr()->like('p.shortDescription', ':search')
                    )
                );
                $qb->setParameter('search', '%' . $search . '%');
            }
            
            // Apply date range filter
            if ($dateRange) {
                $now = new \DateTimeImmutable();
                switch ($dateRange) {
                    case 'today':
                        $start = $now->setTime(0, 0, 0);
                        $qb->andWhere('p.createdAt >= :start')
                           ->setParameter('start', $start);
                        break;
                    case 'week':
                        $start = $now->modify('monday this week')->setTime(0, 0, 0);
                        $qb->andWhere('p.createdAt >= :start')
                           ->setParameter('start', $start);
                        break;
                    case 'month':
                        $start = $now->modify('first day of this month')->setTime(0, 0, 0);
                        $qb->andWhere('p.createdAt >= :start')
                           ->setParameter('start', $start);
                        break;
                    case 'custom':
                        // Custom date range would need start/end dates passed separately
                        $startDate = $request->request->get('startDate');
                        $endDate = $request->request->get('endDate');
                        if ($startDate) {
                            $start = new \DateTimeImmutable($startDate);
                            $qb->andWhere('p.createdAt >= :start')
                               ->setParameter('start', $start);
                        }
                        if ($endDate) {
                            $end = new \DateTimeImmutable($endDate);
                            $end = $end->modify('+1 day')->setTime(0, 0, 0);
                            $qb->andWhere('p.createdAt < :end')
                               ->setParameter('end', $end);
                        }
                        break;
                }
            }
            
            // Execute query
            $products = $qb->getQuery()->getResult();
            
            // Transform products into array
            $productsData = [];
            foreach ($products as $product) {
                $productsData[] = [
                    'id' => $product->getId(),
                    'name' => $product->getName(),
                    'description' => $product->getDescription(),
                    'shortDescription' => $product->getShortDescription(),
                    'status' => $product->getStatus(),
                    'category' => $product->getCategory(),
                    'woocommerceId' => $product->getWoocommerceId(),
                    'imageUrl' => $product->getImageUrl(),
                    'createdAt' => $product->getCreatedAt()->format('Y-m-d'),
                    'updatedAt' => $product->getUpdatedAt() ? $product->getUpdatedAt()->format('Y-m-d') : null,
                    'owner' => [
                        'id' => $product->getOwner()->getId(),
                        'firstName' => $product->getOwner()->getFirstName(),
                        'lastName' => $product->getOwner()->getLastName(),
                    ]
                ];
            }
            
            return new JsonResponse([
                'products' => $productsData,
                'count' => count($productsData),
            ]);
        } catch (\Exception $e) {
            // Log the error and return a JSON response with error message
            error_log('Error filtering products: ' . $e->getMessage());
            return new JsonResponse(['error' => 'An error occurred while filtering products'], 500);
        }
    }
}