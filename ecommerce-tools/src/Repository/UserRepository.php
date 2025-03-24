<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 *
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function save(User $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(User $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', \get_class($user)));
        }

        $user->setPassword($newHashedPassword);
        $this->save($user, true);
    }

    public function findByEmail(string $email): ?User
    {
        return $this->findOneBy(['email' => $email]);
    }

    /**
     * @return User[]
     */
    public function findBySubscriptionTier(string $tier): array
    {
        return $this->findBy(['subscriptionTier' => $tier]);
    }

    /**
     * Find users with credits below a certain threshold
     */
    public function findUsersWithLowCredits(int $threshold = 10): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.credits < :threshold')
            ->setParameter('threshold', $threshold)
            ->getQuery()
            ->getResult();
    }
    
    /**
     * Count users that have a specific role
     */
    public function countUsersByRole(string $role): int
    {
        return $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.roles LIKE :role')
            ->setParameter('role', '%"'.$role.'"%')
            ->getQuery()
            ->getSingleScalarResult();
    }
    
    /**
     * Get the sum of all credits across all users
     */
    public function getTotalCredits(): int
    {
        return (int)$this->createQueryBuilder('u')
            ->select('SUM(u.credits)')
            ->getQuery()
            ->getSingleScalarResult() ?? 0;
    }

    /**
     * Get the number of users who registered within a specific date range
     */
    public function countUsersCreatedBetween(\DateTimeInterface $startDate, \DateTimeInterface $endDate): int
    {
        return $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.createdAt >= :startDate')
            ->andWhere('u.createdAt <= :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->getQuery()
            ->getSingleScalarResult() ?? 0;
    }

    /**
     * Get monthly user growth data for the past 12 months
     * 
     * @return array Array of user counts by month
     */
    public function getUserGrowthByMonth(int $numberOfMonths = 12): array
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
            
            // Get count of users created as of this month end
            $totalCount = $this->createQueryBuilder('u')
                ->select('COUNT(u.id)')
                ->where('u.createdAt <= :endDate')
                ->setParameter('endDate', $endDate)
                ->getQuery()
                ->getSingleScalarResult() ?? 0;
            
            // Get count of active users (those who have verified email)
            $activeCount = $this->createQueryBuilder('u')
                ->select('COUNT(u.id)')
                ->where('u.createdAt <= :endDate')
                ->andWhere('u.isVerified = :verified')
                ->setParameter('endDate', $endDate)
                ->setParameter('verified', true)
                ->getQuery()
                ->getSingleScalarResult() ?? 0;
            
            $monthName = $endDate->format('M');
            
            // Store results with newest month last
            $results[$numberOfMonths - 1 - $i] = [
                'month' => $monthName,
                'total' => (int)$totalCount,
                'active' => (int)$activeCount
            ];
        }
        
        return $results;
    }

    /**
     * Get user distribution by role
     * 
     * @return array Array with counts for basic, premium and admin users
     */
    public function getUserDistribution(): array
    {
        $totalUsers = $this->count([]);
        $premiumUsers = $this->countUsersByRole('ROLE_PREMIUM');
        $adminUsers = $this->countUsersByRole('ROLE_ADMIN');
        $basicUsers = $totalUsers - $premiumUsers - $adminUsers;
        
        return [
            'basic' => max(0, $basicUsers),
            'premium' => $premiumUsers,
            'admin' => $adminUsers
        ];
    }

    /**
     * Get count of users using the system in a specific month
     */
    public function countActiveUsersInMonth(\DateTime $month): int
    {
        $startDate = clone $month;
        $startDate->modify('first day of this month');
        $startDate->setTime(0, 0, 0);
        
        $endDate = clone $month;
        $endDate->modify('last day of this month');
        $endDate->setTime(23, 59, 59);
        
        return $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.lastLogin >= :startDate')
            ->andWhere('u.lastLogin <= :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->getQuery()
            ->getSingleScalarResult() ?? 0;
    }
}