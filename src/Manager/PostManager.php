<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\Post;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Repository\PostRepository;
use App\Utils\Symbols;
use Doctrine\ORM\EntityManagerInterface;
use Money\Currency;
use Money\Money;

class PostManager implements PostManagerInterface
{
    /** @var PostRepository */
    private $repository;

    private TokenManagerInterface $tokenManager;

    private BalanceHandlerInterface $balanceHandler;

    public function __construct(
        EntityManagerInterface $em,
        TokenManagerInterface $tokenManager,
        BalanceHandlerInterface $balanceHandler
    ) {
        /** @var PostRepository $repository */
        $repository = $em->getRepository(Post::class);
        $this->repository = $repository;
        $this->tokenManager = $tokenManager;
        $this->balanceHandler = $balanceHandler;
    }

    public function getById(int $id): ?Post
    {
        return $this->repository->find($id);
    }

    public function getBySlug(string $slug): ?Post
    {
        return $this->repository->findOneBy(['slug' => $slug]);
    }

    public function getRecentPost(User $user, int $page): array
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

        return $this->repository->findRecentPostsByTokens($tokens, $page);
    }

    public function getPostsCreatedToday(?string $date): array
    {
        return $this->repository->getPostsCreatedToday($date);
    }

    public function getPostsCreatedTodayByToken(Token $token, ?string $date): array
    {
        return $this->repository->getPostsCreatedTodayByToken($token, $date);
    }
}
