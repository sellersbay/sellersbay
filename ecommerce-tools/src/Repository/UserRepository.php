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
     * Get monthly user growth data for the past 12 months - OPTIMIZED VERSION
     * 
     * @return array Array of user counts by month
     */
    public function getUserGrowthByMonth(int $numberOfMonths = 12): array
    {
        // Calculate date range
        $endDate = new \DateTime();
        $startDate = clone $endDate;
        $startDate->modify('-' . ($numberOfMonths - 1) . ' months');
        $startDate->modify('first day of this month');
        $startDate->setTime(0, 0, 0);
        
        // Build a query to get the cumulative user counts as of the end of each month
        $conn = $this->getEntityManager()->getConnection();
        $sql = "
            WITH RECURSIVE months AS (
                SELECT 
                    DATE_FORMAT(:startDate, '%Y-%m-01') as month_start,
                    LAST_DAY(:startDate) as month_end,
                    DATE_FORMAT(:startDate, '%b') as month_name
                UNION ALL
                SELECT 
                    DATE_ADD(month_start, INTERVAL 1 MONTH),
                    LAST_DAY(DATE_ADD(month_start, INTERVAL 1 MONTH)),
                    DATE_FORMAT(DATE_ADD(month_start, INTERVAL 1 MONTH), '%b')
                FROM months
                WHERE month_start < DATE_FORMAT(:endDate, '%Y-%m-01')
            )
            SELECT 
                m.month_name,
                (SELECT COUNT(*) FROM user u WHERE u.created_at <= m.month_end) as total,
                (SELECT COUNT(*) FROM user u WHERE u.created_at <= m.month_end AND u.is_verified = 1) as active
            FROM months m
            ORDER BY m.month_start ASC
        ";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindValue('startDate', $startDate->format('Y-m-d'));
        $stmt->bindValue('endDate', $endDate->format('Y-m-d'));
        $results = $stmt->executeQuery()->fetchAllAssociative();
        
        // Format the results
        $formattedResults = [];
        foreach ($results as $i => $result) {
            $formattedResults[$i] = [
                'month' => $result['month_name'],
                'total' => (int)$result['total'],
                'active' => (int)$result['active']
            ];
        }
        
        return $formattedResults;
    }

    /**
     * Get user distribution by role - OPTIMIZED VERSION
     * 
     * @return array Array with counts for basic, premium and admin users
     */
    public function getUserDistribution(): array
    {
        // Get all role counts in a single query
        $conn = $this->getEntityManager()->getConnection();
        $sql = "
            SELECT 
                COUNT(*) as total_users,
                SUM(CASE WHEN JSON_CONTAINS(roles, '\"ROLE_PREMIUM\"') THEN 1 ELSE 0 END) as premium_users,
                SUM(CASE WHEN JSON_CONTAINS(roles, '\"ROLE_ADMIN\"') THEN 1 ELSE 0 END) as admin_users
            FROM user
        ";
        
        $result = $conn->executeQuery($sql)->fetchAssociative();
        
        $totalUsers = (int)$result['total_users'];
        $premiumUsers = (int)$result['premium_users'];
        $adminUsers = (int)$result['admin_users'];
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