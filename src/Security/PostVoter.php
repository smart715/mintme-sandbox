<?php declare(strict_types = 1);

namespace App\Security;

use App\Entity\Post;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Manager\TokenManagerInterface;
use Money\Money;
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

    /** @var TokenManagerInterface */
    private $tokenManager;

    /** @var BalanceHandlerInterface */
    private $balanceHandler;

    public function __construct(
        TokenManagerInterface $tokenManager,
        BalanceHandlerInterface $balanceHandler
    ) {
        $this->tokenManager = $tokenManager;
        $this->balanceHandler = $balanceHandler;
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
        $available = $this->tokenManager->getRealBalance(
            $token,
            $this->balanceHandler->balance($user, $token),
            $user
        )->getAvailable();

        return $available->greaterThanOrEqual($amount);
    }

    private function isOwner(User $user, Token $token): bool
    {
        return $token->getOwner() === $user;
    }
}
