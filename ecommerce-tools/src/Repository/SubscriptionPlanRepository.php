<?php

namespace App\Repository;

use App\Entity\SubscriptionPlan;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SubscriptionPlan>
 *
 * @method SubscriptionPlan|null find($id, $lockMode = null, $lockVersion = null)
 * @method SubscriptionPlan|null findOneBy(array $criteria, array $orderBy = null)
 * @method SubscriptionPlan[]    findAll()
 * @method SubscriptionPlan[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SubscriptionPlanRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SubscriptionPlan::class);
    }

    /**
     * Find a plan by its identifier/code
     */
    public function findByIdentifier(string $identifier): ?SubscriptionPlan
    {
        return $this->findOneBy(['identifier' => $identifier]);
    }

    /**
     * Get all active plans ordered by display order
     */
    public function findActivePlans(): array
    {
        return $this->findBy(
            ['isActive' => true],
            ['displayOrder' => 'ASC']
        );
    }

    /**
     * Get featured plans
     */
    public function findFeaturedPlans(): array
    {
        return $this->findBy(
            ['isActive' => true, 'isFeatured' => true],
            ['displayOrder' => 'ASC']
        );
    }

    /**
     * Save entity to the database
     */
    public function save(SubscriptionPlan $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Remove entity from the database
     */
    public function remove(SubscriptionPlan $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Get subscription plan data as an array for templates
     * 
     * @return array<string, array>
     */
    public function getPlansAsArray(): array
    {
        $plans = $this->findActivePlans();
        $result = [];

        foreach ($plans as $plan) {
            $result[$plan->getIdentifier()] = $plan->toArray();
        }

        return $result;
    }
}