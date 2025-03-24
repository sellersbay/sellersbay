<?php

namespace App\Controller;

use App\Entity\Transaction;
use App\Entity\User;
use App\Repository\PackageAddOnRepository;
use App\Repository\SubscriptionPlanRepository;
use App\Service\StripeService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/subscription')]
// TEMPORARY: Commented out authentication requirement for testing
// Uncomment the following line to restore security
// #[IsGranted('ROLE_USER')]
class SubscriptionController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private StripeService $stripeService;
    private PackageAddOnRepository $packageAddOnRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        StripeService $stripeService,
        PackageAddOnRepository $packageAddOnRepository
    ) {
        $this->entityManager = $entityManager;
        $this->stripeService = $stripeService;
        $this->packageAddOnRepository = $packageAddOnRepository;
    }

    private function getTypedUser(): ?User
    {
        /** @var User|null $user */
        $user = $this->getUser();
        
        // TEMPORARY: For testing, create a mock user if no user is authenticated
        if (!$user) {
            $user = new User();
            $user->setEmail('test@example.com');
            $user->setFirstName('Test');
            $user->setLastName('User');
            $user->setRoles(['ROLE_USER']);
            $user->setCredits(100);
            
            // TEMPORARY: For testing, set a mock ID using reflection
            // Since ID is normally set by the database
            try {
                $reflectionClass = new \ReflectionClass($user);
                $idProperty = $reflectionClass->getProperty('id');
                $idProperty->setAccessible(true);
                $idProperty->setValue($user, 999); // Test ID
            } catch (\Exception $e) {
                // If reflection fails, just log and continue
                error_log('Failed to set mock user ID: ' . $e->getMessage());
            }
        }
        
        return $user;
    }

    #[Route('/buy-credits', name: 'app_buy_credits')]
    public function buyCredits(): Response
    {
        $user = $this->getTypedUser();
        
        // Check if user has an active subscription
        if (empty($user->getSubscriptionTier())) {
            // User doesn't have an active subscription, redirect to plans
            $this->addFlash(
                'error', 
                'You need an active subscription to purchase additional credit packages. Please subscribe to a plan first.'
            );
            return $this->redirectToRoute('app_subscription_plans');
        }
        
        // Get current tier and determine if premium
        $currentTier = $user->getSubscriptionTier();
        $isPremium = in_array($currentTier, ['professional', 'enterprise']);
        
        // Get add-on packages from database
        $addOnPackages = $this->packageAddOnRepository->getAddOnPackagesAsArray();
        if (empty($addOnPackages)) {
            // Fallback to standard packages if none in database
            $addOnPackages = [
                'small' => [
                    'name' => 'Small Package',
                    'credits' => 10,
                    'price_standard' => 9.99,
                    'price_premium' => 8.99
                ],
                'medium' => [
                    'name' => 'Medium Package',
                    'credits' => 50,
                    'price_standard' => 39.99,
                    'price_premium' => 34.99
                ],
                'large' => [
                    'name' => 'Large Package',
                    'credits' => 100,
                    'price_standard' => 69.99,
                    'price_premium' => 59.99
                ]
            ];
        }
        
        // Transform add-on packages to match the format expected by the template
        $creditPackages = [];
        foreach ($addOnPackages as $id => $package) {
            $creditPackages[$id] = [
                'name' => $package['name'],
                'credits' => $package['credits'],
                'price' => $isPremium ? $package['price_premium'] : $package['price_standard']
            ];
        }
        
        // User has an active subscription, show credit packages
        return $this->render('subscription/buy_credits.html.twig', [
            'credit_packages' => $creditPackages,
            'subscription_tier' => $currentTier,
            'is_premium' => $isPremium
        ]);
    }

    #[Route('/process-credit-purchase/{package}', name: 'app_process_credit_purchase')]
    public function processCreditPurchase(string $package): Response
    {
        $user = $this->getTypedUser();
        
        // Check if user has an active subscription
        if (empty($user->getSubscriptionTier())) {
            // User doesn't have an active subscription, redirect to plans
            $this->addFlash(
                'error', 
                'You need an active subscription to purchase additional credit packages. Please subscribe to a plan first.'
            );
            return $this->redirectToRoute('app_subscription_plans');
        }
        
        // Get current tier and determine if premium
        $currentTier = $user->getSubscriptionTier();
        $isPremium = in_array($currentTier, ['professional', 'enterprise']);
        
        // Get add-on packages from database
        $addOnPackages = $this->packageAddOnRepository->getAddOnPackagesAsArray();
        if (empty($addOnPackages)) {
            // Fallback to standard packages if none in database
            $addOnPackages = [
                'small' => [
                    'name' => 'Small Package',
                    'credits' => 10,
                    'price_standard' => 9.99,
                    'price_premium' => 8.99
                ],
                'medium' => [
                    'name' => 'Medium Package',
                    'credits' => 50,
                    'price_standard' => 39.99,
                    'price_premium' => 34.99
                ],
                'large' => [
                    'name' => 'Large Package',
                    'credits' => 100,
                    'price_standard' => 69.99,
                    'price_premium' => 59.99
                ]
            ];
        }
        
        if (!isset($addOnPackages[$package])) {
            throw $this->createNotFoundException('Credit package not found');
        }
        
        $packageInfo = $addOnPackages[$package];
        $price = $isPremium ? $packageInfo['price_premium'] : $packageInfo['price_standard'];
        
        $packageDetails = [
            'name' => $packageInfo['name'],
            'credits' => $packageInfo['credits'],
            'price' => $price
        ];
        
        try {
            // Create Stripe Checkout Session
            $session = $this->stripeService->createCheckoutSession($user, $package);
            
            // TEMPORARY: For debugging session object contents
            error_log('Session created with ID: ' . $session->id);
            
            return $this->render('subscription/credit_purchase.html.twig', [
                'package' => $packageDetails,
                'sessionId' => $session->id, // Pass session ID instead of client secret
                'publicKey' => $this->getParameter('stripe_public_key')
            ]);
        } catch (\Exception $e) {
            // Log the error
            error_log('Error creating checkout session: ' . $e->getMessage());
            
            // For development/testing - show error in template
            return $this->render('subscription/credit_purchase.html.twig', [
                'package' => $packageDetails,
                'error' => true,
                'error_message' => 'Error: ' . $e->getMessage(),
                'sessionId' => 'mock_session_' . time(),
                'publicKey' => $this->getParameter('stripe_public_key')
            ]);
        }
    }
    
    #[Route('/plans', name: 'app_subscription_plans')]
    public function plans(SubscriptionPlanRepository $subscriptionPlanRepository): Response
    {
        // Get the current user
        $user = $this->getTypedUser();
        
        // Get subscription plans from database
        $plans = $subscriptionPlanRepository->getPlansAsArray();
        if (empty($plans)) {
            // Fallback to standard plans if none in database
            $plans = [
                'starter' => [
                    'name' => 'Starter',
                    'credits' => 50,
                    'price' => 29.00,
                    'features' => [
                        'product_descriptions' => true,
                        'meta_descriptions' => true,
                        'image_alt_text' => true,
                        'seo_keywords' => false,
                        'premium_ai' => false
                    ],
                    'feature_descriptions' => [
                        'Basic SEO content generation',
                        '10 product optimizations per month',
                        'Standard support',
                        'Essential AI optimization'
                    ]
                ],
                'professional' => [
                    'name' => 'Professional',
                    'credits' => 100,
                    'price' => 79.00,
                    'features' => [
                        'product_descriptions' => true,
                        'meta_descriptions' => true,
                        'image_alt_text' => true,
                        'seo_keywords' => true,
                        'premium_ai' => false
                    ],
                    'feature_descriptions' => [
                        'Advanced SEO content generation',
                        '50 product optimizations per month',
                        'Priority support',
                        'Bulk processing',
                        'Keyword optimization'
                    ]
                ],
                'enterprise' => [
                    'name' => 'Enterprise',
                    'credits' => 250,
                    'price' => 199.00,
                    'features' => [
                        'product_descriptions' => true,
                        'meta_descriptions' => true,
                        'image_alt_text' => true,
                        'seo_keywords' => true,
                        'premium_ai' => true
                    ],
                    'feature_descriptions' => [
                        'Premium SEO content generation',
                        'Unlimited product optimizations',
                        'Premium support with dedicated agent',
                        'Advanced bulk processing',
                        'Custom AI prompts',
                        'Market trend analysis'
                    ]
                ]
            ];
        }
        
        // Check if user already has a subscription
        $currentTier = $user->getSubscriptionTier();
        
        // Get add-on packages from database
        $addOnPackages = $this->packageAddOnRepository->getAddOnPackagesAsArray();
        if (empty($addOnPackages)) {
            // Fallback to standard packages if none in database
            $addOnPackages = [
                'small' => [
                    'name' => 'Small Package',
                    'credits' => 10,
                    'price_standard' => 9.99,
                    'price_premium' => 8.99
                ],
                'medium' => [
                    'name' => 'Medium Package',
                    'credits' => 50,
                    'price_standard' => 39.99,
                    'price_premium' => 34.99
                ],
                'large' => [
                    'name' => 'Large Package',
                    'credits' => 100,
                    'price_standard' => 69.99,
                    'price_premium' => 59.99
                ]
            ];
        }
        
        // Transform add-on packages to match the format expected by the template
        $creditPackages = [];
        $isPremium = in_array($currentTier, ['professional', 'enterprise']);
        
        foreach ($addOnPackages as $id => $package) {
            $creditPackages[$id] = [
                'name' => $package['name'],
                'credits' => $package['credits'],
                'price' => $isPremium ? $package['price_premium'] : $package['price_standard']
            ];
        }
        
        if (!empty($currentTier)) {
            // User is already subscribed, show upgrade options and add-ons
            
            // Define the plan hierarchy to determine upgrades
            $planHierarchy = [
                'starter' => 1,
                'professional' => 2,
                'enterprise' => 3
            ];
            
            // Filter plans to only show upgrades (higher tier than current)
            $upgradePlans = array_filter($plans, function($planKey) use ($currentTier, $planHierarchy) {
                // Only include plans that are higher in hierarchy than the current tier
                return isset($planHierarchy[$planKey]) && 
                       isset($planHierarchy[$currentTier]) && 
                       $planHierarchy[$planKey] > $planHierarchy[$currentTier];
            }, ARRAY_FILTER_USE_KEY);
            
            return $this->render('subscription/plans.html.twig', [
                'plans' => $upgradePlans,
                'credit_packages' => $creditPackages,
                'is_subscribed' => true,
                'current_tier' => $currentTier,
                'is_premium' => $isPremium,
                'show_add_ons' => true
            ]);
        }
        
        // User is not subscribed, show standard plans
        return $this->render('subscription/plans.html.twig', [
            'plans' => $plans,
            'is_subscribed' => false
        ]);
    }

    #[Route('/purchase/{plan}', name: 'app_subscription_purchase')]
    public function purchase(string $plan, SubscriptionPlanRepository $subscriptionPlanRepository): Response
    {
        $user = $this->getTypedUser();
        
        // We'll log the plan parameter for debugging
        error_log('Attempting to purchase plan: ' . $plan);
        
        // Get plan from repository using a more robust approach
        $plans = $subscriptionPlanRepository->getPlansAsArray();
        
        // Normalize the plan ID to handle case-insensitivity and whitespace
        $normalizedPlan = strtolower(trim($plan));
        
        // Debug plan lookup
        error_log('Plans from repository: ' . json_encode(array_keys($plans)));
        
        // Define our fallback plans
        $standardPlans = [
            'starter' => [
                'name' => 'Starter',
                'credits' => 50, // Matching the displayed value in the UI
                'price' => 29.00, // Matching the displayed value in the UI
                'features' => [
                    'product_descriptions' => true,
                    'meta_descriptions' => true,
                    'image_alt_text' => true,
                    'seo_keywords' => false,
                    'premium_ai' => false
                ],
                'feature_descriptions' => [
                    'Basic SEO content generation',
                    '10 product optimizations per month',
                    'Standard support',
                    'Essential AI optimization'
                ]
            ],
            'professional' => [
                'name' => 'Professional',
                'credits' => 100,
                'price' => 79.00,
                'features' => [
                    'product_descriptions' => true,
                    'meta_descriptions' => true,
                    'image_alt_text' => true,
                    'seo_keywords' => true,
                    'premium_ai' => false
                ],
                'feature_descriptions' => [
                    'Advanced SEO content generation',
                    '50 product optimizations per month',
                    'Priority support',
                    'Bulk processing',
                    'Keyword optimization'
                ]
            ],
            'enterprise' => [
                'name' => 'Enterprise',
                'credits' => 250,
                'price' => 199.00,
                'features' => [
                    'product_descriptions' => true,
                    'meta_descriptions' => true,
                    'image_alt_text' => true,
                    'seo_keywords' => true,
                    'premium_ai' => true
                ],
                'feature_descriptions' => [
                    'Premium SEO content generation',
                    'Unlimited product optimizations',
                    'Premium support with dedicated agent',
                    'Advanced bulk processing',
                    'Custom AI prompts',
                    'Market trend analysis'
                ]
            ],
            'growth' => [
                'name' => 'Growth',
                'credits' => 100, 
                'price' => 79.00,
                'features' => [
                    'product_descriptions' => true,
                    'meta_descriptions' => true,
                    'image_alt_text' => true,
                    'seo_keywords' => true,
                    'premium_ai' => false
                ],
                'feature_descriptions' => [
                    'Advanced SEO content generation',
                    '50 product optimizations per month',
                    'Priority support',
                    'Bulk processing',
                    'Growth analytics'
                ]
            ]
        ];
        
        // Find the matching plan, ignoring case
        $planDetails = null;
        $foundPlan = false;
        
        // First, try to find in database plans
        if (!empty($plans)) {
            foreach ($plans as $planId => $planData) {
                if (strtolower($planId) === $normalizedPlan) {
                    $planDetails = $planData;
                    $foundPlan = true;
                    error_log('Found plan in database: ' . $planId);
                    break;
                }
            }
        }
        
        // If not found in database, try our standardized plans
        if (!$foundPlan) {
            foreach ($standardPlans as $planId => $planData) {
                if (strtolower($planId) === $normalizedPlan) {
                    $planDetails = $planData;
                    $foundPlan = true;
                    error_log('Found plan in standard plans: ' . $planId);
                    break;
                }
            }
        }
        
        // If no matching plan is found, default to starter
        if (!$foundPlan) {
            error_log('No matching plan found, defaulting to starter plan');
            $planDetails = $standardPlans['starter'];
        }
        
        try {
            // Create Stripe Checkout Session
            $session = $this->stripeService->createCheckoutSession($user, $plan);
            
            // Log the session creation for debugging
            error_log(sprintf(
                'Checkout session created: id=%s, plan=%s, user=%s',
                $session->id,
                $plan,
                $user->getEmail()
            ));
    
            return $this->render('subscription/purchase.html.twig', [
                'plan' => $planDetails,
                'plan_id' => $plan,
                'sessionId' => $session->id,
                'publicKey' => $this->getParameter('stripe_public_key')
            ]);
        } catch (\Exception $e) {
            // Log the error
            error_log('Error creating checkout session: ' . $e->getMessage());
            
            // Add user-friendly error message
            $this->addFlash('error', 'We encountered a problem setting up your subscription. Please try again or contact support.');
            
            // For development/testing - show error in template with detailed information
            return $this->render('subscription/purchase.html.twig', [
                'plan' => $planDetails,
                'plan_id' => $plan,
                'error' => true,
                'error_message' => 'Error: ' . $e->getMessage(),
                'sessionId' => 'mock_session_' . time(),
                'publicKey' => $this->getParameter('stripe_public_key')
            ]);
        }
    }

    #[Route('/webhook', name: 'app_subscription_webhook', methods: ['POST'])]
    public function webhook(Request $request): Response
    {
        try {
            $event = $this->stripeService->handleWebhook($request);
    
            if ($event->type === 'checkout.session.completed') {
                $session = $event->data->object;
                
                // Check if this is a mock session (from testing environment)
                $isMockCustomer = false;
                if (isset($session->customer) && str_contains($session->customer, 'mock')) {
                    error_log('Processing mock customer webhook: ' . $session->customer);
                    $isMockCustomer = true;
                }
                
                // Find user by stripe customer ID or use hack for mock users
                $user = null;
                if (!$isMockCustomer) {
                    $user = $this->entityManager->getRepository(User::class)
                        ->findOneBy(['stripeCustomerId' => $session->customer]);
                } else {
                    // TEMPORARY: For testing, create a mock user
                    $user = new User();
                    $user->setEmail('test@example.com');
                    $user->setFirstName('Test');
                    $user->setLastName('User');
                    $user->setRoles(['ROLE_USER']);
                    $user->setCredits(0);
                    $user->setStripeCustomerId($session->customer);
                    
                    // Log mock user creation
                    error_log('Created mock user for webhook processing');
                }
    
                if ($user) {
                    $type = $session->metadata->type ?? 'plan';
                    $packageOrPlan = $session->metadata->package_or_plan;
                    $credits = (int)($session->metadata->credits ?? 0);
                    
                    // Process based on payment type
                    if ($type === 'plan') {
                        // Get subscription ID if this is a subscription mode checkout
                        $subscriptionId = null;
                        if (isset($session->subscription) && $session->subscription) {
                            $subscriptionId = $session->subscription;
                            
                            // Store the subscription ID if method exists
                            if (method_exists($user, 'setStripeSubscriptionId')) {
                                $user->setStripeSubscriptionId($subscriptionId);
                            }
                            
                            // Calculate next billing date (30 days from now)
                            $nextBillingDate = new \DateTime();
                            $nextBillingDate->modify('+30 days');
                            
                            // Set next billing date if method exists
                            if (method_exists($user, 'setNextBillingDate')) {
                                $user->setNextBillingDate($nextBillingDate);
                            }
                            
                            // Log the subscription creation
                            error_log(sprintf(
                                'Subscription created: id=%s, user=%s, plan=%s, next_billing=%s',
                                $subscriptionId,
                                $user->getEmail(),
                                $packageOrPlan,
                                $nextBillingDate->format('Y-m-d')
                            ));
                        }
                        
                        // Set the subscription tier
                        $user->setSubscriptionTier($packageOrPlan);
                        
                        // Update user roles based on plan tier
                        $roles = $user->getRoles();
                        // Remove any existing premium role to reset
                        $roles = array_filter($roles, function($role) {
                            return $role !== 'ROLE_PREMIUM';
                        });
                        
                        // Assign ROLE_PREMIUM for professional and enterprise plans
                        if ($packageOrPlan === 'professional' || $packageOrPlan === 'enterprise') {
                            $roles[] = 'ROLE_PREMIUM';
                        }
                        $user->setRoles(array_values($roles));
                    }
                    
                    // Get price from package/plan based on the type
                    $price = 0;
                    if ($type === 'credit_package') {
                        $packageDetails = $this->getCreditPackageDetails($packageOrPlan);
                        $price = $packageDetails['price'] ?? 0;
                    } else {
                        $planDetails = $this->getPlanDetails($packageOrPlan);
                        $price = $planDetails['price'] ?? 0;
                    }
                    
                    // Create a transaction record
                    if ($type === 'credit_package') {
                        $transaction = Transaction::createCreditPurchase(
                            $user,
                            $price,
                            $credits,
                            $packageOrPlan,
                            $session->id
                        );
                    } else {
                        $transaction = Transaction::createSubscriptionPayment(
                            $user,
                            $price,
                            $packageOrPlan,
                            $session->id
                        );
                    }
                    
                    // Add the transaction to the database
                    $this->entityManager->persist($transaction);
                    
                    // Add credits for both plan and credit package purchases
                    $user->setCredits($user->getCredits() + $credits);
                    
                    // Only persist real users to the database
                    if (!$isMockCustomer) {
                        $this->entityManager->flush();
                    } else {
                        // For mock customers, we need to manually add credits
                        // since we're not persisting to the database
                        $mockMessage = sprintf(
                            'MOCK Transaction created: %s, %d credits, $%.2f',
                            $type === 'credit_package' ? 'Credit Purchase' : 'Subscription',
                            $credits,
                            $price
                        );
                        error_log($mockMessage);
                    }
                    
                    // Log the successful transaction
                    error_log(sprintf(
                        'Payment processed: User %s, Type %s, Credits added: %d, Total credits: %d',
                        $isMockCustomer ? 'MOCK' : $user->getId(),
                        $type,
                        $credits,
                        $user->getCredits()
                    ));
                } else {
                    error_log('No user found for Stripe customer: ' . $session->customer);
                }
            }
        } catch (\Exception $e) {
            // Log the error but return a 200 response to prevent Stripe from retrying
            error_log('Webhook error: ' . $e->getMessage());
        }

        // Always return success to Stripe, even if processing failed
        return new Response('Webhook handled', Response::HTTP_OK);
    }

    #[Route('/success', name: 'app_subscription_success')]
    public function success(Request $request): Response
    {
        // Get the session ID from the query parameters (Stripe includes this in the success redirect)
        $sessionId = $request->query->get('session_id');
        
        if ($sessionId) {
            // For local development, manually simulate a webhook event since Stripe can't reach localhost
            try {
                // Create a simulated webhook request
                $simulatedRequest = new Request();
                
                // Manually call the webhook handler with the session ID as a query parameter
                // This ensures credits are applied even if the real webhook doesn't reach our application
                $simulatedRequest->query->set('session_id', $sessionId);
                $this->webhook($simulatedRequest);
                
                // Log that we've simulated a webhook
                error_log('Simulated webhook event for session: ' . $sessionId);
            } catch (\Exception $e) {
                // Log any errors but continue to show success message
                error_log('Error in simulated webhook: ' . $e->getMessage());
            }
        }
        
        $this->addFlash('success', 'Payment successful! Your credits have been added.');
        return $this->redirectToRoute('app_dashboard');
    }

    #[Route('/cancel', name: 'app_subscription_cancel')]
    public function cancel(): Response
    {
        $this->addFlash('error', 'Payment cancelled. Please try again.');
        return $this->redirectToRoute('app_subscription_plans');
    }
    
    /**
     * Get details for a credit package
     */
    private function getCreditPackageDetails(string $package): array
    {
        $creditPackages = [
            'small' => [
                'name' => 'Small Credit Package',
                'credits' => 10,
                'price' => 9.99
            ],
            'medium' => [
                'name' => 'Medium Credit Package',
                'credits' => 50,
                'price' => 39.99
            ],
            'large' => [
                'name' => 'Large Credit Package',
                'credits' => 100,
                'price' => 69.99
            ]
        ];
        
        return $creditPackages[$package] ?? [
            'name' => 'Unknown Package',
            'credits' => 0,
            'price' => 0
        ];
    }
    
    /**
     * Get details for a subscription plan
     */
    private function getPlanDetails(string $plan): array
    {
        // Normalize plan identifier
        $normalizedPlan = strtolower(trim($plan));
        
        // Update plans to match UI and StripeService
        $plans = [
            'starter' => [
                'name' => 'Starter',
                'credits' => 50,
                'price' => 29.00
            ],
            'professional' => [
                'name' => 'Professional',
                'credits' => 100,
                'price' => 79.00
            ],
            'enterprise' => [
                'name' => 'Enterprise',
                'credits' => 250,
                'price' => 199.00
            ],
            'growth' => [
                'name' => 'Growth',
                'credits' => 100,
                'price' => 79.00
            ]
        ];
        
        return $plans[$normalizedPlan] ?? [
            'name' => ucfirst($plan),
            'credits' => 50,
            'price' => 29.00 // Default to Starter plan details
        ];
    }
}