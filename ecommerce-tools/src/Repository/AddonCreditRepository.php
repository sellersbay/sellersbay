<?php

namespace App\Repository;

use App\Entity\AddonCredit;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AddonCredit>
 *
 * @method AddonCredit|null find($id, $lockMode = null, $lockVersion = null)
 * @method AddonCredit|null findOneBy(array $criteria, array $orderBy = null)
 * @method AddonCredit[]    findAll()
 * @method AddonCredit[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AddonCreditRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AddonCredit::class);
    }

    /**
     * Find all active addon credits for a user
     * 
     * @param User $user
     * @return array<AddonCredit>
     */
    public function findActiveForUser(User $user): array
    {
        return $this->createQueryBuilder('ac')
            ->andWhere('ac.user = :user')
            ->andWhere('ac.isActive = :isActive')
            ->andWhere('ac.remainingAmount > 0')
            ->setParameter('user', $user)
            ->setParameter('isActive', true)
            ->orderBy('ac.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get total remaining addon credits for a user
     * 
     * @param User $user
     * @return int
     */
    public function getTotalRemainingForUser(User $user): int
    {
        $result = $this->createQueryBuilder('ac')
            ->select('SUM(ac.remainingAmount) as total')
            ->andWhere('ac.user = :user')
            ->andWhere('ac.isActive = :isActive')
            ->setParameter('user', $user)
            ->setParameter('isActive', true)
            ->getQuery()
            ->getSingleResult();
        
        return (int)($result['total'] ?? 0);
    }

    /**
     * Find addon credits by package name
     * 
     * @param string $packageName
     * @return array<AddonCredit>
     */
    public function findByPackageName(string $packageName): array
    {
        return $this->createQueryBuilder('ac')
            ->andWhere('ac.packageName = :packageName')
            ->setParameter('packageName', $packageName)
            ->orderBy('ac.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find unused addon credits
     * 
     * @return array<AddonCredit>
     */
    public function findUnused(): array
    {
        return $this->createQueryBuilder('ac')
            ->andWhere('ac.isActive = :isActive')
            ->andWhere('ac.remainingAmount = ac.amount')
            ->setParameter('isActive', true)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find partially used addon credits
     * 
     * @return array<AddonCredit>
     */
    public function findPartiallyUsed(): array
    {
        return $this->createQueryBuilder('ac')
            ->andWhere('ac.isActive = :isActive')
            ->andWhere('ac.remainingAmount < ac.amount')
            ->andWhere('ac.remainingAmount > 0')
            ->setParameter('isActive', true)
            ->getQuery()
            ->getResult();
    }

    /**
     * Get total revenue from addon credits
     * 
     * @return float
     */
    public function getTotalRevenue(): float
    {
        $result = $this->createQueryBuilder('ac')
            ->select('SUM(ac.purchasePrice) as total')
            ->getQuery()
            ->getSingleResult();
        
        return (float)($result['total'] ?? 0);
    }

    /**
     * Save an addon credit entry to the database
     * 
     * @param AddonCredit $addonCredit
     * @param bool $flush Whether to flush changes immediately
     */
    public function save(AddonCredit $addonCredit, bool $flush = false): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist($addonCredit);
        
        if ($flush) {
            $entityManager->flush();
        }
    }

    /**
     * Remove an addon credit entry from the database
     * 
     * @param AddonCredit $addonCredit
     * @param bool $flush Whether to flush changes immediately
     */
    public function remove(AddonCredit $addonCredit, bool $flush = false): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->remove($addonCredit);
        
        if ($flush) {
            $entityManager->flush();
        }
    }
}