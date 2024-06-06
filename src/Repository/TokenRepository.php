<?php declare(strict_types = 1);

namespace App\Repository;

use App\Entity\Token\Token;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Token>
 * @codeCoverageIgnore
 */
class TokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Token::class);
    }

    public function findByName(string $name): ?Token
    {
        return $this->findOneBy(['name' => $name]);
    }

    public function findByIdsWithDeploys(array $ids): array
    {
        return $this->createQueryBuilder('token')
            ->where('token.id IN (:ids)')
            ->leftJoin('token.deploys', 'td')
            ->addSelect(['td'])
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();
    }

    public function findByUrl(string $name): ?Token
    {
        return $this->createQueryBuilder('token')
            ->where('REPLACE(token.name, \' \', \'-\' ) = (:name)')
            ->orWhere('REPLACE(token.name, \'-\', \' \' ) = (:name)')
            ->leftJoin('token.exchangeCryptos', 'ec')
            ->addSelect(['ec'])
            ->setParameter('name', $name)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
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
     * @return Token[]
     */
    public function getDeployedTokens(?int $offset = null, ?int $limit = null): array
    {
        $query = $this->createQueryBuilder('token')
            ->leftJoin('token.deploys', 'deploys')
            ->where('token.deployed = true')
            ->andWhere('token.isBlocked = false')
            ->orderBy('deploys.crypto', 'ASC');

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

    public function getRandomTokens(int $limit): array
    {
        return $this->createQueryBuilder('token')
            ->select('token.name, token.id')
            ->leftJoin('token.deploys', 'deploys')
            ->where('token.deployed = true')
            ->andWhere('token.isBlocked = false')
            ->andWhere('token.isHidden = false')
            ->orderBy('RAND()')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Token[]
     */
    public function findAllTokensWithEmptyDescription(int $numberOfReminder = 14): array
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

    /**
     * @return Token[]
     */
    public function getNotOwnTokens(User $user): array
    {
        return $this->createQueryBuilder('token')
            ->join('token.users', 'u')
            ->where('token.profile <> :profile')
            ->andWhere('u.user = :user')
            ->andWhere('u.isHolder = 1')
            ->setParameter('user', $user)
            ->setParameter('profile', $user->getProfile())
            ->getQuery()
            ->getResult();
    }

    /**
     * @param int[] $excludedIds
     */
    public function findNotDeployedRandomTokenWithExcludedIDs(array $excludedIds): ?Token
    {
        if (!$excludedIds) {
            $excludedIds = [-1]; // should be not empty array in any case
        }

        return $this->createQueryBuilder('t')
            ->where('t.id NOT IN (:excludedIds)')
            ->andWhere('t.deployed = false')
            ->orderBy('RAND()')
            ->setMaxResults(1)
            ->setParameter('excludedIds', $excludedIds)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
