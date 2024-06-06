<?php declare(strict_types = 1);

namespace App\Repository;

use App\Entity\Token\Token;
use App\Entity\Voting\TokenVoting;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TokenVoting>
 * @codeCoverageIgnore
 */
class TokenVotingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TokenVoting::class);
    }

    public function getVotingByTokenId(int $tokenId, int $offset, int $limit): ?array
    {
        $query = $this->createQueryBuilder('v')
            ->where('v INSTANCE OF App\Entity\Voting\TokenVoting')
            ->andWhere('v.token = :token')
            ->leftJoin('v.userVotings', 'uv')
            ->leftJoin('v.options', 'o')
            ->addSelect(['uv', 'o'])
            ->orderBy('v.createdAt', 'DESC')
            ->setParameter('token', $tokenId)
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery();

        $paginator = new Paginator($query);

        /** @phpstan-ignore-next-line calling getArrayCopy() on an Iterator is undefined, but it's defined */
        return $paginator->getIterator()->getArrayCopy();
    }

    public function countOpenVotingsByToken(Token $token): int
    {
        return (int)$this->createQueryBuilder('v')
            ->select('count(v.id)')
            ->where('v INSTANCE OF App\Entity\Voting\TokenVoting')
            ->andWhere('v.token = :token')
            ->andWhere('v.endDate > :now')
            ->setParameter('token', $token)
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countVotingsByToken(Token $token): int
    {
        return (int)$this->createQueryBuilder('v')
            ->select('count(v.id)')
            ->where('v INSTANCE OF App\Entity\Voting\TokenVoting')
            ->andWhere('v.token = :token')
            ->setParameter('token', $token)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getVotingsByCreatorIdAndProfileId(int $creatorId, int $profileId): array
    {
        return $this->createQueryBuilder('v')
            ->join(Token::class, 't', Join::WITH, 'v.token = t.id')
            ->where('v.creator = :creator')
            ->andWhere('t.profile = :profile')
            ->setParameter('creator', $creatorId)
            ->setParameter('profile', $profileId)
            ->getQuery()
            ->getResult();
    }
}
