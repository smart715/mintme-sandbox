<?php declare(strict_types = 1);

namespace App\Repository;

use App\Entity\Token\Token;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class TokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Token::class);
    }

    /** @codeCoverageIgnore */
    public function findByName(string $name): ?Token
    {
        return $this->findOneBy(['name' => $name]);
    }

    /** @codeCoverageIgnore */
    public function findByUrl(string $name): ?Token
    {
        return $this->createQueryBuilder('token')
            ->where('REPLACE(token.name, \' \', \'-\' ) = (:name)')
            ->orWhere('REPLACE(token.name, \'-\', \' \' ) = (:name)')
            ->setParameter('name', $name)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /** @codeCoverageIgnore */
    public function findByAddress(string $address): ?Token
    {
        return $this->createQueryBuilder('token')
            ->where('token.address=:name')
            ->setParameter(':name', $address)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @codeCoverageIgnore
     * @return Token[]
     */
    public function findTokensByPattern(string $pattern): array
    {
        return $this->createQueryBuilder('token')
            ->where('LOWER(token.name) LIKE LOWER(:like)')
            ->andWhere('token.isBlocked=false')
            ->setParameter('like', "$pattern%")
            ->orderBy('token.name', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();
    }

    /**
     * @codeCoverageIgnore
     * @return Token[]
     */
    public function getDeployedTokens(?int $offset = null, ?int $limit = null): array
    {
        $query = $this->createQueryBuilder('token')
            ->leftJoin('token.crypto', 'crypto')
            ->where('token.deployed IS NOT NULL')
            ->orderBy('token.crypto', 'ASC');

        if (is_int($offset)) {
            $query->setFirstResult($offset);
        }

        if (is_int($limit)) {
            $query->setMaxResults($limit);
        }

        return $query
            ->getQuery()
            ->execute();
    }

    /** @codeCoverageIgnore */
    public function findAllTokensWithEmptyDescription(int $numberOfReminder = 14): ?array
    {
        $query = $this->createQueryBuilder('t')
            ->select('t, p, u')
            ->innerJoin('t.profile', 'p', 't.profile = p.id')
            ->innerJoin('p.user', 'u', 'u.id = p.user')
            ->where('t.description is null or p.description = :emptyString')
            ->andWhere('t.numberOfReminder <> :numberOfReminder')
            ->andWhere('t.nextReminderDate = :nextReminderDate OR t.nextReminderDate is null')
            ->setParameter('emptyString', '')
            ->setParameter('numberOfReminder', $numberOfReminder)
            ->setParameter('nextReminderDate', \Date('Y-m-d'));

        return $query->getQuery()->getResult();
    }

    /**
     * @codeCoverageIgnore
     * @return Token[]
     */
    public function getTokensWithoutAirdrops(): array
    {
        return $this->createQueryBuilder('t')
            ->select('t, a')
            ->leftJoin('t.airdrops', 'a')
            ->where('a.id IS NULL')
            ->getQuery()
            ->getResult();
    }

    /**
     * @codeCoverageIgnore
     * @return Token[]
     */
    public function getTokensWithAirdrops(): array
    {
        return $this->createQueryBuilder('t')
            ->select('t, a')
            ->leftJoin('t.airdrops', 'a')
            ->where('a.id IS NOT NULL')
            ->getQuery()
            ->getResult();
    }
}
