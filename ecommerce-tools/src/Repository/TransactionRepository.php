<?php

namespace App\Repository;

use App\Entity\Transaction;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Transaction>
 *
 * @method Transaction|null find($id, $lockMode = null, $lockVersion = null)
 * @method Transaction|null findOneBy(array $criteria, array $orderBy = null)
 * @method Transaction[]    findAll()
 * @method Transaction[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TransactionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Transaction::class);
    }

    public function save(Transaction $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Transaction $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Get revenue data by month for the last X months
     *
     * @param int $numberOfMonths Number of months to include
     * @return array Array of revenue data by month
     */
    public function getRevenueByMonth(int $numberOfMonths = 12): array
    {
        $now = new \DateTime();
        $results = [];
        
        // Get months in reverse (newest to oldest)
        for ($i = 0; $i < $numberOfMonths; $i++) {
            $endDate = clone $now;
            $endDate->modify('-' . $i . ' month');
            $endDate->modify('last day of this month');
            $endDate->setTime(23, 59, 59);
            
            $startDate = clone $endDate;
            $startDate->modify('first day of this month');
            $startDate->setTime(0, 0, 0);
            
            // Get revenue from transactions
            $revenueAmount = $this->createQueryBuilder('t')
                ->select('SUM(t.amount)')
                ->where('t.createdAt >= :startDate')
                ->andWhere('t.createdAt <= :endDate')
                ->andWhere('t.type IN (:types)')
                ->setParameter('startDate', $startDate)
                ->setParameter('endDate', $endDate)
                ->setParameter('types', [
                    Transaction::TYPE_CREDIT_PURCHASE, 
                    Transaction::TYPE_SUBSCRIPTION_PAYMENT
                ])
                ->getQuery()
                ->getSingleScalarResult() ?? 0;
            
            $monthName = $endDate->format('M');
            
            // Store results with newest month last
            $results[$numberOfMonths - 1 - $i] = [
                'month' => $monthName,
                'amount' => (float)$revenueAmount
            ];
        }
        
        return $results;
    }
    
    /**
     * Get revenue breakdown by type
     *
     * @return array Revenue breakdown percentages
     */
    public function getRevenueBreakdown(): array
    {
        // Calculate total revenue
        $totalRevenue = $this->createQueryBuilder('t')
            ->select('SUM(t.amount)')
            ->where('t.amount > 0')
            ->getQuery()
            ->getSingleScalarResult() ?? 0;
        
        // Early return to avoid division by zero
        if ($totalRevenue == 0) {
            return [
                'credit_purchase' => 0,
                'subscription' => 0,
                'other' => 0
            ];
        }
        
        // Get revenue from credit purchases
        $creditPurchaseRevenue = $this->createQueryBuilder('t')
            ->select('SUM(t.amount)')
            ->where('t.type = :type')
            ->setParameter('type', Transaction::TYPE_CREDIT_PURCHASE)
            ->getQuery()
            ->getSingleScalarResult() ?? 0;
        
        // Get revenue from subscriptions
        $subscriptionRevenue = $this->createQueryBuilder('t')
            ->select('SUM(t.amount)')
            ->where('t.type = :type')
            ->setParameter('type', Transaction::TYPE_SUBSCRIPTION_PAYMENT)
            ->getQuery()
            ->getSingleScalarResult() ?? 0;
        
        // Calculate percentages
        $creditPurchasePercentage = round(($creditPurchaseRevenue / $totalRevenue) * 100);
        $subscriptionPercentage = round(($subscriptionRevenue / $totalRevenue) * 100);
        $otherPercentage = 100 - $creditPurchasePercentage - $subscriptionPercentage;
        
        return [
            'credit_purchase' => $creditPurchasePercentage,
            'subscription' => $subscriptionPercentage,
            'other' => max(0, $otherPercentage) // Ensure non-negative
        ];
    }
    
    /**
     * Get monthly credit usage data
     *
     * @param int $numberOfMonths Number of months to include
     * @return array Array of credit usage data by month
     */
    public function getCreditUsageByMonth(int $numberOfMonths = 12): array
    {
        $now = new \DateTime();
        $results = [];
        
        // Get months in reverse (newest to oldest)
        for ($i = 0; $i < $numberOfMonths; $i++) {
            $endDate = clone $now;
            $endDate->modify('-' . $i . ' month');
            $endDate->modify('last day of this month');
            $endDate->setTime(23, 59, 59);
            
            $startDate = clone $endDate;
            $startDate->modify('first day of this month');
            $startDate->setTime(0, 0, 0);
            
            // Get credit usage
            $usedCredits = $this->createQueryBuilder('t')
                ->select('SUM(t.credits)')
                ->where('t.createdAt >= :startDate')
                ->andWhere('t.createdAt <= :endDate')
                ->andWhere('t.type = :type')
                ->setParameter('startDate', $startDate)
                ->setParameter('endDate', $endDate)
                ->setParameter('type', Transaction::TYPE_CREDIT_USAGE)
                ->getQuery()
                ->getSingleScalarResult() ?? 0;
            
            $monthName = $endDate->format('M');
            
            // Store results with newest month last
            $results[$numberOfMonths - 1 - $i] = [
                'month' => $monthName,
                'used' => (int)$usedCredits
            ];
        }
        
        return $results;
    }
    
    /**
     * Get total revenue within a date range
     *
     * @param \DateTimeInterface $startDate Start date
     * @param \DateTimeInterface $endDate End date
     * @return float Total revenue
     */
    public function getTotalRevenueBetween(\DateTimeInterface $startDate, \DateTimeInterface $endDate): float
    {
        return (float)$this->createQueryBuilder('t')
            ->select('SUM(t.amount)')
            ->where('t.createdAt >= :startDate')
            ->andWhere('t.createdAt <= :endDate')
            ->andWhere('t.type IN (:types)')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->setParameter('types', [
                Transaction::TYPE_CREDIT_PURCHASE, 
                Transaction::TYPE_SUBSCRIPTION_PAYMENT
            ])
            ->getQuery()
            ->getSingleScalarResult() ?? 0;
    }
    
    /**
     * Get total credit usage within a date range
     *
     * @param \DateTimeInterface $startDate Start date
     * @param \DateTimeInterface $endDate End date
     * @return int Total credits used
     */
    public function getTotalCreditsUsedBetween(\DateTimeInterface $startDate, \DateTimeInterface $endDate): int
    {
        return (int)$this->createQueryBuilder('t')
            ->select('SUM(t.credits)')
            ->where('t.createdAt >= :startDate')
            ->andWhere('t.createdAt <= :endDate')
            ->andWhere('t.type = :type')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->setParameter('type', Transaction::TYPE_CREDIT_USAGE)
            ->getQuery()
            ->getSingleScalarResult() ?? 0;
    }
    
    /**
     * Get recent transactions
     *
     * @param int $limit Maximum number of transactions to return
     * @return Transaction[] Array of recent transactions
     */
    public function findRecentTransactions(int $limit = 5): array
    {
        return $this->createQueryBuilder('t')
            ->orderBy('t.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
    
    /**
     * Find users who have made the most purchases
     *
     * @param int $limit Maximum number of users to return
     * @return array Array of user data with purchase stats
     */
    public function findTopSpendingUsers(int $limit = 5): array
    {
        $results = $this->createQueryBuilder('t')
            ->select('IDENTITY(t.user) as userId, SUM(t.amount) as totalSpent')
            ->where('t.amount > 0')
            ->groupBy('t.user')
            ->orderBy('totalSpent', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
        
        return $results;
    }
}