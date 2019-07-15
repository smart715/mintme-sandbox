<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\User;
use App\Entity\UserToken;
use App\Repository\UserRepository;

class UserManager extends \FOS\UserBundle\Doctrine\UserManager implements UserManagerInterface
{

    public function find(int $id): ?User
    {
        return $this->getRepository()->find($id);
    }

    public function getRepository(): UserRepository
    {
        return parent::getRepository();
    }

    public function findByReferralCode(string $code): ?User
    {
        return $this->getRepository()->findOneBy([
            'referralCode' => $code,
        ]);
    }

    /**
     * @param int $token
     * @param int[] $userIds
     * @return UserToken[]
     */
    public function getUserToken(int $token, array $userIds): array
    {
        $qb = $this->getRepository()->createQueryBuilder('q');

        return $qb->select('ut')
                ->from(UserToken::class, 'ut')
                ->add('where', $qb->expr()->in('ut.user', $userIds))
                ->andWhere('ut.token = ?1')
                ->setParameter(1, $token)
                ->getQuery()
                ->execute();
    }
}
