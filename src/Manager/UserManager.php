<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\Crypto;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Entity\UserCrypto;
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

    /** @inheritDoc */
    public function getUserToken(Token $token, array $userIds): array
    {
        $qb = $this->getRepository()->createQueryBuilder('q');

        return $qb->select('ut')
                ->from(UserToken::class, 'ut')
                ->add('where', $qb->expr()->in('ut.user', $userIds))
                ->andWhere('ut.token = ?1')
                ->andWhere('ut.user != ?2')
                ->setParameter(1, $token->getId())
                ->setParameter(2, $token->getProfile()->getUser()->getId())
                ->getQuery()
                ->execute();
    }

    /** @inheritDoc */
    public function getUserCrypto(Crypto $crypto, array $userIds): array
    {
        $qb = $this->getRepository()->createQueryBuilder('q');

        return $qb->select('uc')
            ->from(UserCrypto::class, 'uc')
            ->add('where', $qb->expr()->in('uc.user', $userIds))
            ->andWhere('uc.crypto = ?1')
            ->setParameter(1, $crypto->getId())
            ->getQuery()
            ->execute();
    }
}
