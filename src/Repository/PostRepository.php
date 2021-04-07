<?php declare(strict_types = 1);

namespace App\Repository;

use App\Entity\Token\Token;
use Doctrine\ORM\EntityRepository;

class PostRepository extends EntityRepository
{
    /** @codeCoverageIgnore */
    public function findByTokens(array $tokens, int $page): array
    {
        return $this->createQueryBuilder('post')
            ->where('post.token IN (:tokens)')
            ->setParameter('tokens', $tokens)
            ->orderBy('post.createdAt', 'ASC')
            ->setFirstResult($page * 10)
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();
    }
}
