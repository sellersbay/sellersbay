<?php

namespace App\Repository;

use App\Entity\Product;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Product>
 *
 * @method Product|null find($id, $lockMode = null, $lockVersion = null)
 * @method Product|null findOneBy(array $criteria, array $orderBy = null)
 * @method Product[]    findAll()
 * @method Product[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    public function save(Product $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Product $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Find products by status
     *
     * @return Product[]
     */
    public function findByStatus(string $status): array
    {
        return $this->findBy(['status' => $status]);
    }

    /**
     * Find products owned by a specific user
     *
     * @return Product[]
     */
    public function findByOwner(User $owner): array
    {
        return $this->findBy(['owner' => $owner]);
    }

    /**
     * Find products that need AI processing
     *
     * @return Product[]
     */
    public function findProductsNeedingAiProcessing(): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.status = :status')
            ->andWhere('p.aiGeneratedContent IS NULL')
            ->setParameter('status', 'pending_ai')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find products by owner and status
     *
     * @return Product[]
     */
    public function findByOwnerAndStatus(User $owner, string $status): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.owner = :owner')
            ->andWhere('p.status = :status')
            ->setParameter('owner', $owner)
            ->setParameter('status', $status)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find recently processed products
     *
     * @return Product[]
     */
    public function findRecentlyProcessed(int $limit = 10): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.status = :status')
            ->setParameter('status', 'processed')
            ->orderBy('p.updatedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}