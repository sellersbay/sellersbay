<?php

namespace App\Service;

use App\Entity\WooCommerceProduct;
use Doctrine\ORM\EntityManagerInterface;

class SeoStatusService
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Calculate and update the SEO status for a product
     */
    public function updateSeoStatus(WooCommerceProduct $product): void
    {
        $seoStatus = $this->calculateSeoStatus($product);
        $product->setSeoStatus($seoStatus);
        
        $this->entityManager->persist($product);
        $this->entityManager->flush();
    }
    
    /**
     * Calculate SEO status for multiple products
     */
    public function updateSeoStatusBatch(array $products): void
    {
        foreach ($products as $product) {
            $seoStatus = $this->calculateSeoStatus($product);
            $product->setSeoStatus($seoStatus);
            $this->entityManager->persist($product);
        }
        
        $this->entityManager->flush();
    }

    /**
     * Calculate the SEO status based on product fields
     * @return string 'optimized' or 'needs_improvement'
     */
    public function calculateSeoStatus(WooCommerceProduct $product): string
    {
        // Check for essential SEO fields with strict empty checking
        $metaDesc = $product->getMetaDescription();
        $hasMetaDescription = $metaDesc !== null && trim($metaDesc) !== '';
        
        $shortDesc = $product->getShortDescription();
        $hasShortDescription = $shortDesc !== null && trim($shortDesc) !== '';
        
        $altText = $product->getImageAltText();
        $hasImageAltText = $altText !== null && trim($altText) !== '';
        
        // Check for detailed description (at least 50 characters)
        $description = $product->getDescription();
        $hasDetailedDescription = $description !== null && trim($description) !== '' && strlen(strip_tags($description)) > 50;
        
        // Check for target keyphrase (but don't include in scoring yet)
        $keyphrase = $product->getTargetKeyphrase();
        $hasTargetKeyphrase = $keyphrase !== null && trim($keyphrase) !== '';
        
        // Debug info for each product's SEO status
        $productInfo = sprintf(
            'Product #%d "%s": metaDesc=%s [%s], shortDesc=%s, imageAlt=%s, detailedDesc=%s, keyphrase=%s',
            $product->getId() ?? 0,
            $product->getName(),
            $hasMetaDescription ? 'yes' : 'no',
            $metaDesc ? (strlen($metaDesc) > 30 ? substr($metaDesc, 0, 30) . '...' : $metaDesc) : 'NULL',
            $hasShortDescription ? 'yes' : 'no',
            $hasImageAltText ? 'yes' : 'no',
            $hasDetailedDescription ? 'yes' : 'no',
            $hasTargetKeyphrase ? 'yes' : 'no'
        );
        
        // Calculate SEO score - specifically focusing on the fields mentioned by user
        $seoScore = 0;
        if ($hasMetaDescription) $seoScore++;
        if ($hasShortDescription) $seoScore++;
        if ($hasImageAltText) $seoScore++;
        if ($hasDetailedDescription) $seoScore++;
        
        // Consider optimized if it has at least 3 out of 4 criteria
        $isOptimized = ($seoScore >= 3);
        $status = $isOptimized ? 'optimized' : 'needs_improvement';
        
        // Add conclusion to debug log
        $productInfo .= sprintf(' => Score: %d/4 => Status: %s', $seoScore, $status);
        error_log($productInfo);
        
        return $status;
    }
    
    /**
     * Run a batch update for all products
     */
    public function updateAllProductsSeoStatus(): int
    {
        $repository = $this->entityManager->getRepository(WooCommerceProduct::class);
        $products = $repository->findAll();
        
        $count = 0;
        foreach ($products as $product) {
            $seoStatus = $this->calculateSeoStatus($product);
            $product->setSeoStatus($seoStatus);
            $this->entityManager->persist($product);
            $count++;
            
            // Flush in batches to avoid memory issues
            if ($count % 50 === 0) {
                $this->entityManager->flush();
            }
        }
        
        $this->entityManager->flush();
        return $count;
    }
} 