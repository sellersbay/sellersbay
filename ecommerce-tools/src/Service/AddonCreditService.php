<?php

namespace App\Service;

use App\Entity\AddonCredit;
use App\Entity\PackageAddOn;
use App\Entity\Transaction;
use App\Entity\User;
use App\Repository\AddonCreditRepository;
use App\Repository\TransactionRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Service for managing addon credits
 */
class AddonCreditService
{
    private EntityManagerInterface $entityManager;
    private AddonCreditRepository $addonCreditRepository;
    private TransactionRepository $transactionRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        AddonCreditRepository $addonCreditRepository,
        TransactionRepository $transactionRepository
    ) {
        $this->entityManager = $entityManager;
        $this->addonCreditRepository = $addonCreditRepository;
        $this->transactionRepository = $transactionRepository;
    }

    /**
     * Add addon credits to a user from a package purchase
     * 
     * @param User $user
     * @param PackageAddOn $package
     * @param float $purchasePrice
     * @param string|null $stripePaymentId
     * @return AddonCredit
     */
    public function addCreditsFromPackage(User $user, PackageAddOn $package, float $purchasePrice, ?string $stripePaymentId = null): AddonCredit
    {
        // Create addon credit record
        $addonCredit = AddonCredit::createFromPackage($user, $package, $purchasePrice, $stripePaymentId);
        
        // Add to user's addon credits collection
        $user->addAddonCredit($addonCredit);
        
        // Record the transaction
        $transaction = Transaction::createAddonCreditPurchase(
            $user,
            $purchasePrice,
            $package->getCredits(),
            $package->getName(),
            $stripePaymentId
        );
        
        // Save to database
        $this->entityManager->persist($addonCredit);
        $this->entityManager->persist($transaction);
        $this->entityManager->flush();
        
        return $addonCredit;
    }

    /**
     * Add addon credits to a user from an admin adjustment
     * 
     * @param User $user
     * @param int $amount
     * @param string $notes
     * @return AddonCredit
     */
    public function addCreditsFromAdjustment(User $user, int $amount, string $notes): AddonCredit
    {
        // Create addon credit record
        $addonCredit = AddonCredit::createFromAdjustment($user, $amount, $notes);
        
        // Add to user's addon credits collection
        $user->addAddonCredit($addonCredit);
        
        // Record the transaction
        $transaction = Transaction::createAdminAdjustment($user, $amount, $notes);
        
        // Save to database
        $this->entityManager->persist($addonCredit);
        $this->entityManager->persist($transaction);
        $this->entityManager->flush();
        
        return $addonCredit;
    }

    /**
     * Use addon credits for a user
     * 
     * @param User $user
     * @param int $amount
     * @param string $description
     * @return bool True if successful, false if not enough credits
     */
    public function useAddonCredits(User $user, int $amount, string $description): bool
    {
        // Get total addon credits available
        $totalAddonCredits = $user->getTotalAddonCredits();
        
        if ($totalAddonCredits < $amount) {
            return false;
        }
        
        // Get active addon credits, sorted by oldest first
        $activeAddons = $this->addonCreditRepository->findActiveForUser($user);
        
        usort($activeAddons, function(AddonCredit $a, AddonCredit $b) {
            return $a->getCreatedAt() <=> $b->getCreatedAt();
        });
        
        // Use credits from oldest to newest
        $remainingToUse = $amount;
        foreach ($activeAddons as $addonCredit) {
            $available = $addonCredit->getRemainingAmount();
            
            if ($available >= $remainingToUse) {
                // Use part of this credit package
                $addonCredit->useCredits($remainingToUse);
                $remainingToUse = 0;
                break;
            } else {
                // Use all of this credit package and continue
                $addonCredit->useCredits($available);
                $remainingToUse -= $available;
            }
        }
        
        // Record the transaction
        $transaction = Transaction::createAddonCreditUsage($user, $amount, $description);
        $this->entityManager->persist($transaction);
        
        // Save changes
        $this->entityManager->flush();
        
        return true;
    }

    /**
     * Get total addon credits for a user
     * 
     * @param User $user
     * @return int
     */
    public function getTotalAddonCredits(User $user): int
    {
        return $this->addonCreditRepository->getTotalRemainingForUser($user);
    }

    /**
     * Get all active addon credits for a user
     * 
     * @param User $user
     * @return array<AddonCredit>
     */
    public function getActiveAddonCredits(User $user): array
    {
        return $this->addonCreditRepository->findActiveForUser($user);
    }
    
    /**
     * Get all addon credit transactions for a user
     * 
     * @param User $user
     * @return array<Transaction>
     */
    public function getAddonCreditTransactions(User $user): array
    {
        return $this->transactionRepository->findBy([
            'user' => $user,
            'type' => [
                Transaction::TYPE_ADDON_CREDIT_PURCHASE,
                Transaction::TYPE_ADDON_CREDIT_USAGE
            ]
        ], ['createdAt' => 'DESC']);
    }
}