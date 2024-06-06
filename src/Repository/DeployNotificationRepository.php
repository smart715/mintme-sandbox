<?php declare(strict_types = 1);

namespace App\Repository;

use App\Entity\DeployNotification;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method DeployNotification|null find($id, $lockMode = null, $lockVersion = null)
 * @method DeployNotification|null findOneBy(array $criteria, array $orderBy = null)
 * @method DeployNotification[]    findAll()
 * @method DeployNotification[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @codeCoverageIgnore
 */
class DeployNotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DeployNotification::class);
    }

    /**
     * @param User[] $users
     * @return int[]
     */
    public function getAlreadyNotifiedByUsersTokenIDs(array $users, int $maxNotifications): array
    {
        $qb = $this->createQueryBuilder('d');
        $result = $qb
            ->select('COUNT(d.token) AS c', 'IDENTITY(d.token) AS token_id')
            ->groupBy('d.token')
            ->where('d.notifier IN (:users)')
            ->having($qb->expr()->gte('c', ':maxNotifications'))
            ->setParameter('users', $users)
            ->setParameter('maxNotifications', $maxNotifications)
            ->getQuery()
            ->getResult();

        if (!$result) {
            return [];
        }

        $tokenIds = [];

        foreach ($result as $value) {
            $tokenIds[] = (int)$value['token_id'];
        }

        return $tokenIds;
    }
}
