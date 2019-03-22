<?php

namespace App\Exchange\Factory;

use App\Entity\Crypto;
use App\Entity\Token\Token;
use App\Entity\TradebleInterface;
use App\Entity\User;
use App\Exchange\Market;
use App\Manager\CryptoManagerInterface;
use App\Manager\TokenManagerInterface;

class MarketFactory implements MarketFactoryInterface
{
    /** @var CryptoManagerInterface */
    private $cryptoManager;

    /** @var TokenManagerInterface */
    private $tokenManager;

    public function __construct(
        CryptoManagerInterface $cryptoManager,
        TokenManagerInterface $tokenManager
    ) {
        $this->cryptoManager = $cryptoManager;
        $this->tokenManager = $tokenManager;
    }

    public function create(TradebleInterface $crypto, TradebleInterface $token): Market
    {
        return new Market($crypto, $token);
    }

    /** {@inheritdoc} */
    public function createAll(): array
    {
        return $this->getMarkets(
            $this->getExchangableCryptos(),
            $this->tokenManager->findAll()
        );
    }

    /** {@inheritdoc} */
    public function createUserRelated(User $user): array
    {
        return $this->getMarkets(
            $this->getExchangableCryptos(),
            $user->getRelatedTokens()
        );
    }

    /** @return Crypto[] */
    private function getExchangableCryptos(): array
    {
        return array_filter($this->cryptoManager->findAll(), function (Crypto $crypto) {
            return $crypto->isExchangeble();
        });
    }

    /**
     * @param Crypto[] $cryptos
     * @param Token[] $tokens
     * @return Market[]
     */
    private function getMarkets(array $cryptos, array $tokens): array
    {
        $markets = [];

        foreach ($cryptos as $crypto) {
            foreach ($tokens as $token) {
                $market = $this->create($crypto, $token);

                if (null !== $market) {
                    $markets[] = $market;
                }
            }
        }

        return $markets;
    }
}
