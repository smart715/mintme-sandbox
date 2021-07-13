<?php declare(strict_types = 1);

namespace App\Exchange\Balance\Factory;

use App\Entity\Token\Token;
use App\Entity\User;
use App\Exchange\Balance\Model\BalanceResultContainer;
use App\Manager\CryptoManagerInterface;
use App\Manager\TokenManagerInterface;
use App\Utils\Converter\TokenNameConverterInterface;

class BalanceViewFactory implements BalanceViewFactoryInterface
{
    /** @var CryptoManagerInterface */
    private $cryptoManager;

    /** @var TokenManagerInterface */
    private $tokenManager;

    /** @var TokenNameConverterInterface */
    private $tokenNameConverter;

    /** @var int */
    private $tokenSubunit;

    public function __construct(
        CryptoManagerInterface $cryptoManager,
        TokenManagerInterface $tokenManager,
        TokenNameConverterInterface $tokenNameConverter,
        int $tokenSubunit
    ) {
        $this->cryptoManager = $cryptoManager;
        $this->tokenManager = $tokenManager;
        $this->tokenNameConverter = $tokenNameConverter;
        $this->tokenSubunit = $tokenSubunit;
    }

    /** {@inheritdoc} */
    public function create(BalanceResultContainer $container, User $user): array
    {
        $result = [];

        foreach ($container as $key => $balanceResult) {
            $token = $this->tokenManager->findByName($key) ?? $this->tokenManager->findByHiddenName($key);
            $crypto = $this->cryptoManager->findBySymbol($key);

            if (!$token && !$crypto) {
                continue;
            }

            if ($token) {
                $name = $token->getName();
                $fee = $token->getFee();
                $subunit = $this->tokenSubunit;

                $owner = null !== $token->getProfile() && $user->getId() === $token->getProfile()->getUser()->getId();
            } else {
                $name = $crypto->getSymbol();
                $fee = $crypto->getFee();
                $subunit = $crypto->getShowSubunit();

                $owner = false;
            }

            $result[$name] = new BalanceView(
                $this->tokenNameConverter->convert($token ?? $crypto),
                $token
                    ? $this->tokenManager->getRealBalance(
                        $token,
                        $balanceResult,
                        $user
                    )->getAvailable()
                    : $balanceResult->getAvailable(),
                $token && $token->getLockIn() ? $token->getLockIn()->getFrozenAmount() : null,
                $name,
                $fee,
                $token
                    ? null === $token->getDecimals() || $token->getDecimals() > $subunit ? $subunit : $token->getDecimals()
                    : false,
                $token && $token->getCrypto() ? $token->getCrypto()->isExchangeble() : false,
                $token && $token->getCrypto() ? $token->getCrypto()->isTradable() : false,
                $token ? Token::DEPLOYED === $token->getDeploymentStatus() : false,
                $owner,
                $token ? $token->isBlocked() : false,
                $token ? $token->getCryptoSymbol() : false,
            );
        }

        return $result;
    }
}
