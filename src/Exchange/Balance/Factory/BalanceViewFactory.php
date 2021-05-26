<?php declare(strict_types = 1);

namespace App\Exchange\Balance\Factory;

use App\Entity\Token\Token;
use App\Entity\User;
use App\Exchange\Balance\Model\BalanceResultContainer;
use App\Manager\TokenManagerInterface;
use App\Utils\Converter\TokenNameConverterInterface;

class BalanceViewFactory implements BalanceViewFactoryInterface
{
    /** @var TokenManagerInterface */
    private $tokenManager;

    /** @var TokenNameConverterInterface */
    private $tokenNameConverter;

    /** @var int */
    private $tokenSubunit;

    public function __construct(
        TokenManagerInterface $tokenManager,
        TokenNameConverterInterface $tokenNameConverter,
        int $tokenSubunit
    ) {
        $this->tokenManager = $tokenManager;
        $this->tokenNameConverter = $tokenNameConverter;
        $this->tokenSubunit = $tokenSubunit;
    }

    /** {@inheritdoc} */
    public function create(BalanceResultContainer $container, ?User $user = null): array
    {
        $result = [];

        foreach ($container as $key => $balanceResult) {
            $token = $this->tokenManager->findByName($key) ?? $this->tokenManager->findByHiddenName($key);

            if (!$token) {
                continue;
            }

            $name = $token->getName();
            $fee = $token->getId()
                ? $token->getFee()
                : $token->getCrypto()->getFee();
            $subunit = $this->tokenSubunit;

            $owner = !is_null($user) && !is_null($token->getProfile())
                ? $user->getId() === $token->getProfile()->getUser()->getId()
                : false;

            if (!$token->getId() && $token->getCrypto()) {
                $name = $token->getCrypto()->getName();
                $subunit = $token->getCrypto()->getShowSubunit();
            }

            $result[$token->getName()] = new BalanceView(
                $this->tokenNameConverter->convert($token),
                $this->tokenManager->getRealBalance(
                    $token,
                    $balanceResult
                )->getAvailable(),
                $token->getLockIn() ? $token->getLockIn()->getFrozenAmount() : null,
                $name,
                $fee,
                null === $token->getDecimals() || $token->getDecimals() > $subunit ? $subunit : $token->getDecimals(),
                $token->getCrypto() ? $token->getCrypto()->isExchangeble() : false,
                $token->getCrypto() ? $token->getCrypto()->isTradable() : false,
                Token::DEPLOYED === $token->getDeploymentStatus(),
                $owner,
                $token->isBlocked(),
                $token->getCryptoSymbol(),
            );
        }

        return $result;
    }
}
