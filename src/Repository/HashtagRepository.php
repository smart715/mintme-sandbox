<?php declare(strict_types = 1);

namespace App\Repository;

use App\Entity\Hashtag;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;

/**
 *  @extends ServiceEntityRepository<Hashtag>
 *  @codeCoverageIgnore
 */
class HashtagRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Hashtag::class);
    }

    public function findPopularHashtagsByKeyword(string $keyword): array
    {
        return $this->createQueryBuilder('h')
            ->select('h.value as value')
            ->where('h.value LIKE :keyword')
            ->setParameter('keyword', $keyword . '%')
            ->orderBy('h.updatedAt', Criteria::DESC)
            ->setFirstResult(0)
            ->setMaxResults(8)
            ->getQuery()
            ->getResult();
    }

    public function getPopularHashtags(\DateTimeImmutable $fromDate): array
    {
        return $this->createQueryBuilder('h')
            ->select('h.value as value')
            // only count one post per author
            ->addSelect('COUNT(DISTINCT p.token) as HIDDEN posts_count')
            // only count one comment per author
            ->addSelect('COUNT(DISTINCT p.token) + COUNT(DISTINCT c.author) as total')
            ->leftJoin('h.posts', 'p', Join::WITH, 'p.createdAt >= :fromDate')
            ->leftJoin('h.comments', 'c', Join::WITH, 'c.createdAt >= :fromDate')
            ->where('h.updatedAt >= :fromDate')
            ->groupBy('h.value')
            ->having('posts_count > 0')
            ->orderBy('total', 'DESC')
            ->setParameter('fromDate', $fromDate)
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();
    }
}
