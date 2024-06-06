<?php declare(strict_types = 1);

namespace App\Repository;

use App\Entity\Token\Token;
use App\Entity\TopHolder;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method TopHolder|null find($id, $lockMode = null, $lockVersion = null)
 * @method TopHolder|null findOneBy(array $criteria, array $orderBy = null)
 * @method TopHolder[]    findAll()
 * @method TopHolder[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @codeCoverageIgnore
 */
class TopHolderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TopHolder::class);
    }

    /**
     * @param Token[] $tokens
     * @return TopHolder[]
     */
    public function findByTokens(array $tokens): array
    {
        return $this->createQueryBuilder('th')
            ->where('th.token IN (:tokens)')
            ->setParameter('tokens', $tokens)
            ->getQuery()
            ->getResult();
    }
}
