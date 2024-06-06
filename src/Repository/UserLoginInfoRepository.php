<?php declare(strict_types = 1);

namespace App\Repository;

use App\Entity\User;
use App\Entity\UserLoginInfo;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @codeCoverageIgnore */
class UserLoginInfoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserLoginInfo::class);
    }

    public function getStoreUserDeviceInfo(User $user, string $deviceIp): ?UserLoginInfo
    {
        return $this->createQueryBuilder('user_login_info')
            ->andWhere('user_login_info.user = :user')
            ->andWhere('user_login_info.ip_address = :ip_address')
            ->setParameter('user', $user)
            ->setParameter('ip_address', $deviceIp)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
