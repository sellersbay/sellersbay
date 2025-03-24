<?php

namespace App\Repository;

use App\Entity\PackageAddOn;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PackageAddOn>
 *
 * @method PackageAddOn|null find($id, $lockMode = null, $lockVersion = null)
 * @method PackageAddOn|null findOneBy(array $criteria, array $orderBy = null)
 * @method PackageAddOn[]    findAll()
 * @method PackageAddOn[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PackageAddOnRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PackageAddOn::class);
    }

    public function save(PackageAddOn $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(PackageAddOn $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return array<string, array> Get all active add-on packages as an array
     */
    public function getAddOnPackagesAsArray(): array
    {
        $addOns = $this->findBy(['isActive' => true], ['displayOrder' => 'ASC']);
        
        $result = [];
        foreach ($addOns as $addOn) {
            $result[$addOn->getIdentifier()] = $addOn->toArray();
        }
        
        return $result;
    }

    /**
     * @return PackageAddOn[] Returns all active packages sorted by display order
     */
    public function findActivePackages(): array
    {
        return $this->findBy(['isActive' => true], ['displayOrder' => 'ASC']);
    }

    /**
     * @return PackageAddOn[] Returns featured packages sorted by display order
     */
    public function findFeaturedPackages(): array
    {
        return $this->findBy(['isActive' => true, 'isFeatured' => true], ['displayOrder' => 'ASC']);
    }
}