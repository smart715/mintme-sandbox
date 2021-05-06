<?php declare(strict_types = 1);

namespace App\Repository;

use App\Entity\Post;
use App\Entity\User;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Manager\TokenManagerInterface;
use App\Utils\Symbols;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Money\Currency;
use Money\Money;

class PostRepository extends ServiceEntityRepository
{
    private TokenManagerInterface $tokenManager;

    private BalanceHandlerInterface $balanceHandler;

    public function __construct(
        ManagerRegistry $registry,
        TokenManagerInterface $tokenManager,
        BalanceHandlerInterface $balanceHandler
    ) {
        $this->tokenManager = $tokenManager;
        $this->balanceHandler = $balanceHandler;

        parent::__construct($registry, Post::class);
    }

    /** @codeCoverageIgnore */
    public function findRecentPostsOfUser(User $user, int $page = 0, int $max = 10): array
    {
        $tokens = [];

        foreach ($user->getTokens() as $token) {
            $available = $this->tokenManager->getRealBalance(
                $token,
                $this->balanceHandler->balance($user, $token)
            )->getAvailable();

            if ($available->greaterThanOrEqual(new Money(0, new Currency(Symbols::TOK)))) {
                $tokens[] = $token;
            }
        }

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
