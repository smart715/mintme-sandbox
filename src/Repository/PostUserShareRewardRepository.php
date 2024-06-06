<?php declare(strict_types = 1);

namespace App\Repository;

use App\Entity\Post;
use App\Entity\PostUserShareReward;
use App\Entity\Profile;
use App\Entity\PromotionHistory;
use App\Entity\Token\Token;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;

/** @codeCoverageIgnore */
class PostUserShareRewardRepository extends ServiceEntityRepository implements PromotionHistoryRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PostUserShareReward::class);
    }

    /**
     * @return PromotionHistory[]
     */
    public function getPromotionHistoryByUserAndToken(
        User $user,
        int $offset,
        int $limit,
        \DateTimeImmutable $fromDate
    ): array {
        return $this->createQueryBuilder('pusr')
            ->join(Post::class, 'post', Join::WITH, 'pusr.post = post.id')
            ->join(Token::class, 't', Join::WITH, 'post.token = t.id')
            ->join(Profile::class, 'profile', Join::WITH, 't.profile = profile.id')
            ->where('profile.user = :userId OR pusr.user = :userId')
            ->andWhere('pusr.createdAt > :fromDate')
            ->setParameter('userId', $user->getId())
            ->setParameter('fromDate', $fromDate)
            ->orderBy('pusr.createdAt', Criteria::DESC)
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function hasSharePostReward(User $user, Token $token): bool
    {
        return (bool)$this->createQueryBuilder('pusr')
            ->join(Post::class, 'post', Join::WITH, 'pusr.post = post.id')
            ->where('pusr.user = :userId')
            ->andWhere('post.token = :tokenId')
            ->setParameter('userId', $user->getId())
            ->setParameter('tokenId', $token->getId())
            ->setMaxResults(1)
            ->getQuery()
            ->getResult();
    }
}
