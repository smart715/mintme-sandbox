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

    /** @psalm-suppress ImplementedReturnTypeMismatch  */
    public function getRepository(): UserRepository
    {
        /** @var UserRepository $repository */
        $repository = parent::getRepository();

        return $repository;
    }

    public function findByReferralCode(string $code): ?User
    {
        return $this->getRepository()->findOneBy([
            'referralCode' => $code,
        ]);
    }

    public function findByDomain(string $domain): array
    {
        return $this->getRepository()->findByDomain($domain);
    }

    /**
     * {@inheritdoc}
     */
    public function findUserByEmail($email)
    {
        return $this->findUserBy(['email' => $email]);
    }

    public function checkExistCanonicalEmail(string $email): bool
    {
        return $this->getRepository()->checkExistCanonicalEmail($email);
    }

    /** @inheritDoc */
    public function getUserToken(Token $token, array $userIds): array
    {
        $qb = $this->getRepository()->createQueryBuilder('q');

        return $qb->select('ut')
                ->from(UserToken::class, 'ut')
                ->innerJoin('ut.user', 'u')
                ->innerJoin('u.profile', 'p')
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
            ->innerJoin('uc.user', 'u')
            ->innerJoin('u.profile', 'p')
            ->add('where', $qb->expr()->in('uc.user', $userIds))
            ->andWhere('uc.crypto = ?1')
            ->andWhere('p.anonymous = 0')
            ->setParameter(1, $crypto->getId())
            ->getQuery()
            ->execute();
    }

    /**
     * @param array $domains
     * @return array|null
     */
    public function getUsersByDomains(array $domains): ?array
    {
        $emailDomains = [];

        foreach ($domains as $domain) {
            $emailDomains = array_merge($emailDomains, $this->getRepository()->findByDomain($domain));
        }

        return $emailDomains;
    }
}
