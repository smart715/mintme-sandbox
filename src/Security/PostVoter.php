<?php declare(strict_types = 1);

namespace App\Security;

use App\Entity\Post;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Manager\TokenManagerInterface;
use Money\Money;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class PostVoter extends Voter
{
    private const VIEW = 'view';
    private const EDIT = 'edit';
    private const ACTIONS = [
        self::VIEW,
        self::EDIT,
    ];

    private TokenManagerInterface $tokenManager;
    private BalanceHandlerInterface $balanceHandler;
    private LoggerInterface $logger;

    public function __construct(
        TokenManagerInterface $tokenManager,
        BalanceHandlerInterface $balanceHandler,
        LoggerInterface $logger
    ) {
        $this->tokenManager = $tokenManager;
        $this->balanceHandler = $balanceHandler;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    protected function supports($attribute, $subject): bool
    {
        return in_array($attribute, self::ACTIONS) && $subject instanceof Post;
    }

    /**
     * {@inheritdoc}
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        $user = $user instanceof User
            ? $user
            : null;

        /** @var Post */
        $post = $subject;

        if (self::VIEW === $attribute) {
            return $this->canView($post, $user);
        } elseif (self::EDIT === $attribute) {
            return $this->canEdit($post, $user);
        }

        return false;
    }

    private function canView(Post $post, ?User $user): bool
    {
        return $user
            ? $this->isOwner($user, $post->getToken()) || $this->checkBalance($user, $post->getToken(), $post->getAmount())
            : $post->getAmount()->isZero();
    }

    private function canEdit(Post $post, ?User $user): bool
    {
        return $post->getAuthor()->getUser() === $user;
    }

    private function checkBalance(User $user, Token $token, Money $amount): bool
    {
        if ($amount->isZero()) {
            return true;
        }

        try {
            $availableFullBalance = $this->tokenManager->getRealBalance(
                $token,
                $this->balanceHandler->balance($user, $token),
                $user
            )->getFullAvailable();

            return $availableFullBalance->greaterThanOrEqual($amount);
        } catch (\Throwable $ex) {
            $this->logger->error('Can\'t fetch token balance to allow to view token posts', [
                'user' => $user->getEmail(),
                'message' => $ex->getMessage(),
            ]);

            return false;
        }
    }

    private function isOwner(User $user, Token $token): bool
    {
        return $token->getOwner() === $user;
    }
}
