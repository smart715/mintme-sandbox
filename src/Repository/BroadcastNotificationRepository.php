<?php declare(strict_types = 1);

namespace App\Repository;

use App\Entity\BroadcastNotification;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<BroadcastNotification>
 * @method BroadcastNotification|null find($id, $lockMode = null, $lockVersion = null)
 * @method BroadcastNotification|null findOneBy(array $criteria, array $orderBy = null)
 * @method BroadcastNotification[]    findAll()
 * @method BroadcastNotification[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BroadcastNotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BroadcastNotification::class);
    }

    /**
     * @return BroadcastNotification[]
     */
    public function findLatest(int $dayLimit): array
    {
        $from = new \DateTimeImmutable("-{$dayLimit} days");

        $qb = $this->createQueryBuilder('bn')
            ->where('bn.date >= :from')
            ->setParameter('from', $from)
            ->orderBy('bn.date', 'DESC')
            ->getQuery();

        return $qb->getResult();
    }
}
