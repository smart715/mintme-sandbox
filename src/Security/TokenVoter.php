<?php declare(strict_types = 1);

namespace App\Security;

use App\Entity\Crypto;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Manager\BlockedUserManagerInterface;
use App\Manager\TokenManagerInterface;
use App\Utils\Symbols;
use App\Wallet\Money\MoneyWrapperInterface;
use Money\Money;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class TokenVoter extends Voter
{
    private const CREATE = 'create';
    private const EDIT = 'edit';
    private const DELETE = 'delete';
    private const DELETE_FROM_WALLET = 'delete-from-wallet';
    private const NOT_BLOCKED = 'not-blocked';
    private const INTERACT = 'interact';
    private const EXCEED = 'exceed';

    private ContainerInterface $container;
    private TokenManagerInterface $tokenManager;
    private AccessDecisionManagerInterface $decisionManager;
    private BalanceHandlerInterface $balanceHandler;
    private MoneyWrapperInterface  $moneyWrapper;
    private BlockedUserManagerInterface $blockedUserManager;

    public function __construct(
        TokenManagerInterface $tokenManager,
        AccessDecisionManagerInterface $decisionManager,
        ContainerInterface $container,
        BalanceHandlerInterface $balanceHandler,
        MoneyWrapperInterface $moneyWrapper,
        BlockedUserManagerInterface $blockedUserManager
    ) {
        $this->tokenManager = $tokenManager;
        $this->decisionManager = $decisionManager;
        $this->container = $container;
        $this->balanceHandler = $balanceHandler;
        $this->moneyWrapper = $moneyWrapper;
        $this->blockedUserManager = $blockedUserManager;
    }

    /**
     * {@inheritdoc}
     */
    protected function supports($attribute, $subject): bool
    {
        if (!in_array($attribute, [
            self::CREATE,
            self::NOT_BLOCKED,
            self::EDIT,
            self::DELETE,
            self::DELETE_FROM_WALLET,
            self::INTERACT,
            self::EXCEED,
        ], true)
        ) {
            return false;
        }

        return $subject instanceof Token || $subject instanceof Crypto || is_null($subject);
    }

    /**
     * {@inheritdoc}
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $securityToken): bool
    {
        /** @psalm-suppress UndefinedDocblockClass */
        $user = $securityToken->getUser();

        if (!$user instanceof User) {
            // the user must be logged in; if not, deny access
            return false;
        }

        if (!$subject || $subject instanceof Crypto) {
            return !$user->isBlocked();
        }

        /** @var Token $cryptoToken */
        $cryptoToken = $subject;

        switch ($attribute) {
            case self::CREATE:
                return $this->canCreateToken($securityToken);
            case self::NOT_BLOCKED:
                return !$cryptoToken->isBlocked();
            case self::EDIT:
            case self::DELETE:
                return $cryptoToken->isOwner($this->tokenManager->getOwnTokens()) && !$cryptoToken->isBlocked();
            case self::DELETE_FROM_WALLET:
                return $this->canDeleteFromWallet($cryptoToken, $user);
            case self::INTERACT:
                return $this->canInteract($user, $cryptoToken->getOwner());
            case self::EXCEED:
                return $this->isTokenLimitExceeded();
        }

        return false;
    }
    private function canCreateToken(TokenInterface $token): bool
    {
        if ($this->isTokenLimitExceeded()) {
            return false;
        }

        if ($this->container->getParameter('auth_make_disable_token_creation')) {
            return $this->decisionManager->decide($token, [User::ROLE_AUTHENTICATED]);
        }

        return true;
    }

    private function canDeleteFromWallet(Token $token, User $user): bool
    {
        $minTokensAmount = $this->moneyWrapper->parse(
            (string)$this->container->getParameter('min_wallet_tokens_amount'),
            Symbols::TOK
        );

        return $token->getOwnerId() !== $user->getId()
            && ($this->checkBalance($user, $token, $minTokensAmount) || $token->isBlocked());
    }

    private function canInteract(User $blockedUser, User $owner): bool
    {
        return !$this->blockedUserManager->findByBlockedUserAndOwner($owner, $blockedUser);
    }

    private function checkBalance(User $user, Token $token, Money $amount): bool
    {
        $available = $this->tokenManager->getRealBalance(
            $token,
            $this->balanceHandler->balance($user, $token),
            $user
        )->getAvailable();

        return $available->lessThan($amount);
    }

    private function isTokenLimitExceeded(): bool
    {
        $limit = (int)$this->container->getParameter('token_create_limit');

        return $this->tokenManager->getTokensCount() >= $limit;
    }
}
