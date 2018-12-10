<?php

namespace App\Manager;

use App\Entity\Crypto;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Exchange\Market;

class MarketManager implements MarketManagerInterface
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

    public function getMarket(Crypto $crypto, Token $token): ?Market
    {
        return new Market($crypto, $token);
    }

    /** {@inheritdoc} */
    public function getAllMarkets(): array
    {
        return $this->getMarkets(
            $this->cryptoManager->findAll(),
            $this->tokenManager->findAll()
        );
    }

    /** {@inheritdoc} */
    public function getUserRelatedMarkets(User $user): array
    {
        return $this->getMarkets(
            $this->cryptoManager->findAll(),
            $user->getRelatedTokens()
        );
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
                $market = $this->getMarket($crypto, $token);

                if (null !== $market) {
                    $markets[] = $market;
                }
            }
        }

        return $markets;
    }
}
