<?php declare(strict_types = 1);

namespace App\Repository;

use Doctrine\ORM\EntityRepository;

class PostRepository extends EntityRepository
{
    /** @codeCoverageIgnore */
    public function findRecentPostsByTokens(array $tokens, int $page = 0, int $max = 10): array
    {
        return $this->createQueryBuilder('post')
            ->where('post.token IN (:tokens)')
            ->andWhere('post.createdAt BETWEEN :thirtyDays AND :today')
            ->setParameter('tokens', $tokens)
            ->setParameter('today', date('Y-m-d H:i:s'))
            ->setParameter('thirtyDays', date('Y-m-d H:i:s', strtotime('-30 days')))
            ->orderBy('post.createdAt', 'DESC')
            ->setFirstResult($page * $max)
            ->setMaxResults($max)
            ->getQuery()
            ->getResult();
    }
}
