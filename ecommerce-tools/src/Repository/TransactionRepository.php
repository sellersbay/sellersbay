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
     * Get revenue data by month for the last X months - OPTIMIZED VERSION
     *
     * @param int $numberOfMonths Number of months to include
     * @return array Array of revenue data by month
     */
    public function getRevenueByMonth(int $numberOfMonths = 12): array
    {
        // Calculate date range
        $endDate = new \DateTime();
        $startDate = clone $endDate;
        $startDate->modify('-' . ($numberOfMonths - 1) . ' months');
        $startDate->modify('first day of this month');
        $startDate->setTime(0, 0, 0);
        
        // Get all monthly revenue in a single query
        $query = $this->createQueryBuilder('t')
            ->select("DATE_FORMAT(t.createdAt, '%Y-%m') as yearMonth")
            ->addSelect("DATE_FORMAT(t.createdAt, '%b') as month")
            ->addSelect('SUM(t.amount) as amount')
            ->where('t.createdAt >= :startDate')
            ->andWhere('t.createdAt <= :endDate')
            ->andWhere('t.type IN (:types)')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->setParameter('types', [
                Transaction::TYPE_CREDIT_PURCHASE, 
                Transaction::TYPE_SUBSCRIPTION_PAYMENT
            ])
            ->groupBy('yearMonth')
            ->orderBy('yearMonth', 'ASC')
            ->getQuery();
        
        $results = $query->getResult();
        
        // Create an array of all months in the range (including those with zero revenue)
        $formattedResults = [];
        $currentDate = clone $startDate;
        
        for ($i = 0; $i < $numberOfMonths; $i++) {
            $yearMonth = $currentDate->format('Y-m');
            $monthName = $currentDate->format('M');
            
            // Default to zero revenue
            $monthAmount = 0;
            
            // Try to find actual revenue for this month
            foreach ($results as $result) {
                if ($result['yearMonth'] === $yearMonth) {
                    $monthAmount = (float) $result['amount'];
                    break;
                }
            }
            
            $formattedResults[$i] = [
                'month' => $monthName,
                'amount' => $monthAmount
            ];
            
            // Move to next month
            $currentDate->modify('+1 month');
        }
        
        return $formattedResults;
    }
    
    /**
     * Get revenue breakdown by type - OPTIMIZED VERSION
     *
     * @return array Revenue breakdown percentages
     */
    public function getRevenueBreakdown(): array
    {
        // Get all revenue data in a single query with CASE expressions
        $result = $this->createQueryBuilder('t')
            ->select('SUM(t.amount) as total_revenue')
            ->addSelect('SUM(CASE WHEN t.type = :credit_type THEN t.amount ELSE 0 END) as credit_purchase_revenue')
            ->addSelect('SUM(CASE WHEN t.type = :subscription_type THEN t.amount ELSE 0 END) as subscription_revenue')
            ->where('t.amount > 0')
            ->setParameter('credit_type', Transaction::TYPE_CREDIT_PURCHASE)
            ->setParameter('subscription_type', Transaction::TYPE_SUBSCRIPTION_PAYMENT)
            ->getQuery()
            ->getOneOrNullResult();
        
        // Early return to avoid division by zero
        $totalRevenue = (float)($result['total_revenue'] ?? 0);
        if ($totalRevenue == 0) {
            return [
                'credit_purchase' => 0,
                'subscription' => 0,
                'other' => 0
            ];
        }
        
        // Calculate percentages
        $creditPurchaseRevenue = (float)($result['credit_purchase_revenue'] ?? 0);
        $subscriptionRevenue = (float)($result['subscription_revenue'] ?? 0);
        
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
     * Get monthly credit usage data - OPTIMIZED VERSION
     *
     * @param int $numberOfMonths Number of months to include
     * @return array Array of credit usage data by month
     */
    public function getCreditUsageByMonth(int $numberOfMonths = 12): array
    {
        // Calculate date range
        $endDate = new \DateTime();
        $startDate = clone $endDate;
        $startDate->modify('-' . ($numberOfMonths - 1) . ' months');
        $startDate->modify('first day of this month');
        $startDate->setTime(0, 0, 0);
        
        // Get all monthly credit usage in a single query
        $query = $this->createQueryBuilder('t')
            ->select("DATE_FORMAT(t.createdAt, '%Y-%m') as yearMonth")
            ->addSelect("DATE_FORMAT(t.createdAt, '%b') as month")
            ->addSelect('SUM(t.credits) as used_credits')
            ->where('t.createdAt >= :startDate')
            ->andWhere('t.createdAt <= :endDate')
            ->andWhere('t.type = :type')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->setParameter('type', Transaction::TYPE_CREDIT_USAGE)
            ->groupBy('yearMonth')
            ->orderBy('yearMonth', 'ASC')
            ->getQuery();
        
        $results = $query->getResult();
        
        // Create an array of all months in the range (including those with zero usage)
        $formattedResults = [];
        $currentDate = clone $startDate;
        
        for ($i = 0; $i < $numberOfMonths; $i++) {
            $yearMonth = $currentDate->format('Y-m');
            $monthName = $currentDate->format('M');
            
            // Default to zero usage
            $monthUsage = 0;
            
            // Try to find actual usage for this month
            foreach ($results as $result) {
                if ($result['yearMonth'] === $yearMonth) {
                    $monthUsage = (int) $result['used_credits'];
                    break;
                }
            }
            
            $formattedResults[$i] = [
                'month' => $monthName,
                'used' => $monthUsage
            ];
            
            // Move to next month
            $currentDate->modify('+1 month');
        }
        
        return $formattedResults;
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