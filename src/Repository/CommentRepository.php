<?php declare(strict_types = 1);

namespace App\Repository;

use App\Entity\Comment;
use App\Entity\Post;
use App\Entity\Token\Token;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Comment>
 * @codeCoverageIgnore
 */
class CommentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Comment::class);
    }

    /**
     * @return Comment[]
     */
    public function findAllByCreatorIdAndTokenOwnerProfileId(int $creatorId, int $profileId): array
    {
        return $this->createQueryBuilder('comments')
            ->join(Post::class, 'post', Join::WITH, 'comments.post = post.id')
            ->join(Token::class, 't', Join::WITH, 't.profile = :profileId')
            ->where('comments.author = :userId')
            ->andWhere('post.token = t.id')
            ->setParameter('userId', $creatorId)
            ->setParameter('profileId', $profileId)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Comment[]
     */
    public function findRecentComments(
        int $page = 0,
        int $limit = 10
    ): array {
        return $this->createQueryBuilder('c')
            ->join(Post::class, 'p', Join::WITH, 'c.post = p.id')
            ->andWhere('p.status = :statusActive')
            ->setParameter('statusActive', Post::STATUS_ACTIVE)
            ->orderBy('c.createdAt', Criteria::DESC)
            ->setFirstResult($page * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param User[] $tokenOwners
     * @return Comment[]
     */
    public function findRecentCommentsByTokenOwners(
        array $tokenOwners,
        int $page = 0,
        int $limit = 10,
        string $days = '-30 days'
    ): array {
        return $this->createQueryBuilder('c')
            ->join(Post::class, 'p', Join::WITH, 'c.post = p.id')
            ->andWhere('p.status = :statusActive')
            ->andWhere('c.author IN (:tokenOwners)')
            ->andWhere('c.createdAt BETWEEN :thirtyDays AND :today')
            ->setParameter('tokenOwners', $tokenOwners)
            ->setParameter('statusActive', Post::STATUS_ACTIVE)
            ->setParameter('today', date('Y-m-d H:i:s'))
            ->setParameter('thirtyDays', date('Y-m-d H:i:s', (int)strtotime($days)))
            ->orderBy('c.createdAt', Criteria::DESC)
            ->setFirstResult($page * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Comment[]
     */
    public function findCommentsByHashtag(
        string $hashtag,
        int $page = 0,
        int $limit = 10
    ): array {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.post', 'p')
            ->leftJoin(Token::class, 't', Join::WITH, 't.isQuiet = false AND p.token = t.id')
            ->join('c.hashtags', 'h', Join::WITH, 'h.value = :hashtag')
            ->setParameter('hashtag', $hashtag)
            ->orderBy('c.createdAt', Criteria::DESC)
            ->setFirstResult($page * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
