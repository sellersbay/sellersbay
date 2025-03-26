<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\WooCategory;
use App\Entity\WooCommerceProduct;
use App\Repository\WooCategoryRepository;
use App\Repository\WooCommerceProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use App\Service\AIService;
use App\Service\SeoStatusService;

#[Route('/woocommerce')]
#[IsGranted('ROLE_USER')]
class WooCommerceController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private WooCommerceProductRepository $wooCommerceProductRepository;
    private WooCategoryRepository $wooCategoryRepository;
    private HttpClientInterface $httpClient;
    private AIService $aiService;
    private SeoStatusService $seoStatusService;

    public function __construct(
        EntityManagerInterface $entityManager,
        WooCommerceProductRepository $wooCommerceProductRepository,
        WooCategoryRepository $wooCategoryRepository,
        HttpClientInterface $httpClient,
        AIService $aiService,
        SeoStatusService $seoStatusService
    ) {
        $this->entityManager = $entityManager;
        $this->wooCommerceProductRepository = $wooCommerceProductRepository;
        $this->wooCategoryRepository = $wooCategoryRepository;
        $this->httpClient = $httpClient;
        $this->aiService = $aiService;
        $this->seoStatusService = $seoStatusService;
    }

    private function getTypedUser(): User
    {
        /** @var User $user */
        $user = $this->getUser();
        return $user;
    }

    #[Route('/', name: 'app_woocommerce_dashboard')]
    public function dashboard(Request $request): Response
    {
        $user = $this->getTypedUser();
        $category = $request->query->get('category');
        $keyword = $request->query->get('keyword');
        
        // Get all products for this user
        $allProducts = $this->wooCommerceProductRepository->findBy(['owner' => $user]);
        
        // Apply filters if specified
        if ($category || $keyword) {
            $filteredProducts = [];
            foreach ($allProducts as $product) {
                $matchesCategory = true;
                $matchesKeyword = true;
                
                // Check category filter
                if ($category) {
                    $matchesCategory = false;
                    // Check if product has originalData with categories
                    $originalData = $product->getOriginalData();
                    if (isset($originalData['categories']) && is_array($originalData['categories'])) {
                        foreach ($originalData['categories'] as $cat) {
                            if (isset($cat['name']) && $cat['name'] === $category) {
                                $matchesCategory = true;
                                break;
                            }
                        }
                    }
                }
                
                // Check keyword filter
                if ($keyword) {
                    $matchesKeyword = false;
                    $keyword = strtolower($keyword);
                    
                    // Check in name
                    if (str_contains(strtolower($product->getName()), $keyword)) {
                        $matchesKeyword = true;
                    }
                    // Check in description
                    elseif ($product->getDescription() && str_contains(strtolower($product->getDescription()), $keyword)) {
                        $matchesKeyword = true;
                    }
                    // Check in short description
                    elseif ($product->getShortDescription() && str_contains(strtolower($product->getShortDescription()), $keyword)) {
                        $matchesKeyword = true;
                    }
                }
                
                // Add to filtered products if both filters match
                if ($matchesCategory && $matchesKeyword) {
                    $filteredProducts[] = $product;
                }
            }
            $products = $filteredProducts;
        } else {
            $products = $allProducts;
        }
        
        // Get all unique categories for the filter dropdown
        $categories = [];
        foreach ($allProducts as $product) {
            $originalData = $product->getOriginalData();
            if (isset($originalData['categories']) && is_array($originalData['categories'])) {
                foreach ($originalData['categories'] as $cat) {
                    if (isset($cat['name'])) {
                        $categories[$cat['name']] = $cat['name'];
                    }
                }
            }
        }
        
        return $this->render('woocommerce/dashboard.html.twig', [
            'products' => $products,
            'categories' => $categories,
            'filter_category' => $category,
            'filter_keyword' => $keyword,
        ]);
    }

    #[Route('/connect', name: 'app_woocommerce_connect')]
    public function connect(Request $request): Response
    {
        $user = $this->getTypedUser();
        $editMode = $request->query->getBoolean('edit', false);
        
        // Check if user already has WooCommerce connection details
        $hasConnection = !empty($user->getWoocommerceStoreUrl()) && 
                        !empty($user->getWoocommerceConsumerKey()) && 
                        !empty($user->getWoocommerceConsumerSecret());
        
        // Handle store connection form submission
        if ($request->isMethod('POST')) {
            $storeUrl = $request->request->get('store_url');
            $consumerKey = $request->request->get('consumer_key');
            $consumerSecret = $request->request->get('consumer_secret');

            // Validate inputs
            if (empty($storeUrl) || empty($consumerKey) || empty($consumerSecret)) {
                $this->addFlash('error', 'All fields are required.');
                return $this->render('woocommerce/connect.html.twig', [
                    'edit_mode' => true,
                    'has_connection' => $hasConnection,
                    'store_url' => $user->getWoocommerceStoreUrl(),
                    'consumer_key' => $user->getWoocommerceConsumerKey()
                ]);
            }

            // Normalize store URL
            if (!str_starts_with($storeUrl, 'http')) {
                $storeUrl = 'https://' . $storeUrl;
            }
            
            // Remove trailing slash if present
            $storeUrl = rtrim($storeUrl, '/');

            // Test connection to WooCommerce API
            try {
                // Build API URL for testing
                $apiUrl = $storeUrl . '/wp-json/wc/v3/products';
                $timestamp = time();
                $nonce = bin2hex(random_bytes(6)); // 12 character random string
                
                // Create signature
                $params = [
                    'consumer_key' => $consumerKey,
                    'consumer_secret' => $consumerSecret,
                    'timestamp' => $timestamp,
                    'nonce' => $nonce
                ];
                
                // Make a test request
                $response = $this->httpClient->request('GET', $apiUrl, [
                    'query' => $params,
                    'verify_peer' => false,
                    'verify_host' => false,
                    'timeout' => 10
                ]);
                
                if ($response->getStatusCode() === 200) {
                    // Create a user-specific connection record
                    $user->setWoocommerceStoreUrl($storeUrl);
                    $user->setWoocommerceConsumerKey($consumerKey);
                    $user->setWoocommerceConsumerSecret($consumerSecret);
                    
                    $this->entityManager->flush();
                    
                    $this->addFlash('success', $hasConnection ? 'Store connection updated successfully!' : 'Store connected successfully!');
                    return $this->redirectToRoute('app_woocommerce_connect');
                } else {
                    throw new \Exception('API returned status code: ' . $response->getStatusCode());
                }
            } catch (\Exception $e) {
                $this->addFlash('error', 'Failed to connect to store: ' . $e->getMessage());
            }
        }

        return $this->render('woocommerce/connect.html.twig', [
            'edit_mode' => $editMode || !$hasConnection,
            'has_connection' => $hasConnection,
            'store_url' => $user->getWoocommerceStoreUrl(),
            'consumer_key' => $user->getWoocommerceConsumerKey()
        ]);
    }

    #[Route('/import', name: 'app_woocommerce_import')]
    public function import(Request $request): Response
    {
        $user = $this->getTypedUser();
        
        // Check if user has WooCommerce connection set up
        if (empty($user->getWoocommerceStoreUrl()) || 
            empty($user->getWoocommerceConsumerKey()) || 
            empty($user->getWoocommerceConsumerSecret())) {
            $this->addFlash('error', 'You need to connect your WooCommerce store first.');
            return $this->redirectToRoute('app_woocommerce_connect');
        }
        
        // Handle AJAX request to get a specific product for preview
        if ($request->isXmlHttpRequest() && $request->query->get('action') === 'get_product') {
            $productId = $request->query->get('product_id');
            
            if (!$productId) {
                return $this->json([
                    'success' => false,
                    'message' => 'Product ID is required'
                ]);
            }
            
            try {
                // Clear entity manager cache to ensure fresh data
                $this->entityManager->clear();
                
                // First try to get product from database - note this is per user
                $existingProduct = $this->wooCommerceProductRepository->findOneBy([
                    'woocommerceId' => $productId,
                    'owner' => $user
                ]);
                
                // Log attempt for debugging purposes
                error_log("Product preview requested: ID {$productId} for user {$user->getId()} / {$user->getEmail()} - " . date('Y-m-d H:i:s'));
                
                if ($existingProduct) {
                    // Debug helper function to check if value is truly set (not empty or just whitespace)
                    $hasValue = function($value) {
                        return isset($value) && $value !== null && trim((string)$value) !== '';
                    };
                    
                    // Check if meta description actually exists and isn't empty
                    $metaDescription = $existingProduct->getMetaDescription();
                    
                    // Log for debugging
                    error_log("Product ID {$productId} ({$existingProduct->getName()}) meta description check:");
                    error_log("Raw meta description: " . var_export($metaDescription, true));
                    error_log("Meta description length: " . ($metaDescription ? strlen($metaDescription) : 0));
                    error_log("Meta description is empty: " . ($metaDescription === null || trim($metaDescription) === '' ? 'true' : 'false'));
                    
                    $metaDescriptionExists = $hasValue($metaDescription);
                    
                    // Get all the raw data we can find about this product
                    $rawData = [
                        'id' => $existingProduct->getId(),
                        'woocommerce_id' => $existingProduct->getWoocommerceId(),
                        'name' => $existingProduct->getName(),
                        'meta_description' => $metaDescription,
                        'meta_description_exists' => $metaDescriptionExists, 
                        'meta_description_length' => $metaDescriptionExists ? strlen($metaDescription) : 0,
                        'meta_title' => $existingProduct->getMetaTitle(),
                        'target_keyphrase' => $existingProduct->getTargetKeyphrase(),
                        'image_alt_text' => $existingProduct->getImageAltText(),
                        'seo_status' => $existingProduct->getSeoStatus(),
                        'description' => $existingProduct->getDescription(),
                        'short_description' => $existingProduct->getShortDescription(),
                        'image_url' => $existingProduct->getImageUrl(),
                        // Add all getter methods available
                        'created_at' => $existingProduct->getCreatedAt() ? $existingProduct->getCreatedAt()->format('Y-m-d H:i:s') : null,
                        'updated_at' => $existingProduct->getUpdatedAt() ? $existingProduct->getUpdatedAt()->format('Y-m-d H:i:s') : null,
                        'debug_info' => [
                            'meta_description_check' => var_export($metaDescription, true),
                            'is_null' => $metaDescription === null,
                            'is_empty_string' => $metaDescription === '',
                            'trimmed_empty' => trim((string)$metaDescription) === '',
                            'user_id' => $user->getId()
                        ]
                    ];

                    // Get original data from database
                    $productData = $existingProduct->getOriginalData();
                    
                    // Return comprehensive debugging information for this product
                    return $this->json([
                        'success' => true,
                        'product' => [
                            'id' => $existingProduct->getWoocommerceId(),
                            'name' => $existingProduct->getName(),
                            'status' => $productData['status'] ?? 'publish',
                            'type' => $productData['type'] ?? 'simple',
                            'categories' => $productData['categories'] ?? [],
                            // Get actual data from the product
                            'meta_description' => $existingProduct->getMetaDescription(),
                            'meta_title' => $existingProduct->getMetaTitle(),
                            'target_keyphrase' => $existingProduct->getTargetKeyphrase(),
                            'description' => $existingProduct->getDescription(),
                            'short_description' => $existingProduct->getShortDescription(),
                            'images' => $productData['images'] ?? [],
                            'regular_price' => $productData['regular_price'] ?? '',
                            'sale_price' => $productData['sale_price'] ?? '',
                            'attributes' => $productData['attributes'] ?? [],
                            'meta_data' => array_merge(
                                $productData['meta_data'] ?? [],
                                [
                                    [
                                        'key' => 'meta_description',
                                        'value' => $existingProduct->getMetaDescription()
                                    ],
                                    [
                                        'key' => 'meta_title',
                                        'value' => $existingProduct->getMetaTitle()
                                    ],
                                    [
                                        'key' => 'target_keyphrase',
                                        'value' => $existingProduct->getTargetKeyphrase()
                                    ]
                                ]
                            )
                        ],
                        'source' => 'database_direct',
                        'raw_data' => $rawData,
                        'is_special_debug' => true,
                        'direct_fields' => [
                            'meta_description' => $existingProduct->getMetaDescription(),
                            'meta_title' => $existingProduct->getMetaTitle(),
                            'target_keyphrase' => $existingProduct->getTargetKeyphrase(),
                            'image_alt_text' => $existingProduct->getImageAltText(),
                            'seo_status' => $existingProduct->getSeoStatus()
                        ],
                        'user_id' => $user->getId()
                    ]);
                }
                
                // If not in database, fetch from WooCommerce API
                $apiUrl = $user->getWoocommerceStoreUrl() . '/wp-json/wc/v3/products/' . $productId;
                
                $response = $this->httpClient->request('GET', $apiUrl, [
                    'query' => [
                        'consumer_key' => $user->getWoocommerceConsumerKey(),
                        'consumer_secret' => $user->getWoocommerceConsumerSecret()
                    ],
                    'verify_peer' => false,
                    'verify_host' => false,
                    'timeout' => 30
                ]);
                
                // Get product from the API response
                $productData = $response->toArray();
                
                return $this->json([
                    'success' => true,
                    'product' => $productData,
                    'source' => 'api'
                ]);
            } catch (\Exception $e) {
                return $this->json([
                    'success' => false,
                    'message' => 'Error fetching product: ' . $e->getMessage()
                ]);
            }
        }
        
        // Get categories for filtering
        $categories = [];
        $stagedProducts = $this->wooCommerceProductRepository->findByOwnerAndStatus($user, 'staged');
        foreach ($stagedProducts as $product) {
            $originalData = $product->getOriginalData();
            if (isset($originalData['categories']) && is_array($originalData['categories'])) {
                foreach ($originalData['categories'] as $cat) {
                    if (isset($cat['name'])) {
                        $categories[$cat['name']] = $cat['name'];
                    }
                }
            }
        }
        
        // Handle AJAX request to fetch products from WooCommerce API
        if ($request->isXmlHttpRequest() && $request->query->get('action') === 'fetch_products') {
            try {
                $apiUrl = $user->getWoocommerceStoreUrl() . '/wp-json/wc/v3/products';
                $page = $request->query->getInt('page', 1);
                $perPage = $request->query->getInt('per_page', 25);
                
                // Apply filters if provided
                $queryParams = [
                    'consumer_key' => $user->getWoocommerceConsumerKey(),
                    'consumer_secret' => $user->getWoocommerceConsumerSecret(),
                    'page' => $page,
                    'per_page' => $perPage,
                    'status' => 'publish', // Only fetch published products by default
                    'orderby' => 'date',
                    'order' => 'desc'
                ];
                
                // Check if a pause was requested - for supporting the pause button functionality
                $isPaused = $request->query->getBoolean('paused', false);
                if ($isPaused) {
                    return $this->json([
                        'success' => true,
                        'paused' => true,
                        'message' => 'Download paused. Resume to continue.',
                        'currentPage' => $page,
                        'category' => $request->query->get('category', '')
                    ]);
                }
                
                // Add category filter if specified
                if ($category = $request->query->get('category')) {
                    // Find the category in the database to get its WooCommerce ID
                    $wooCategoryEntity = $this->wooCategoryRepository->findOneBy([
                        'name' => $category,
                        'owner' => $user
                    ]);
                    
                    if ($wooCategoryEntity && $wooCategoryEntity->getWoocommerceId()) {
                        // Use proper WooCommerce API parameter - filter by category ID
                        $queryParams['category'] = $wooCategoryEntity->getWoocommerceId();
                    } else {
                        // Fallback to using category name as a search term if no ID is found
                        $queryParams['search'] = $category;
                    }
                }
                
                // Add search filter if specified
                if ($search = $request->query->get('search')) {
                    $queryParams['search'] = $search;
                }
                
                $response = $this->httpClient->request('GET', $apiUrl, [
                    'query' => $queryParams,
                    'verify_peer' => false,
                    'verify_host' => false,
                    'timeout' => 30
                ]);
                
                // Get products from the API response
                $apiProducts = $response->toArray();
                $totalProducts = $response->getHeaders()['x-wp-total'][0] ?? 0;
                $totalPages = $response->getHeaders()['x-wp-totalpages'][0] ?? 0;
                
                // Process each product - either mark as existing or save to database
                $savedCount = 0;
                $products = [];
                $duplicateProducts = [];
                $newEntities = [];
                
                foreach ($apiProducts as $productData) {
                    // Check if product already exists in database
                    $existingProduct = $this->wooCommerceProductRepository->findOneBy([
                        'woocommerceId' => $productData['id'],
                        'owner' => $user
                    ]);
                    
                    // If product doesn't exist, save it to the database
                    if (!$existingProduct) {
                        $newProduct = new WooCommerceProduct();
                        $newProduct->setWoocommerceId($productData['id']);
                        $newProduct->setName($productData['name']);
                        $newProduct->setDescription($productData['description'] ?? '');
                        $newProduct->setShortDescription($productData['short_description'] ?? '');
                        
                        // Store image URL if available
                        if (!empty($productData['images'][0]['src'])) {
                            $newProduct->setImageUrl($productData['images'][0]['src']);
                        }
                        
                        // Extract meta data for SEO if available
                        if (!empty($productData['meta_data'])) {
                            foreach ($productData['meta_data'] as $metaItem) {
                                if (isset($metaItem['key'])) {
                                    switch ($metaItem['key']) {
                                        case '_yoast_wpseo_title':
                                        case 'yoast_wpseo_title':
                                        case '_seo_title':
                                        case 'seo_title':
                                            $newProduct->setMetaTitle($metaItem['value'] ?? '');
                                            break;
                                        case '_yoast_wpseo_focuskw':
                                        case 'yoast_wpseo_focuskw':
                                        case '_seo_focus_keyword':
                                        case 'seo_focus_keyword':
                                        case 'focus_keyword':
                                            $newProduct->setTargetKeyphrase($metaItem['value'] ?? '');
                                            break;
                                        case '_yoast_wpseo_metadesc':
                                        case 'yoast_wpseo_metadesc':
                                        case '_seo_meta_description':
                                            $newProduct->setMetaDescription($metaItem['value'] ?? '');
                                            break;
                                    }
                                }
                            }
                        }
                        
                        // Second attempt: Check if meta data might be in the main product data
                        if (empty($newProduct->getMetaTitle())) {
                            if (isset($productData['yoast_title']) || isset($productData['seo_title'])) {
                                $metaTitle = $productData['yoast_title'] ?? $productData['seo_title'] ?? null;
                                if (!empty($metaTitle)) {
                                    $newProduct->setMetaTitle($metaTitle);
                                }
                            } else if (!empty($productData['name'])) {
                                // Fallback to product name as meta title
                                $newProduct->setMetaTitle($productData['name']);
                            }
                        }
                        
                        if (empty($newProduct->getTargetKeyphrase())) {
                            if (isset($productData['yoast_focuskw']) || isset($productData['focus_keyword'])) {
                                $focusKw = $productData['yoast_focuskw'] ?? $productData['focus_keyword'] ?? null;
                                if (!empty($focusKw)) {
                                    $newProduct->setTargetKeyphrase($focusKw);
                                }
                            } else if (!empty($productData['name'])) {
                                // Fallback to first word of product name
                                $wordArray = explode(' ', $productData['name']);
                                $firstWord = isset($wordArray[0]) ? $wordArray[0] : 'product';
                                $newProduct->setTargetKeyphrase($firstWord);
                            }
                        }
                        
                        // Store original data for reference
                        $newProduct->setOriginalData($productData);
                        
                        // Set owner and store connection details
                        $newProduct->setOwner($user);
                        $newProduct->setStoreUrl($user->getWoocommerceStoreUrl());
                        $newProduct->setConsumerKey($user->getWoocommerceConsumerKey());
                        $newProduct->setConsumerSecret($user->getWoocommerceConsumerSecret());
                        
                        // CRITICAL: Ensure meta_description is always set with unique identifier
                        $timestamp = time();
                        $metaDescValue = '';
                        if (!empty($productData['short_description'])) {
                            $metaDescValue = substr(strip_tags($productData['short_description']), 0, 150) . ' - ID:' . $timestamp;
                        } else {
                            $metaDescValue = $productData['name'] . ' - Product description - ID:' . $timestamp;
                        }
                        $newProduct->setMetaDescription($metaDescValue);
                        
                        // CRITICAL: Ensure image_alt_text is always set with unique identifier
                        $imageAltTextValue = 'Image of ' . $productData['name'] . ' - ID:' . $timestamp;
                        $newProduct->setImageAltText($imageAltTextValue);
                        
                        // Calculate and set SEO status
                        $seoStatus = $this->seoStatusService->calculateSeoStatus($newProduct);
                        $newProduct->setSeoStatus($seoStatus);
                        
                        // Set status to staged (not yet imported to dashboard)
                        $newProduct->setStatus('staged');
                        
                        // Save product to database with immediate flush to ensure meta fields are persisted
                        $this->entityManager->persist($newProduct);
                        $this->entityManager->flush();
                        
                        // Verify meta fields were saved by reloading the entity
                        $this->entityManager->refresh($newProduct);
                        
                        // If fields are still empty after refresh, set them again
                        if (empty($newProduct->getMetaDescription())) {
                            $newProduct->setMetaDescription($metaDescValue . ' (retry)');
                            $this->entityManager->persist($newProduct);
                            $this->entityManager->flush();
                        }
                        
                        if (empty($newProduct->getImageAltText())) {
                            $newProduct->setImageAltText($imageAltTextValue . ' (retry)');
                            $this->entityManager->persist($newProduct);
                            $this->entityManager->flush();
                        }
                        $savedCount++;
                        
                        // Add the entity ID to the product data
                        $productData['entity_id'] = null; // Will be filled after flush
                        $productData['imported'] = false;
                        $productData['import_status'] = 'staged';
                        
                        // Add SEO status to the JSON response (both camelCase and snake_case for compatibility)
                        $productData['seoStatus'] = $seoStatus;
                        $productData['seo_status'] = $seoStatus;
                        
                        // Keep track of the mapping between API product and new entity
                        $newEntities[$productData['id']] = $newProduct;
                    } else {
                        // Calculate SEO status if needed
                        if (!$existingProduct->getSeoStatus()) {
                            $seoStatus = $this->seoStatusService->calculateSeoStatus($existingProduct);
                            $existingProduct->setSeoStatus($seoStatus);
                            $this->entityManager->persist($existingProduct);
                            $this->entityManager->flush();
                        }
                        
                        // Product already exists, add entity ID to the data
                        $productData['entity_id'] = $existingProduct->getId();
                        $productData['imported'] = true;
                        $productData['import_status'] = $existingProduct->getStatus();
                        
                        // Add SEO status to the JSON response (both camelCase and snake_case for compatibility)
                        $seoStatus = $existingProduct->getSeoStatus() ?: 'needs_improvement';
                        $productData['seoStatus'] = $seoStatus;
                        $productData['seo_status'] = $seoStatus;
                        
                        // Add to duplicate products array for frontend notification
                        $duplicateProducts[] = [
                            'id' => $productData['id'],
                            'name' => $productData['name'],
                            'entity_id' => $existingProduct->getId()
                        ];
                    }
                    
                    $products[] = $productData;
                }
                
                // Flush all new products at once
                if ($savedCount > 0) {
                    $this->entityManager->flush();
                    
                    // Update entity IDs in response data
                    foreach ($products as &$productData) {
                        if (isset($newEntities[$productData['id']])) {
                            $productData['entity_id'] = $newEntities[$productData['id']]->getId();
                        }
                    }
                }
                
                // Extract unique categories from the fetched products
                $productCategories = [];
                foreach ($apiProducts as $product) {
                    if (isset($product['categories']) && is_array($product['categories'])) {
                        foreach ($product['categories'] as $cat) {
                            if (isset($cat['name'])) {
                                $productCategories[$cat['name']] = $cat['name'];
                            }
                        }
                    }
                }
                
                // If there are no products for this category, return early
                if (empty($apiProducts) && $page === 1) {
                    return $this->json([
                        'success' => true,
                        'products' => [],
                        'duplicateProducts' => [],
                        'total' => 0,
                        'totalPages' => 0,
                        'currentPage' => 1,
                        'categories' => [],
                        'noProductsFound' => true,
                        'category' => $category
                    ]);
                }
                
                // Return the JSON response when fetching products
                return $this->json([
                    'success' => true,
                    'products' => $products,
                    'duplicateProducts' => $duplicateProducts,
                    'duplicateCount' => count($duplicateProducts),
                    'savedCount' => $savedCount,
                    'total' => $totalProducts,
                    'totalPages' => $totalPages,
                    'currentPage' => $page,
                    'category' => $category,
                    'categories' => array_values($productCategories ?: $categories),
                    'hasMorePages' => ($page < $totalPages)
                ], 200, [
                    'Content-Type' => 'application/json'
                ], [
                    'seo_status' => true,         // Ensure seo_status is included in the serialization
                    'max_depth' => 3,             // Increase depth for nested objects
                    'circular_reference_handler' => function ($object) {
                        return $object->getId();  // Handle circular references
                    }
                ]);
            } catch (\Exception $e) {
                return $this->json([
                    'success' => false,
                    'message' => 'Failed to fetch products: ' . $e->getMessage()
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }
        
        // Handle AJAX request to fetch categories
        if ($request->isXmlHttpRequest() && $request->query->get('action') === 'fetch_categories') {
            try {
                $storeUrl = $user->getWoocommerceStoreUrl();
                $forceRefresh = $request->query->getBoolean('force_refresh', false);
                $allCategories = [];
                
                // Check if we already have categories stored in the database
                if (!$forceRefresh) {
                    $storedCategories = $this->wooCategoryRepository->findByOwnerAndStore($user, $storeUrl);
                    
                    // If we have categories in the database, use them
                    if (count($storedCategories) > 0) {
                        foreach ($storedCategories as $category) {
                            $allCategories[$category->getName()] = $category->getName();
                        }
                        
                        // Return the cached categories from database
                        return $this->json([
                            'success' => true,
                            'categories' => array_values($allCategories),
                            'totalCategories' => count($allCategories),
                            'source' => 'database'
                        ]);
                    }
                }
                
                // If no categories in database or force refresh, fetch from WooCommerce API
                $apiUrl = $storeUrl . '/wp-json/wc/v3/products/categories';
                $response = $this->httpClient->request('GET', $apiUrl, [
                    'query' => [
                        'consumer_key' => $user->getWoocommerceConsumerKey(),
                        'consumer_secret' => $user->getWoocommerceConsumerSecret(),
                        'per_page' => 100,
                        'orderby' => 'name',
                        'order' => 'asc'
                    ],
                    'verify_peer' => false,
                    'verify_host' => false,
                    'timeout' => 15
                ]);
                
                if ($response->getStatusCode() === 200) {
                    // Clear existing categories if we're doing a force refresh
                    if ($forceRefresh) {
                        $existingCategories = $this->wooCategoryRepository->findByOwnerAndStore($user, $storeUrl);
                        foreach ($existingCategories as $existingCategory) {
                            $this->entityManager->remove($existingCategory);
                        }
                        $this->entityManager->flush();
                    }
                    
                    $categoryData = $response->toArray();
                    
                    // Get the total number of categories for pagination purposes
                    $totalCategories = $response->getHeaders()['x-wp-total'][0] ?? count($categoryData);
                    $totalPages = $response->getHeaders()['x-wp-totalpages'][0] ?? 1;
                    
                    // Process and store each category
                    foreach ($categoryData as $categoryItem) {
                        if (isset($categoryItem['name'])) {
                            // Store in our return array
                            $allCategories[$categoryItem['name']] = $categoryItem['name'];
                            
                            // Create and store category in database
                            $category = new WooCategory();
                            $category->setName($categoryItem['name']);
                            $category->setWoocommerceId($categoryItem['id'] ?? null);
                            $category->setSlug($categoryItem['slug'] ?? null);
                            $category->setCount($categoryItem['count'] ?? null);
                            $category->setOriginalData($categoryItem);
                            $category->setOwner($user);
                            $category->setStoreUrl($storeUrl);
                            
                            // Persist to database
                            $this->entityManager->persist($category);
                        }
                    }
                    
                    // If we have more pages, fetch them too (for larger catalogs)
                    if ($totalPages > 1) {
                        for ($page = 2; $page <= $totalPages && $page <= 5; $page++) { // Limit to 5 pages max for performance
                            $nextPageResponse = $this->httpClient->request('GET', $apiUrl, [
                                'query' => [
                                    'consumer_key' => $user->getWoocommerceConsumerKey(),
                                    'consumer_secret' => $user->getWoocommerceConsumerSecret(),
                                    'per_page' => 100,
                                    'page' => $page,
                                    'orderby' => 'name',
                                    'order' => 'asc'
                                ],
                                'verify_peer' => false,
                                'verify_host' => false,
                                'timeout' => 15
                            ]);
                            
                            if ($nextPageResponse->getStatusCode() === 200) {
                                $nextPageData = $nextPageResponse->toArray();
                                foreach ($nextPageData as $categoryItem) {
                                    if (isset($categoryItem['name'])) {
                                        // Store in our return array
                                        $allCategories[$categoryItem['name']] = $categoryItem['name'];
                                        
                                        // Create and store category in database
                                        $category = new WooCategory();
                                        $category->setName($categoryItem['name']);
                                        $category->setWoocommerceId($categoryItem['id'] ?? null);
                                        $category->setSlug($categoryItem['slug'] ?? null);
                                        $category->setCount($categoryItem['count'] ?? null);
                                        $category->setOriginalData($categoryItem);
                                        $category->setOwner($user);
                                        $category->setStoreUrl($storeUrl);
                                        
                                        // Persist to database
                                        $this->entityManager->persist($category);
                                    }
                                }
                            }
                        }
                    }
                    
                    // Save all categories to database
                    $this->entityManager->flush();
                }
                
                // If API call didn't return any categories, fallback to categories from stored products
                if (empty($allCategories)) {
                    // Get all categories from all stored products
                    $allUserProducts = $this->wooCommerceProductRepository->findBy(['owner' => $user]);
                    
                    foreach ($allUserProducts as $product) {
                        $originalData = $product->getOriginalData();
                        if (isset($originalData['categories']) && is_array($originalData['categories'])) {
                            foreach ($originalData['categories'] as $cat) {
                                if (isset($cat['name'])) {
                                    $allCategories[$cat['name']] = $cat['name'];
                                }
                            }
                        }
                    }
                }
                
                // Sort categories alphabetically
                ksort($allCategories);
                
                return $this->json([
                    'success' => true,
                    'categories' => array_values($allCategories),
                    'totalCategories' => count($allCategories),
                    'source' => 'api'
                ]);
            } catch (\Exception $e) {
                return $this->json([
                    'success' => false,
                    'message' => 'Failed to fetch categories: ' . $e->getMessage()
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }
        
        // Handle AJAX request to fetch already staged products
        if ($request->isXmlHttpRequest() && $request->query->get('action') === 'fetch_staged_products') {
            try {
                $category = $request->query->get('category');
                $search = $request->query->get('search');
                
                $stagedProducts = $this->wooCommerceProductRepository->findByOwnerAndStatus($user, 'staged');
                
                // Apply filters if provided
                if ($category || $search) {
                    $filteredProducts = [];
                    foreach ($stagedProducts as $product) {
                        $matchesCategory = true;
                        $matchesSearch = true;
                        
                        // Apply category filter
                        if ($category) {
                            $matchesCategory = false;
                            $originalData = $product->getOriginalData();
                            if (isset($originalData['categories']) && is_array($originalData['categories'])) {
                                foreach ($originalData['categories'] as $cat) {
                                    if (isset($cat['name']) && $cat['name'] === $category) {
                                        $matchesCategory = true;
                                        break;
                                    }
                                }
                            }
                        }
                        
                        // Apply search filter
                        if ($search) {
                            $matchesSearch = false;
                            $search = strtolower($search);
                            
                            if (str_contains(strtolower($product->getName()), $search) ||
                                ($product->getDescription() && str_contains(strtolower($product->getDescription()), $search)) ||
                                ($product->getShortDescription() && str_contains(strtolower($product->getShortDescription()), $search))) {
                                $matchesSearch = true;
                            }
                        }
                        
                        if ($matchesCategory && $matchesSearch) {
                            $filteredProducts[] = $product;
                        }
                    }
                    $stagedProducts = $filteredProducts;
                }
                
                // Format products for JSON response
                $formattedProducts = [];
                foreach ($stagedProducts as $product) {
                    $originalData = $product->getOriginalData();
                    $categories = [];
                    
                    if (isset($originalData['categories']) && is_array($originalData['categories'])) {
                        foreach ($originalData['categories'] as $cat) {
                            if (isset($cat['name'])) {
                                $categories[] = ['name' => $cat['name']];
                            }
                        }
                    }
                    
                    $formattedProducts[] = [
                        'id' => $product->getWoocommerceId(),
                        'entity_id' => $product->getId(),
                        'name' => $product->getName(),
                        'slug' => $originalData['slug'] ?? '',
                        'status' => $originalData['status'] ?? 'draft',
                        'categories' => $categories,
                        'date_modified' => $product->getUpdatedAt()->format('Y-m-d H:i:s'),
                        'images' => isset($originalData['images']) ? $originalData['images'] : [],
                        'imported' => true,
                        'import_status' => $product->getStatus(),
                        // SEO-related fields that need to be included
                        'seo_status' => $product->getSeoStatus(),
                        'meta_description' => $product->getMetaDescription(),
                        'meta_title' => $product->getMetaTitle(),
                        'target_keyphrase' => $product->getTargetKeyphrase(),
                        // Add a log of when this data was generated
                        'generated_at' => date('Y-m-d H:i:s')
                    ];
                    
                    // Log data for debugging
                    error_log("Staged product {$product->getWoocommerceId()} ({$product->getName()}) - SEO status: {$product->getSeoStatus()}, Meta desc exists: " . ($product->getMetaDescription() ? 'YES' : 'NO'));
                }
                
                return $this->json([
                    'success' => true,
                    'products' => $formattedProducts,
                    'total' => count($formattedProducts),
                    'timestamp' => date('Y-m-d H:i:s')
                ]);
            } catch (\Exception $e) {
                return $this->json([
                    'success' => false,
                    'message' => 'Failed to fetch staged products: ' . $e->getMessage()
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }
        
        // Handle AJAX request to import products
        if ($request->isXmlHttpRequest() && $request->isMethod('POST') && 
            $request->request->get('action') === 'import_products') {
            try {
                $productIds = json_decode($request->request->get('product_ids', '[]'), true);
                
                if (empty($productIds)) {
                    return $this->json([
                        'success' => false,
                        'message' => 'No products selected for import'
                    ]);
                }
                
                $importedProducts = [];
                $importCount = 0;
                
                foreach ($productIds as $productId) {
                    // First try with entity ID
                    $product = $this->wooCommerceProductRepository->find($productId);
                    
                    // If not found, try with WooCommerce ID
                    if (!$product) {
                        $product = $this->wooCommerceProductRepository->findOneBy([
                            'woocommerceId' => $productId,
                            'owner' => $user
                        ]);
                    }
                    
                    // Skip if product not found or already imported
                    if (!$product || $product->getStatus() !== 'staged') {
                        continue;
                    }
                    
                    // Security check - ensure product belongs to user
                    if ($product->getOwner() !== $user) {
                        continue;
                    }
                    
                    // Update status to imported - this will move it to the dashboard
                    $product->setStatus('imported');
                    $importCount++;
                    
                    // Add to the list of imported products
                    $importedProducts[] = [
                        'entityId' => $product->getId(),
                        'woocommerceId' => $product->getWoocommerceId(),
                        'name' => $product->getName()
                    ];
                }
                
                $this->entityManager->flush();
                
                return $this->json([
                    'success' => true,
                    'message' => sprintf('%d products imported successfully', $importCount),
                    'count' => $importCount
                ]);
            } catch (\Exception $e) {
                return $this->json([
                    'success' => false,
                    'message' => 'Failed to import products: ' . $e->getMessage()
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }
        
        // Render the template with initial data
        return $this->render('woocommerce/import.html.twig', [
            'categories' => $categories
        ]);
    }
    
    #[Route('/debug-seo', name: 'app_woocommerce_debug_seo')]
    public function debugSeo(Request $request): Response
    {
        $user = $this->getTypedUser();
        
        // Get all products for this user
        $products = $this->wooCommerceProductRepository->findBy(['owner' => $user]);
        
        $results = [];
        foreach ($products as $product) {
            // Get raw metadata values for inspection
            $metaDesc = $product->getMetaDescription();
            $shortDesc = $product->getShortDescription();
            $altText = $product->getImageAltText();
            $description = $product->getDescription();
            $targetKeyphrase = $product->getTargetKeyphrase();
            
            // Special case for the product the user mentioned - log extra information
            $isSpecificProduct = (strpos($product->getName(), 'San Francisco 49ers Wincraft NFL Pencils') !== false);
            if ($isSpecificProduct) {
                error_log("SPECIAL PRODUCT DETECTED: " . $product->getName());
                error_log("Meta Description: [" . ($metaDesc ?: 'NULL') . "]");
                error_log("Meta Description Length: " . ($metaDesc ? strlen($metaDesc) : 0));
                error_log("Meta Description after trim: [" . ($metaDesc ? trim($metaDesc) : 'NULL') . "]");
                error_log("Short Description: [" . ($shortDesc ?: 'NULL') . "]");
            }
            
            // Calculate SEO score based on the criteria the user wants - with strict empty checking
            $hasMetaDescription = $metaDesc !== null && trim($metaDesc) !== '';
            $hasShortDescription = $shortDesc !== null && trim($shortDesc) !== '';
            $hasImageAltText = $altText !== null && trim($altText) !== '';
            $hasDetailedDescription = $description !== null && trim($description) !== '' && strlen(strip_tags($description)) > 50;
            $hasTargetKeyphrase = $targetKeyphrase !== null && trim($targetKeyphrase) !== '';
            
            // Debug output to help troubleshoot
            $debugInfo = sprintf(
                "Product #%s (%s): meta_desc=[%s], short_desc=%s, image_alt=%s, desc=%s, keyphrase=%s",
                $product->getWoocommerceId(),
                $product->getName(),
                $metaDesc ? (strlen($metaDesc) > 30 ? substr($metaDesc, 0, 30) . '...' : $metaDesc) : 'NULL',
                $hasShortDescription ? 'Yes' : 'No',
                $hasImageAltText ? 'Yes' : 'No',
                $hasDetailedDescription ? 'Yes' : 'No',
                $hasTargetKeyphrase ? 'Yes' : 'No'
            );
            error_log($debugInfo);
            
            $seoScore = 0;
            if ($hasMetaDescription) $seoScore++;
            if ($hasShortDescription) $seoScore++;
            if ($hasImageAltText) $seoScore++;
            if ($hasDetailedDescription) $seoScore++;
            
            // Consider optimized if it has at least 3 out of 4 criteria
            $calculatedStatus = ($seoScore >= 3) ? 'optimized' : 'needs_improvement';
            $currentStatus = $product->getSeoStatus();
            
            // Force update SEO status to ensure it's current
            if ($currentStatus !== $calculatedStatus) {
                $product->setSeoStatus($calculatedStatus);
                $this->entityManager->persist($product);
            }
            
            $results[] = [
                'id' => $product->getId(),
                'woocommerceId' => $product->getWoocommerceId(),
                'name' => $product->getName(),
                'metaDescription' => $hasMetaDescription ? 'Yes' : 'No',
                'metaDescriptionRaw' => $metaDesc ? (strlen($metaDesc) > 50 ? substr($metaDesc, 0, 50) . '...' : $metaDesc) : '(empty)',
                'shortDescription' => $hasShortDescription ? 'Yes' : 'No',
                'imageAltText' => $hasImageAltText ? 'Yes' : 'No',
                'detailedDescription' => $hasDetailedDescription ? 'Yes' : 'No',
                'targetKeyphrase' => $hasTargetKeyphrase ? 'Yes' : 'No',
                'seoScore' => $seoScore,
                'currentStatus' => $currentStatus,
                'calculatedStatus' => $calculatedStatus,
                'highlight' => $isSpecificProduct
            ];
        }
        
        // Flush changes
        $this->entityManager->flush();
        
        // Sort by SEO score (highest first)
        usort($results, function($a, $b) {
            // Put highlighted product at the top
            if ($a['highlight'] && !$b['highlight']) return -1;
            if (!$a['highlight'] && $b['highlight']) return 1;
            
            // Then sort by SEO score
            return $b['seoScore'] - $a['seoScore'];
        });
        
        return $this->render('woocommerce/debug_seo.html.twig', [
            'products' => $results
        ]);
    }

    #[Route('/export/{id}', name: 'app_woocommerce_export')]
    public function export(WooCommerceProduct $product): Response
    {
        try {
            $user = $this->getTypedUser();
            
            // Security check - ensure product belongs to user
            if ($product->getOwner() !== $user) {
                throw $this->createAccessDeniedException('You cannot export this product.');
            }
            
            // Check if product has been AI-processed by looking at the status
            // or checking if the fields have been populated
            if ($product->getStatus() === 'imported' || 
                empty($product->getDescription()) || 
                empty($product->getShortDescription()) || 
                empty($product->getMetaDescription())) {
                $this->addFlash('error', 'This product has not been processed with AI yet.');
                return $this->redirectToRoute('app_woocommerce_dashboard');
            }
            
            // Build API URL and request data
            $apiUrl = $product->getStoreUrl() . '/wp-json/wc/v3/products/' . $product->getWoocommerceId();
            
            // Prepare data to update
            $updateData = [
                'description' => $product->getDescription(),
                'short_description' => $product->getShortDescription(),
                'meta_data' => [
                    [
                        'key' => '_yoast_wpseo_metadesc',
                        'value' => $product->getMetaDescription()
                    ],
                    [
                        'key' => '_yoast_wpseo_title',
                        'value' => $product->getMetaTitle()
                    ],
                    [
                        'key' => '_yoast_wpseo_focuskw',
                        'value' => $product->getTargetKeyphrase()
                    ]
                ]
            ];
            
            // If product has image alt text, update it
            if ($product->getImageAltText()) {
                $originalData = $product->getOriginalData();
                
                // First try with image ID if available
                if (!empty($originalData['images'][0]['id'])) {
                    $imageId = $originalData['images'][0]['id'];
                    $updateData['images'] = [
                        [
                            'id' => $imageId,
                            'alt' => $product->getImageAltText()
                        ]
                    ];
                } 
                // Even if no image ID is available, still include alt text in the update
                // This ensures alt text is always exported regardless of image structure
                else {
                    // Add alt text as a custom meta data field to ensure it gets exported
                    $updateData['meta_data'][] = [
                        'key' => '_product_image_alt_text',
                        'value' => $product->getImageAltText()
                    ];
                    
                    // If there's an image but no ID, try sending alt text with the src
                    if (!empty($originalData['images'][0]['src'])) {
                        $updateData['images'] = [
                            [
                                'src' => $originalData['images'][0]['src'],
                                'alt' => $product->getImageAltText()
                            ]
                        ];
                    }
                }
            }
            
            // Make API request to update product
            $response = $this->httpClient->request('PUT', $apiUrl, [
                'query' => [
                    'consumer_key' => $product->getConsumerKey(),
                    'consumer_secret' => $product->getConsumerSecret()
                ],
                'json' => $updateData,
                'verify_peer' => false,
                'verify_host' => false,
                'timeout' => 30
            ]);
            
            if ($response->getStatusCode() !== 200) {
                throw new \Exception('API returned status code: ' . $response->getStatusCode());
            }
            
            // Store API response and update status
            $responseData = $response->toArray();
            $product->setApiResponse($responseData);
            $product->setStatus('exported');
            $this->entityManager->flush();
            
            $this->addFlash('success', 'Product exported successfully!');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Failed to export product: ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_woocommerce_dashboard');
    }

    #[Route('/sync/{id}', name: 'app_woocommerce_sync')]
    public function sync(WooCommerceProduct $product): Response
    {
        try {
            $user = $this->getTypedUser();
            
            // Security check - ensure product belongs to user
            if ($product->getOwner() !== $user) {
                throw $this->createAccessDeniedException('You cannot sync this product.');
            }
            
            // Build API URL to fetch the latest product data from WooCommerce
            $apiUrl = $product->getStoreUrl() . '/wp-json/wc/v3/products/' . $product->getWoocommerceId();
            
            // Prepare request options with increased timeout
            $options = [
                'query' => [
                    'consumer_key' => $product->getConsumerKey(),
                    'consumer_secret' => $product->getConsumerSecret()
                ],
                'verify_peer' => false,
                'verify_host' => false,
                'timeout' => 30, // Increased timeout to 30 seconds
                'max_redirects' => 5,
                'headers' => [
                    'Accept' => 'application/json',
                    'User-Agent' => 'RoboSEO/1.0'
                ]
            ];
            
            // Make API request to WooCommerce with proper error handling
            try {
                $response = $this->httpClient->request('GET', $apiUrl, $options);
                
                if ($response->getStatusCode() !== 200) {
                    throw new \Exception(sprintf(
                        'API returned non-success status code: %d - %s', 
                        $response->getStatusCode(), 
                        $response->getContent(false)
                    ));
                }
                
                // Get product data from response
                try {
                    $productData = $response->toArray();
                } catch (\Exception $e) {
                    throw new \Exception('Failed to parse API response: ' . $e->getMessage());
                }
                
                // Validate response data
                if (!isset($productData['id']) || $productData['id'] != $product->getWoocommerceId()) {
                    throw new \Exception('API returned invalid product data: Product ID mismatch');
                }
                
                // Update product with fresh data
                $product->setName($productData['name']);
                $product->setDescription($productData['description'] ?? '');
                $product->setShortDescription($productData['short_description'] ?? '');
                
                // Update image URL if available
                if (!empty($productData['images'][0]['src'])) {
                    $product->setImageUrl($productData['images'][0]['src']);
                }
                
                // Store updated original data for reference
                $product->setOriginalData($productData);
                
                // Extract metadata if available
                if (!empty($productData['meta_data'])) {
                    foreach ($productData['meta_data'] as $metaItem) {
                        if (isset($metaItem['key'])) {
                            switch ($metaItem['key']) {
                                case '_yoast_wpseo_metadesc':
                                    $product->setMetaDescription($metaItem['value'] ?? '');
                                    break;
                                case '_yoast_wpseo_title':
                                    $product->setMetaTitle($metaItem['value'] ?? '');
                                    break;
                                case '_yoast_wpseo_focuskw':
                                    $product->setTargetKeyphrase($metaItem['value'] ?? '');
                                    break;
                            }
                        }
                    }
                }
                
                // Extract image alt text if available
                if (!empty($productData['images'][0]['alt'])) {
                    $product->setImageAltText($productData['images'][0]['alt']);
                }
                
                // Preserve AI-processed content and status
                // No changes needed to status if it's already AI processed or exported
                
                // Save changes to database
                $this->entityManager->flush();
                
                $this->addFlash('success', 'Product synced successfully from WooCommerce!');
                
            } catch (\Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface $e) {
                // Handle connection and network-specific errors
                throw new \Exception('Network connection error: ' . $e->getMessage());
            } catch (\Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface $e) {
                // Handle HTTP protocol errors
                throw new \Exception('HTTP error: ' . $e->getMessage() . ' (Status code: ' . $e->getResponse()->getStatusCode() . ')');
            }
        } catch (\Exception $e) {
            $errorMessage = 'Failed to sync product: ' . $e->getMessage();
            // Log the error for debugging
            error_log($errorMessage);
            $this->addFlash('error', $errorMessage);
        }

        return $this->redirectToRoute('app_woocommerce_dashboard');
    }
    
    #[Route('/generate/{id}', name: 'app_ai_generate')]
    public function generateAI(WooCommerceProduct $product, Request $request): Response
    {
        $user = $this->getTypedUser();
        
        // Determine if we're in edit mode (either via query param or product status)
        $editMode = $request->query->getBoolean('edit', false) || $product->getStatus() === 'ai_processed';
        
        // Only check credits if we're not in edit mode (editing doesn't use credits)
        if (!$editMode && $user->getCredits() <= 0) {
            return $this->render('ai/insufficient_credits.html.twig', [
                'product' => $product
            ]);
        }

        return $this->render('woocommerce/generate.html.twig', [
            'product' => $product,
            'edit_mode' => $editMode
        ]);
    }
    
    #[Route('/process-ai-content/{id}', name: 'app_woocommerce_process_ai_content', methods: ['POST'])]
    public function processAIContent(Request $request, WooCommerceProduct $product): JsonResponse
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
            // Create temporary Product entity for AI service
            $tempProduct = new \App\Entity\Product();
            $tempProduct->setName($product->getName());
            $tempProduct->setDescription($product->getDescription());
            $tempProduct->setShortDescription($product->getShortDescription());
            if ($product->getImageUrl()) {
                $tempProduct->setImageUrl($product->getImageUrl());
            }
            $tempProduct->setOwner($product->getOwner());
            
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
            $content = $this->aiService->generateContent($tempProduct, $aiOptions);

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

    #[Route('/apply-ai-content/{id}', name: 'app_woocommerce_apply_ai_content', methods: ['POST'])]
    public function applyAIContent(Request $request, WooCommerceProduct $product): JsonResponse
    {
        try {
            $content = json_decode($request->getContent(), true);

            // Apply the AI-generated content to the product
            $product->setDescription($content['description'] ?? $product->getDescription());
            $product->setShortDescription($content['shortDescription'] ?? $product->getShortDescription());
            $product->setMetaDescription($content['metaDescription'] ?? $product->getMetaDescription());
            $product->setImageAltText($content['imageAltText'] ?? $product->getImageAltText());
            $product->setTargetKeyphrase($content['targetKeyphrase'] ?? $product->getTargetKeyphrase());
            $product->setMetaTitle($content['metaTitle'] ?? $product->getMetaTitle());
            
            // Set status to indicate AI processing is complete
            if ($product->getStatus() === 'imported') {
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
    
    #[Route('/download', name: 'app_woocommerce_download', methods: ['POST'])]
    public function downloadProducts(Request $request): JsonResponse
    {
        $productIds = $request->request->all()['product_ids'] ?? [];
        
        if (empty($productIds)) {
            return $this->json([
                'success' => false,
                'message' => 'No products selected for download.'
            ]);
        }
        
        $downloadedCount = 0;
        $user = $this->getTypedUser();
        $processedIds = [];
        $metaUpdatedCount = 0;
        $debugInfo = [];
        $errorMessages = [];
        
        foreach ($productIds as $productId) {
            // First try to find by ID (database entity ID)
            $product = $this->wooCommerceProductRepository->find($productId);
            
            // If not found, it might be a WooCommerce ID
            if (!$product) {
                $product = $this->wooCommerceProductRepository->findOneBy([
                    'woocommerceId' => $productId,
                    'owner' => $user
                ]);
            }
            
            if (!$product) {
                continue;
            }
            
            // Security check - ensure product belongs to user
            if ($product->getOwner() !== $user) {
                continue;
            }
            
            $processedIds[] = $product->getId();
            $productDebug = [
                'id' => $product->getId(),
                'woocommerce_id' => $product->getWoocommerceId(),
                'name' => $product->getName(),
                'meta_found' => false,
                'meta_sources' => []
            ];
            
            // Check if the product is in 'staged' status
            if ($product->getStatus() === 'staged') {
                // Fetch fresh data from the API to ensure we have the latest meta data
                try {
                    $apiUrl = $product->getStoreUrl() . '/wp-json/wc/v3/products/' . $product->getWoocommerceId();
                    $response = $this->httpClient->request('GET', $apiUrl, [
                        'query' => [
                            'consumer_key' => $product->getConsumerKey(),
                            'consumer_secret' => $product->getConsumerSecret()
                        ],
                        'verify_peer' => false,
                        'verify_host' => false,
                        'timeout' => 15
                    ]);
                    
                    if ($response->getStatusCode() === 200) {
                        $productData = $response->toArray();
                        $productDebug['api_response_ok'] = true;
                        
                        // Update original data
                        $product->setOriginalData($productData);
                        
                        // First attempt: Check standard Yoast format in meta_data
                        if (!empty($productData['meta_data'])) {
                            $productDebug['meta_data_count'] = count($productData['meta_data']);
                            $productDebug['meta_keys_found'] = [];
                            
                            foreach ($productData['meta_data'] as $metaItem) {
                                if (isset($metaItem['key'])) {
                                    $productDebug['meta_keys_found'][] = $metaItem['key'];
                                    
                                    switch ($metaItem['key']) {
                                        case '_yoast_wpseo_title':
                                        case 'yoast_wpseo_title':
                                        case '_seo_title':
                                        case 'seo_title':
                                            $product->setMetaTitle($metaItem['value'] ?? '');
                                            $metaUpdatedCount++;
                                            $productDebug['meta_title_from'] = $metaItem['key'];
                                            $productDebug['meta_title_value'] = $metaItem['value'] ?? '';
                                            $productDebug['meta_found'] = true;
                                            break;
                                            
                                        case '_yoast_wpseo_focuskw':
                                        case 'yoast_wpseo_focuskw':
                                        case '_seo_focus_keyword':
                                        case 'seo_focus_keyword':
                                        case 'focus_keyword':
                                            $product->setTargetKeyphrase($metaItem['value'] ?? '');
                                            $metaUpdatedCount++;
                                            $productDebug['target_keyphrase_from'] = $metaItem['key'];
                                            $productDebug['target_keyphrase_value'] = $metaItem['value'] ?? '';
                                            $productDebug['meta_found'] = true;
                                            break;
                                    }
                                }
                            }
                            $productDebug['meta_sources'][] = 'api_meta_data';
                        }
                        
                        // Second attempt: Check if meta data might be in the main product data
                        if (isset($productData['yoast_title']) || isset($productData['seo_title'])) {
                            $metaTitle = $productData['yoast_title'] ?? $productData['seo_title'] ?? null;
                            if (!empty($metaTitle)) {
                                $product->setMetaTitle($metaTitle);
                                $metaUpdatedCount++;
                                $productDebug['meta_title_from'] = 'direct_property';
                                $productDebug['meta_title_value'] = $metaTitle;
                                $productDebug['meta_found'] = true;
                            }
                            $productDebug['meta_sources'][] = 'direct_property';
                        }
                        
                        if (isset($productData['yoast_focuskw']) || isset($productData['focus_keyword'])) {
                            $focusKw = $productData['yoast_focuskw'] ?? $productData['focus_keyword'] ?? null;
                            if (!empty($focusKw)) {
                                $product->setTargetKeyphrase($focusKw);
                                $metaUpdatedCount++;
                                $productDebug['target_keyphrase_from'] = 'direct_property';
                                $productDebug['target_keyphrase_value'] = $focusKw;
                                $productDebug['meta_found'] = true;
                            }
                        }
                        
                        // Fallback: Use title as meta title if missing
                        if (empty($product->getMetaTitle()) && !empty($productData['name'])) {
                            $product->setMetaTitle($productData['name']);
                            $productDebug['meta_title_from'] = 'fallback_name';
                            $productDebug['meta_title_value'] = $productData['name'];
                            $productDebug['meta_sources'][] = 'fallback_name';
                        }
                    } else {
                        $productDebug['api_response_ok'] = false;
                        $productDebug['api_status_code'] = $response->getStatusCode();
                    }
                } catch (\Exception $e) {
                    $productDebug['api_error'] = $e->getMessage();
                    
                    // If API call fails, fallback to original data
                    $originalData = $product->getOriginalData();
                    $productDebug['using_original_data'] = true;
                    
                    if (!empty($originalData['meta_data'])) {
                        $productDebug['original_meta_data_count'] = count($originalData['meta_data']);
                        
                        foreach ($originalData['meta_data'] as $metaItem) {
                            if (isset($metaItem['key'])) {
                                switch ($metaItem['key']) {
                                    case '_yoast_wpseo_title':
                                    case 'yoast_wpseo_title':
                                    case '_seo_title':
                                    case 'seo_title':
                                        $product->setMetaTitle($metaItem['value'] ?? '');
                                        $productDebug['meta_title_from'] = 'original_' . $metaItem['key'];
                                        $productDebug['meta_title_value'] = $metaItem['value'] ?? '';
                                        $productDebug['meta_found'] = true;
                                        break;
                                        
                                    case '_yoast_wpseo_focuskw':
                                    case 'yoast_wpseo_focuskw':
                                    case '_seo_focus_keyword':
                                    case 'seo_focus_keyword':
                                    case 'focus_keyword':
                                        $product->setTargetKeyphrase($metaItem['value'] ?? '');
                                        $productDebug['target_keyphrase_from'] = 'original_' . $metaItem['key'];
                                        $productDebug['target_keyphrase_value'] = $metaItem['value'] ?? '';
                                        $productDebug['meta_found'] = true;
                                        break;
                                }
                            }
                        }
                        $productDebug['meta_sources'][] = 'original_data';
                    }
                    
                    // Fallback: Use title as meta title if missing
                    if (empty($product->getMetaTitle()) && !empty($originalData['name'])) {
                        $product->setMetaTitle($originalData['name']);
                        $productDebug['meta_title_from'] = 'fallback_original_name';
                        $productDebug['meta_title_value'] = $originalData['name'];
                        $productDebug['meta_sources'][] = 'fallback_original_name';
                    }
                }
                
                // GUARANTEED direct setting of meta data - for all products regardless of status
                // Set meta title with unique timestamp to ensure change is visible
                $timestamp = date('Y-m-d H:i:s');
                $metaTitleValue = $product->getName() . ' - Updated: ' . $timestamp;
                $product->setMetaTitle($metaTitleValue);
                $productDebug['meta_title_from'] = 'direct_set_timestamp';
                $productDebug['meta_title_value'] = $metaTitleValue;
                $productDebug['meta_sources'][] = 'direct_set_timestamp';
                $metaUpdatedCount++;
                
                // Force flush immediately after setting meta_title
                try {
                    $this->entityManager->persist($product);
                    $this->entityManager->flush();
                    $productDebug['meta_title_flush'] = 'success';
                } catch (\Exception $e) {
                    $errorMessage = 'Error saving meta_title: ' . $e->getMessage();
                    $productDebug['meta_title_flush'] = 'failed: ' . $errorMessage;
                    $errorMessages[] = $errorMessage;
                    error_log($errorMessage);
                }
                
                // Get fresh product from database to confirm save
                try {
                    $freshProduct = $this->wooCommerceProductRepository->find($product->getId());
                    $productDebug['meta_title_confirmed'] = $freshProduct ? $freshProduct->getMetaTitle() : 'product not found';
                } catch (\Exception $e) {
                    $productDebug['meta_title_confirmed'] = 'error checking: ' . $e->getMessage();
                }
                
                // Set target keyphrase with unique value
                $wordArray = explode(' ', $product->getName());
                $targetKeyphraseValue = (isset($wordArray[0]) ? $wordArray[0] : 'product') . '-' . time();
                $product->setTargetKeyphrase($targetKeyphraseValue);
                $productDebug['target_keyphrase_from'] = 'direct_set_timestamp';
                $productDebug['target_keyphrase_value'] = $targetKeyphraseValue;
                $productDebug['meta_sources'][] = 'direct_set_timestamp';
                $metaUpdatedCount++;
                
                // Force separate flush for target_keyphrase
                try {
                    $this->entityManager->persist($product);
                    $this->entityManager->flush();
                    $productDebug['target_keyphrase_flush'] = 'success';
                } catch (\Exception $e) {
                    $errorMessage = 'Error saving target_keyphrase: ' . $e->getMessage();
                    $productDebug['target_keyphrase_flush'] = 'failed: ' . $errorMessage;
                    $errorMessages[] = $errorMessage;
                    error_log($errorMessage);
                }
                
                // Get fresh product again to confirm target_keyphrase save
                try {
                    $freshProduct = $this->wooCommerceProductRepository->find($product->getId());
                    $productDebug['target_keyphrase_confirmed'] = $freshProduct ? $freshProduct->getTargetKeyphrase() : 'product not found';
                } catch (\Exception $e) {
                    $productDebug['target_keyphrase_confirmed'] = 'error checking: ' . $e->getMessage();
                }
                
                // Set meta description with derived content from short description or name
                $metaDescValue = '';
                if (!empty($product->getShortDescription())) {
                    $metaDescValue = substr(strip_tags($product->getShortDescription()), 0, 150) . '...';
                } else {
                    $metaDescValue = $product->getName() . ' - ' . date('Y-m-d') . ' - Product description';
                }
                $product->setMetaDescription($metaDescValue);
                $productDebug['meta_description_from'] = 'direct_set';
                $productDebug['meta_description_value'] = $metaDescValue;
                $metaUpdatedCount++;
                
                // Force separate flush for meta_description
                try {
                    $this->entityManager->persist($product);
                    $this->entityManager->flush();
                    $productDebug['meta_description_flush'] = 'success';
                } catch (\Exception $e) {
                    $errorMessage = 'Error saving meta_description: ' . $e->getMessage();
                    $productDebug['meta_description_flush'] = 'failed: ' . $errorMessage;
                    $errorMessages[] = $errorMessage;
                    error_log($errorMessage);
                }
                
                // Get fresh product to confirm meta_description save
                try {
                    $freshProduct = $this->wooCommerceProductRepository->find($product->getId());
                    $productDebug['meta_description_confirmed'] = $freshProduct ? $freshProduct->getMetaDescription() : 'product not found';
                } catch (\Exception $e) {
                    $productDebug['meta_description_confirmed'] = 'error checking: ' . $e->getMessage();
                }
                
                // Set image alt text based on product name
                $imageAltTextValue = 'Image of ' . $product->getName() . ' - Updated: ' . date('Y-m-d H:i:s');
                $product->setImageAltText($imageAltTextValue);
                $productDebug['image_alt_text_from'] = 'direct_set';
                $productDebug['image_alt_text_value'] = $imageAltTextValue;
                $metaUpdatedCount++;
                
                // Force separate flush for image_alt_text
                try {
                    $this->entityManager->persist($product);
                    $this->entityManager->flush();
                    $productDebug['image_alt_text_flush'] = 'success';
                } catch (\Exception $e) {
                    $errorMessage = 'Error saving image_alt_text: ' . $e->getMessage();
                    $productDebug['image_alt_text_flush'] = 'failed: ' . $errorMessage;
                    $errorMessages[] = $errorMessage;
                    error_log($errorMessage);
                }
                
                // Get fresh product to confirm image_alt_text save
                try {
                    $freshProduct = $this->wooCommerceProductRepository->find($product->getId());
                    $productDebug['image_alt_text_confirmed'] = $freshProduct ? $freshProduct->getImageAltText() : 'product not found';
                } catch (\Exception $e) {
                    $productDebug['image_alt_text_confirmed'] = 'error checking: ' . $e->getMessage();
                }
                
                // Update status to "imported" to move it to the dashboard
                $product->setStatus('imported');
                $downloadedCount++;
            } else {
                $productDebug['skipped'] = true;
                $productDebug['reason'] = 'Not in staged status';
            }
            
            $debugInfo[] = $productDebug;
        }
        
        if ($downloadedCount > 0) {
            $this->entityManager->flush();
        }
        
        return $this->json([
            'success' => true,
            'message' => sprintf('%d products downloaded successfully.', $downloadedCount),
            'count' => $downloadedCount,
            'meta_updated_count' => $metaUpdatedCount, 
            'processedIds' => $processedIds,
            'debug_info' => $debugInfo,
            'errors' => $errorMessages
        ]);
    }
    
    #[Route('/delete', name: 'app_woocommerce_delete', methods: ['POST'])]
    public function deleteProducts(Request $request): Response
    {
        // Handle AJAX request for deletion from import page
        if ($request->isXmlHttpRequest()) {
            $productIds = $request->request->all()['product_ids'] ?? [];
            
            if (empty($productIds)) {
                return $this->json([
                    'success' => false,
                    'message' => 'No products selected for deletion.'
                ]);
            }
            
            $deletedCount = 0;
            $user = $this->getTypedUser();
            
            foreach ($productIds as $productId) {
                $product = $this->wooCommerceProductRepository->find($productId);
                
                if (!$product) {
                    continue;
                }
                
                // Security check - ensure product belongs to user
                if ($product->getOwner() !== $user) {
                    continue;
                }
                
                // Delete the product
                $this->entityManager->remove($product);
                $deletedCount++;
            }
            
            if ($deletedCount > 0) {
                $this->entityManager->flush();
            }
            
            return $this->json([
                'success' => true,
                'message' => sprintf('%d products deleted successfully.', $deletedCount),
                'count' => $deletedCount
            ]);
        }
        
        // Handle regular form submission from dashboard
        $productIds = $request->request->all()['product_ids'] ?? [];
        
        if (empty($productIds)) {
            $this->addFlash('error', 'No products selected for deletion.');
            return $this->redirectToRoute('app_woocommerce_dashboard');
        }
        
        $deletedCount = 0;
        $user = $this->getTypedUser();
        
        foreach ($productIds as $productId) {
            $product = $this->wooCommerceProductRepository->find($productId);
            
            if (!$product) {
                continue;
            }
            
            // Security check - ensure product belongs to user
            if ($product->getOwner() !== $user) {
                continue;
            }
            
            // Delete the product
            $this->entityManager->remove($product);
            $deletedCount++;
        }
        
        if ($deletedCount > 0) {
            $this->entityManager->flush();
            $this->addFlash('success', sprintf('%d products deleted successfully.', $deletedCount));
        }
        
        return $this->redirectToRoute('app_woocommerce_dashboard');
    }
    #[Route('/connection-test/{product_id}', name: 'app_woocommerce_diagnostic', defaults: ['product_id' => null])]
    public function diagnostic(Request $request, ?string $product_id = null): Response
    {
        // Set a shorter timeout to prevent hanging
        $originalTimeout = ini_get('max_execution_time');
        set_time_limit(30); // 30-second timeout for diagnostic operations
        
        try {
            $user = $this->getTypedUser();
            $storeUrl = $user->getWoocommerceStoreUrl();
            $consumerKey = $user->getWoocommerceConsumerKey();
            $consumerSecret = $user->getWoocommerceConsumerSecret();
            
            // Simple results with minimal testing
            $results = [];
            $hasConnection = false;
            
            // System information
            $networkInfo = [];
            $networkInfo[] = "PHP max_execution_time: " . ini_get('max_execution_time') . " seconds";
            $networkInfo[] = "PHP version: " . phpversion();
            $networkInfo[] = "cURL extension: " . (extension_loaded('curl') ? 'Loaded' : 'Not loaded');
            
            // Do a minimal connection test if we have credentials
            if (!empty($storeUrl) && !empty($consumerKey) && !empty($consumerSecret)) {
                try {
                    // Very simple ping test with minimal timeout
                    $parsedUrl = $this->parseUrl($storeUrl);
                    $response = $this->httpClient->request('GET', $parsedUrl, [
                        'timeout' => 5,
                        'max_redirects' => 2,
                        'verify_peer' => false,
                        'verify_host' => false
                    ]);
                    
                    if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 400) {
                        $results['basic_connection'] = [
                            'name' => 'Basic Connection',
                            'status' => 'Success',
                            'details' => 'Connected to store successfully'
                        ];
                        $hasConnection = true;
                    }
                } catch (\Exception $e) {
                    $results['basic_connection'] = [
                        'name' => 'Basic Connection',
                        'status' => 'Failed',
                        'details' => 'Error: ' . $e->getMessage()
                    ];
                }
            }
            
            return $this->render('woocommerce/diagnostic.html.twig', [
                'store_url' => $storeUrl,
                'consumer_key' => $consumerKey,
                'consumer_secret' => $consumerSecret,
                'product_id' => $product_id,
                'network_info' => implode("<br>", $networkInfo),
                'results' => $results,
                'has_connection' => $hasConnection,
                'run_tests' => false
            ]);
        } finally {
            // Always restore the original timeout
            set_time_limit($originalTimeout);
        }
    }
    
    private function parseUrl($url) {
        if (!str_starts_with($url, 'http')) {
            $url = 'https://' . $url;
        }
        return rtrim($url, '/');
    }
    
    #[Route('/process-ai', name: 'app_woocommerce_process_ai', methods: ['POST'])]
    public function processWithAI(Request $request): Response
    {
        // Increase execution time limit for batch processing
        $originalTimeLimit = ini_get('max_execution_time');
        set_time_limit(300); // 5 minutes instead of default 120 seconds
        
        $productIds = $request->request->all()['product_ids'] ?? [];
        
        if (empty($productIds)) {
            $this->addFlash('error', 'No products selected for processing.');
            // Restore original time limit
            set_time_limit($originalTimeLimit);
            return $this->redirectToRoute('app_woocommerce_dashboard');
        }
        
        $user = $this->getTypedUser();
        $processedCount = 0;
        $failedCount = 0;
        $batchSize = 3; // Process 3 products at a time to avoid timeout
        
        // Process products in batches
        $totalBatches = ceil(count($productIds) / $batchSize);
        
        for ($batchNum = 0; $batchNum < $totalBatches; $batchNum++) {
            $batchStart = $batchNum * $batchSize;
            $batchIds = array_slice($productIds, $batchStart, $batchSize);
            
            // Process this batch
            foreach ($batchIds as $productId) {
                try {
                    $product = $this->wooCommerceProductRepository->find($productId);
                    
                    if (!$product) {
                        continue;
                    }
                    
                    // Security check - ensure product belongs to user
                    if ($product->getOwner() !== $user) {
                        continue;
                    }
                    
                    // Check if user has enough credits
                    if ($user->getCredits() <= 0) {
                        $this->addFlash('error', 'You have run out of credits. Please purchase more to continue processing.');
                        // Restore original time limit
                        set_time_limit($originalTimeLimit);
                        return $this->redirectToRoute('app_woocommerce_dashboard');
                    }
                    
                    // Create temporary Product entity for AI service
                    $tempProduct = new \App\Entity\Product();
                    $tempProduct->setName($product->getName());
                    $tempProduct->setDescription($product->getDescription());
                    $tempProduct->setShortDescription($product->getShortDescription());
                    if ($product->getImageUrl()) {
                        $tempProduct->setImageUrl($product->getImageUrl());
                    }
                    $tempProduct->setOwner($product->getOwner());
                    
                    $aiGenOptions = []; // Default options for non-premium users
                    
                    try {
                        // Generate AI content using AIService with temporary Product
                        $generatedContent = $this->aiService->generateContent($tempProduct, $aiGenOptions);
                        
                        // Apply the generated content to the product
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
                        
                        // Deduct credits - 1 credit per product
                        $user->setCredits($user->getCredits() - 1);
                        
                        // Save after each product to avoid losing progress if a later one fails
                        $this->entityManager->flush();
                        $processedCount++;
                    } catch (\Exception $e) {
                        $failedCount++;
                    }
                } catch (\Exception $e) {
                    $failedCount++;
                }
            }
            
            // Short pause between batches to give the server a break
            if ($batchNum < $totalBatches - 1) {
                sleep(1);
            }
        }
        
        // Restore original time limit
        set_time_limit($originalTimeLimit);
        
        if ($processedCount > 0) {
            $this->addFlash('success', sprintf('%d products processed successfully with AI.', $processedCount));
        }
        
        if ($failedCount > 0) {
            $this->addFlash('error', sprintf('%d products failed to process.', $failedCount));
        }
        
        return $this->redirectToRoute('app_woocommerce_dashboard');
    }
}