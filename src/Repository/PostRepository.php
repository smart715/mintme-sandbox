<?php declare(strict_types = 1);

namespace App\Repository;

use App\Entity\Token\Token;
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

    public function getPostsCreatedToday(?string $date): array
    {
        if ($date) {
            $from = $date.' 00:00:00';
            $to = $date.' 23:59:59';
        } else {
            $from = date('Y-m-d', strtotime('-1 day')).' 00:00:00';
            $to = date('Y-m-d', strtotime('-1 day')).' 23:59:59';
        }

        return $this->createQueryBuilder('post')
            ->where('post.createdAt BETWEEN :from AND :to')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->orderBy('post.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getPostsCreatedTodayByToken(Token $token, ?string $date): array
    {
        if ($date) {
            $from = $date.' 00:00:00';
            $to = $date.' 23:59:59';
        } else {
            $from = date('Y-m-d 00:00:00');
            $to = date('Y-m-d 23:59:59');
        }

        return $this->createQueryBuilder('post')
            ->where('post.token = :token')
            ->andWhere('post.createdAt BETWEEN :from AND :to')
            ->setParameter('token', $token)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->orderBy('post.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
