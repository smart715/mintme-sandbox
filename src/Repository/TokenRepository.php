<?php declare(strict_types = 1);

namespace App\Repository;

use App\Entity\Profile;
use App\Entity\Token\Token;
use Doctrine\ORM\EntityRepository;

class TokenRepository extends EntityRepository
{
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
    public function getDeployedTokens(): array
    {
        return $this->createQueryBuilder('token')
            ->join('token.crypto', 'crypto')
            ->where('token.deployed IS NOT NULL')
            ->orWhere('crypto.symbol = :ethSymbol')
            ->setParameter('ethSymbol', Token::ETH_SYMBOL)
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
}
