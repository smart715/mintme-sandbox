<?php declare(strict_types = 1);

namespace App\Repository;

use App\Entity\Token\Token;
use App\Entity\Token\TokenReleaseAddressHistory;
use App\Entity\User;
use App\Wallet\Model\Status;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TokenReleaseAddressHistory>
 * @codeCoverageIgnore
 */
class TokenReleaseAddressHistoryRepository extends ServiceEntityRepository implements PromotionHistoryRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TokenReleaseAddressHistory::class);
    }

    public function findLatestPending(Token $token): ?TokenReleaseAddressHistory
    {
        return $this->createQueryBuilder('h')
            ->join('h.token', 't')
            ->where('h.token = :token')
            ->andWhere('h.status = :status')
            ->setParameter('token', $token)
            ->setParameter('status', Status::PENDING)
            ->orderBy('h.createdAt', Criteria::DESC)
            ->setMaxResults(1)
            ->getQuery()
            ->getResult()[0] ?? null;
    }

    public function getPromotionHistoryByUserAndToken(
        User $user,
        int $offset,
        int $limit,
        \DateTimeImmutable $fromDate
    ): array {
        return $this->createQueryBuilder('h')
            ->join('h.token', 't')
            ->where('t.profile = :profile')
            ->andWhere('h.createdAt > :fromDate')
            ->setParameter('profile', $user->getProfile())
            ->setParameter('fromDate', $fromDate)
            ->orderBy('h.createdAt', Criteria::DESC)
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
