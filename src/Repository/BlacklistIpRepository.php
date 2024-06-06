<?php declare(strict_types = 1);

namespace App\Repository;

use App\Entity\Blacklist\BlacklistIp;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method BlacklistIp|null find($id, $lockMode = null, $lockVersion = null)
 * @method BlacklistIp|null findOneBy(array $criteria, array $orderBy = null)
 * @method BlacklistIp[]    findAll()
 * @method BlacklistIp[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @codeCoverageIgnore
 */
class BlacklistIpRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BlacklistIp::class);
    }

    public function findByIp(string $ipAddress): ?BlacklistIp
    {
        return $this->findOneBy(['address' => $ipAddress]);
    }
}
