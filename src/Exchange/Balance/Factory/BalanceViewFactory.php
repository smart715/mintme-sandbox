<?php declare(strict_types = 1);

namespace App\Exchange\Balance\Factory;

use App\Entity\Crypto;
use App\Entity\Token\Token;
use App\Entity\TradableInterface;
use App\Entity\User;
use App\Exchange\Balance\Model\BalanceResult;
use App\Exchange\Balance\Model\BalanceResultContainer;
use App\Manager\TokenManagerInterface;
use App\Manager\UserTokenManagerInterface;
use App\Utils\Converter\TokenNameConverterInterface;

class BalanceViewFactory implements BalanceViewFactoryInterface
{
    private TokenManagerInterface $tokenManager;
    private UserTokenManagerInterface $userTokenManager;
    private TokenNameConverterInterface $tokenNameConverter;
    private int $tokenSubunit;

    public function __construct(
        TokenManagerInterface $tokenManager,
        UserTokenManagerInterface $userTokenManager,
        TokenNameConverterInterface $tokenNameConverter,
        int $tokenSubunit
    ) {
        $this->tokenManager = $tokenManager;
        $this->userTokenManager = $userTokenManager;
        $this->tokenNameConverter = $tokenNameConverter;
        $this->tokenSubunit = $tokenSubunit;
    }

    /** {@inheritdoc} */
    public function create(
        array $tradablesProp,
        BalanceResultContainer $container,
        User $user
    ): array {
        /** @var TradableInterface[] $tradables */
        $tradables = [];

        foreach ($tradablesProp as $tradable) {
            $key = $this->tokenNameConverter->convert($tradable);
            $tradables[$key] = $tradable;
        }

        /** @var BalanceView[] $result */
        $result = [];

        foreach ($container as $key => $balanceResult) {
            $tradable = $tradables[$key] ?? null;

            if (!$tradables) {
                continue;
            }

            if ($tradable instanceof Token) {
                $result[$tradable->getSymbol()] = $this->setUpTokenBalanceView(
                    $tradable,
                    $balanceResult,
                    $user
                );
            }

            if ($tradable instanceof Crypto) {
                $result[$tradable->getSymbol()] = $this->setUpCryptoBalanceView(
                    $tradable,
                    $balanceResult
                );
            }
        }

        return $result;
    }

    private function setUpTokenBalanceView(
        Token $token,
        BalanceResult $balanceResult,
        User $user
    ): BalanceView {
        $realBalance = $this->tokenManager->getRealBalance(
            $token,
            $balanceResult,
            $user
        )->getAvailable();

        $subunit = null === $token->getDecimals() || $token->getDecimals() > $this->tokenSubunit
            ? $this->tokenSubunit
            : $token->getDecimals();

        $owner = null !== $token->getProfile() && $user->getId() === $token->getProfile()->getUser()->getId();
        $isRemoved = $this->userTokenManager->findByUserToken($user, $token)->isRemoved();

        return new BalanceView(
            $this->tokenNameConverter->convert($token),
            $realBalance,
            $token->getLockIn() ? $token->getLockIn()->getFrozenAmount() : null,
            $balanceResult->getBonus(),
            $token->getName(),
            $token->getFee(),
            $subunit,
            false,
            false,
            $token->isDeployed(),
            $owner,
            $token->isBlocked(),
            true,
            $token->getCryptoSymbol(),
            $token->isCreatedOnMintmeSite(),
            $isRemoved,
            $token->getHasTax(),
            $token->getIsPausable()
        );
    }
    private function setUpCryptoBalanceView(
        Crypto $crypto,
        BalanceResult $balanceResult
    ): BalanceView {
        return new BalanceView(
            $this->tokenNameConverter->convert($crypto),
            $balanceResult->getAvailable(),
            null,
            $balanceResult->getBonus(),
            $crypto->getName(),
            $crypto->getFee(),
            $crypto->getShowSubunit(),
            $crypto->isExchangeble(),
            $crypto->isTradable(),
            false,
            false,
            false,
            $crypto->isToken(),
            $crypto->getSymbol(),
            false,
            false,
            false,
            false,
        );
    }
}
