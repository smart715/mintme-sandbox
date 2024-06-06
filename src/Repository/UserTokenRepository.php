<?php declare(strict_types = 1);

namespace App\Repository;

use App\Entity\Token\Token;
use App\Entity\UserToken;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method UserToken[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @codeCoverageIgnore
 */
class UserTokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserToken::class);
    }

    public function findByUserToken(int $userId, int $tokenId): ?UserToken
    {
        return $this->createQueryBuilder('ut')
            ->andWhere('ut.user = :userId')
            ->andWhere('ut.token = :tokenId')
            ->setParameter('userId', $userId)
            ->setParameter('tokenId', $tokenId)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findByUserNonReferralTokens(
        int $userId,
        ?int $page,
        int $max
    ): ?array {
        $query = $this->createQueryBuilder('ut')
            ->select('t')
            ->join(Token::class, 't', Join::WITH, 'ut.token = t.id')
            ->andWhere('ut.user = :userId')
            ->andWhere('ut.isReferral = 0')
            ->setParameter('userId', $userId);

        if ($page) {
            $query
                ->setFirstResult(($page - 1) * $max)
                ->setMaxResults($max);
        }

        return $query
            ->getQuery()
            ->getResult();
    }

    public function getUserToken(Token $token, array $userIds): array
    {
        $qb = $this->createQueryBuilder('ut');

        return $qb->select('ut')
            ->join('ut.user', 'u')
            ->add('where', $qb->expr()->in('ut.user', $userIds))
            ->andWhere('ut.token = :tokenId')
            ->andWhere('ut.user != :userId')
            ->andWhere('u.isBlocked = false')
            ->setParameter('tokenId', $token->getId())
            ->setParameter('userId', $token->getProfile()->getUser()->getId())
            ->getQuery()
            ->execute();
    }

    public function getUserOwnsCount(int $userId): int
    {
        return (int)$this->createQueryBuilder('ut')
            ->join(Token::class, 't', Join::WITH, 'ut.token = t.id')
            ->select('count(ut.id)')
            ->andWhere('ut.user = :userId')
            ->andWhere('ut.isReferral = 0')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findWithDiscordByToken(Token $token): array
    {
        return $this->createQueryBuilder('ut')
            ->join('ut.user', 'u', Join::WITH, 'ut.user = u.id AND u.discordId IS NOT NULL')
            ->where('ut.token = :token')
            ->setParameter('token', $token)
            ->getQuery()
            ->getResult();
    }
}
