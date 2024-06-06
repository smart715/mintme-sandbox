<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\Post;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Repository\PostRepository;
use App\Utils\Symbols;
use Money\Currency;
use Money\Money;

class PostManager implements PostManagerInterface
{
    private PostRepository $repository;
    private TokenManagerInterface $tokenManager;
    private BalanceHandlerInterface $balanceHandler;
    private UserTokenFollowManagerInterface $userTokenFollowManager;

    public function __construct(
        PostRepository $repository,
        TokenManagerInterface $tokenManager,
        BalanceHandlerInterface $balanceHandler,
        UserTokenFollowManagerInterface $userTokenFollowManager
    ) {
        $this->repository = $repository;
        $this->tokenManager = $tokenManager;
        $this->balanceHandler = $balanceHandler;
        $this->userTokenFollowManager = $userTokenFollowManager;
    }

    public function getRepository(): PostRepository
    {
        return $this->repository;
    }

    public function getById(int $id): ?Post
    {
        return $this->repository->find($id);
    }

    public function getBySlug(string $slug): ?Post
    {
        return $this->repository->findOneBy([
            'slug' => $slug,
            'status' => POST::STATUS_ACTIVE,
        ]);
    }

    public function getRecentPosts(int $page, int $max): array
    {
        return $this->repository->findRecentPosts($page, $max);
    }

    public function getRecentPostsByUserFeed(User $user, int $page): array
    {
        $tokens = [];

        $followedTokens = $this->userTokenFollowManager->getFollowedTokens($user);

        foreach ($followedTokens as $token) {
            if ($token->isQuiet()) {
                continue;
            }

            $available = $this->tokenManager->getRealBalance(
                $token,
                $this->balanceHandler->balance($user, $token),
                $user
            )->getAvailable();

            if ($available->greaterThanOrEqual(new Money(0, new Currency(Symbols::TOK)))) {
                $tokens[] = $token;
            }
        }

        return $this->repository->findRecentPostsByTokens(
            array_merge($tokens, $this->tokenManager->getOwnTokens()),
            $page
        );
    }

    public function getPostsCreatedAt(\DateTimeImmutable $date): array
    {
        return $this->repository->getPostsCreatedAt($date);
    }

    public function getPostsCreatedAtByToken(Token $token, \DateTimeImmutable $date, bool $includeDeleted = false): array
    {
        return $this->repository->getPostsCreatedAtByToken($token, $date, $includeDeleted);
    }

    public function getPostsByToken(Token $token, int $offset, int $limit): array
    {
        return $this->repository->findBy(
            [
                'token' => $token,
                'status' => POST::STATUS_ACTIVE,
            ],
            ['createdAt' => 'DESC'],
            $limit,
            $offset
        );
    }

    public function getTokenPostsCount(Token $token): int
    {
        return $this->repository->count([
            'token' => $token,
            'status' => POST::STATUS_ACTIVE,
        ]);
    }

    public function getActivePostsByToken(Token $token, int $offset, int $limit): array
    {
        return $this->repository->getActivePostsByToken($token, $offset, $limit);
    }

    public function getActivePostsCountByToken(Token $token): int
    {
        return $this->repository->count([
            'token' => $token,
            'status' => POST::STATUS_ACTIVE,
        ]);
    }

    /** {@inheritDoc} */
    public function getPostsByHashtag(string $hashtag, int $page): array
    {
        return $this->repository->findPostsByHashtag($hashtag, $page);
    }
}
