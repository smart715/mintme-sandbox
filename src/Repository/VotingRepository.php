<?php declare(strict_types = 1);

namespace App\Repository;

use App\Entity\Voting\Option;
use App\Entity\Voting\Voting;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Voting>
 * @codeCoverageIgnore
 */
class VotingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Voting::class);
    }

    public function getByOptionId(int $optionId): ?Voting
    {
        return $this->createQueryBuilder('v')
                ->join(Option::class, 'o', Join::WITH, 'v.id = o.voting')
                ->where('o.id = :optionId')
                ->setParameter('optionId', $optionId)
                ->groupBy('v.id')
                ->getQuery()
                ->getResult()[0] ?? null;
    }
}
