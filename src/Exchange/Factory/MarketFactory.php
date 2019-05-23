<?php declare(strict_types = 1);

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
        return array_merge(
            $this->getCoinMarkets(),
            $this->getMarkets(
                $this->getExchangableCryptos(),
                $this->tokenManager->findAll()
            )
        );
    }

    /** {@inheritdoc} */
    public function createUserRelated(User $user): array
    {
        return array_merge(
            $this->getCoinMarkets(),
            $this->getMarkets(
                $this->getExchangableCryptos(),
                $user->getRelatedTokens()
            )
        );
    }

    public function createPredefined(): array
    {
        return array_merge(
            $this->getCoinMarkets(),
            $this->getMarkets(
                $this->getExchangableCryptos(),
                $this->getExchangableCryptos()
            )
        );
    }

    /** @return Crypto[] */
    private function getExchangableCryptos(): array
    {
        return array_filter($this->cryptoManager->findAll(), function (Crypto $crypto) {
            return $crypto->isExchangeble();
        });
    }

    private function getTradableCryptos(): array
    {
        return array_filter($this->cryptoManager->findAll(), function (Crypto $crypto) {
            return $crypto->isTradable();
        });
    }

    /** @return Market[] */
    private function getCoinMarkets(): array
    {
        return $this->getMarkets(
            $this->getTradableCryptos(),
            $this->getExchangableCryptos()
        );
    }

    /**
     * @param TradebleInterface[] $bases
     * @param TradebleInterface[] $quotes
     * @return Market[]
     */
    private function getMarkets(array $bases, array $quotes): array
    {
        $markets = [];

        foreach ($bases as $base) {
            foreach ($quotes as $quote) {
                if ($base === $quote) {
                    continue;
                }

                $markets[] = $this->create($base, $quote);
            }
        }

        return $markets;
    }
}
