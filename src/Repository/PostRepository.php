<?php declare(strict_types = 1);

namespace App\Repository;

use App\Entity\Post;
use App\Entity\Token\Token;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Post>
 * @codeCoverageIgnore
 */
class PostRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Post::class);
    }

    public function findRecentPosts(int $page = 0, int $max = 10): array
    {
        return $this->findBy(
            ['status' => Post::STATUS_ACTIVE],
            ['createdAt' => Criteria::DESC],
            $max,
            $page * $max,
        );
    }

    public function findRecentPostsByTokens(array $tokens, int $page = 0, int $max = 10): array
    {
        return $this->createQueryBuilder('post')
            ->where('post.token IN (:tokens)')
            ->andWhere('post.createdAt BETWEEN :thirtyDays AND :today')
            ->andWhere('post.status = :statusActive')
            ->setParameter('tokens', $tokens)
            ->setParameter('today', date('Y-m-d H:i:s'))
            ->setParameter('thirtyDays', date('Y-m-d H:i:s', strtotime('-30 days')))
            ->setParameter('statusActive', Post::STATUS_ACTIVE)
            ->orderBy('post.createdAt', Criteria::DESC)
            ->setFirstResult($page * $max)
            ->setMaxResults($max)
            ->getQuery()
            ->getResult();
    }

    public function getPostsCreatedAt(\DateTimeImmutable $date): array
    {
        $from = $date->setTime(0, 0)->format('Y-m-d H:i:s');
        $to = $date->setTime(23, 59, 59)->format('Y-m-d H:i:s');

        return $this->createQueryBuilder('post')
            ->where('post.createdAt BETWEEN :from AND :to')
            ->andWhere('post.status = :statusActive')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->setParameter('statusActive', Post::STATUS_ACTIVE)
            ->orderBy('post.createdAt', Criteria::DESC)
            ->getQuery()
            ->getResult();
    }

    public function getPostsCreatedAtByToken(Token $token, \DateTimeImmutable $date, bool $includeDeleted = false): array
    {
        $from = $date->setTime(0, 0)->format('Y-m-d H:i:s');
        $to = $date->setTime(23, 59, 59)->format('Y-m-d H:i:s');

        $query = $this->createQueryBuilder('post')
            ->where('post.token = :token')
            ->andWhere('post.createdAt BETWEEN :from AND :to')
            ->setParameter('token', $token)
            ->setParameter('from', $from)
            ->setParameter('to', $to);

        if (!$includeDeleted) {
            $query
                ->andWhere('post.status = :statusActive')
                ->setParameter('statusActive', Post::STATUS_ACTIVE);
        }

        $query->orderBy('post.createdAt', 'DESC');

        return $query
            ->orderBy('post.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getActivePostsByToken(Token $token, int $offset, int $limit): array
    {
        $postsId = $this->createQueryBuilder('post')
            ->select('post.id')
            ->where('post.token = :token')
            ->andWhere('post.status = :statusActive')
            ->setParameter('token', $token)
            ->setParameter('statusActive', Post::STATUS_ACTIVE)
            ->orderBy('post.createdAt', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return $this->addFullRelation($this->createQueryBuilder('post'))
            ->where('post.id IN (:postsId)')
            ->setParameter('postsId', $postsId)
            ->orderBy('post.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    private function addFullRelation(QueryBuilder $query): QueryBuilder
    {
        return $query
            ->leftJoin('post.token', 't')
            ->leftJoin('t.profile', 'tp')
            ->leftJoin('post.comments', 'c')
            ->leftJoin('post.userShareRewards', 'usr')
            ->leftJoin('post.usersLiked', 'ul')
            ->addSelect(['t','tp', 'c', 'usr', 'ul']);
    }

    /**
     * @return Post[]
     */
    public function findPostsByHashtag(
        string $hashtag,
        int $page = 0,
        int $limit = 10
    ): array {
        return $this->createQueryBuilder('p')
            ->join(Token::class, 't', Join::WITH, 't.isQuiet = false AND t.id = p.token')
            ->join('p.hashtags', 'h', Join::WITH, 'h.value = :hashtag')
            ->setParameter('hashtag', $hashtag)
            ->orderBy('p.createdAt', Criteria::DESC)
            ->setFirstResult($page * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
