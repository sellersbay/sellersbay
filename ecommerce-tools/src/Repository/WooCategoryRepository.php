<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\WooCategory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WooCategory>
 */
class WooCategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WooCategory::class);
    }

    /**
     * Find categories by owner and store URL
     */
    public function findByOwnerAndStore(User $owner, string $storeUrl): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.owner = :owner')
            ->andWhere('c.storeUrl = :storeUrl')
            ->setParameter('owner', $owner)
            ->setParameter('storeUrl', $storeUrl)
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Check if category exists by WooCommerce ID, owner, and store URL
     */
    public function findOneByWooCommerceIdAndOwner(int $woocommerceId, User $owner, string $storeUrl): ?WooCategory
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.woocommerceId = :woocommerceId')
            ->andWhere('c.owner = :owner')
            ->andWhere('c.storeUrl = :storeUrl')
            ->setParameter('woocommerceId', $woocommerceId)
            ->setParameter('owner', $owner)
            ->setParameter('storeUrl', $storeUrl)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Delete all categories for a user and store
     */
    public function deleteAllForUserAndStore(User $owner, string $storeUrl): int
    {
        return $this->createQueryBuilder('c')
            ->delete()
            ->andWhere('c.owner = :owner')
            ->andWhere('c.storeUrl = :storeUrl')
            ->setParameter('owner', $owner)
            ->setParameter('storeUrl', $storeUrl)
            ->getQuery()
            ->execute();
    }

    /**
     * Save a new category
     */
    public function save(WooCategory $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Remove a category
     */
    public function remove(WooCategory $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}