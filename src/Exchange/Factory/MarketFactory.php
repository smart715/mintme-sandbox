<?php declare(strict_types = 1);

namespace App\Exchange\Factory;

use App\Entity\Crypto;
use App\Entity\Token\Token;
use App\Entity\TokenCrypto;
use App\Entity\TradableInterface;
use App\Entity\User;
use App\Exchange\Config\MarketPairsConfig;
use App\Exchange\Market;
use App\Manager\CryptoManagerInterface;
use App\Manager\TokenManagerInterface;
use App\Utils\Symbols;

class MarketFactory implements MarketFactoryInterface
{
    private CryptoManagerInterface $cryptoManager;
    private TokenManagerInterface $tokenManager;
    private MarketPairsConfig $marketPairsConfig;

    public function __construct(
        CryptoManagerInterface $cryptoManager,
        TokenManagerInterface $tokenManager,
        MarketPairsConfig $marketPairsConfig
    ) {
        $this->cryptoManager = $cryptoManager;
        $this->tokenManager = $tokenManager;
        $this->marketPairsConfig = $marketPairsConfig;
    }

    public function createBySymbols(string $baseSymbol, string $quoteSymbol): Market
    {
        $base = $this->cryptoManager->findBySymbol($baseSymbol)
            ?? $this->tokenManager->findByName($baseSymbol);

        $quote = $this->cryptoManager->findBySymbol($quoteSymbol)
            ?? $this->tokenManager->findByName($quoteSymbol);

        if (!$base || !$quote) {
            throw new \Exception("Cryptos or Tokens not found $baseSymbol/$quoteSymbol");
        }

        return $this->create($base, $quote);
    }

    public function create(TradableInterface $crypto, TradableInterface $token): Market
    {
        return new Market($crypto, $token);
    }

    /** {@inheritdoc} */
    public function createAll(): array
    {
        return array_merge(
            $this->getCoinMarkets(),
            $this->getTokenMarkets(
                $this->getExchangableCryptos(),
                $this->tokenManager->findAll()
            )
        );
    }

    /** {@inheritdoc} */
    public function createUserRelated(User $user, bool $deployed = false): array
    {
        return array_merge(
            $this->getCoinMarkets(),
            $this->getTokenMarkets(
                $this->getExchangableCryptos(),
                !$deployed ? $user->getTokens() : $this->tokenManager->getDeployedTokens()
            )
        );
    }

    /** {@inheritdoc} */
    public function createTokenMarkets(Token $token): array
    {
        $tokenMarkets = [];

        /** @var TokenCrypto $tokenCrypto */
        foreach ($token->getExchangeCryptos()->toArray() as $tokenCrypto) {
            $crypto = $tokenCrypto->getCrypto();
            $tokenMarkets[$crypto->getSymbol()] = $this->create($crypto, $token);
        }

        return $tokenMarkets;
    }

    /** @return Crypto[] */
    private function getExchangableCryptos(): array
    {
        return array_filter($this->cryptoManager->findAll(), function (Crypto $crypto) {
            return $crypto->isExchangeble();
        });
    }

    /** @return Crypto[] */
    private function getTradableCryptos(): array
    {
        return array_filter($this->cryptoManager->findAll(), function (Crypto $crypto) {
            return $crypto->isTradable();
        });
    }

    /** {@inheritdoc} */
    public function getCoinMarkets(): array
    {
        $markets = [];
        $enabledPairs = $this->marketPairsConfig->getParsedEnabledPairs();
        $tradableCryptosMap = array_reduce($this->getTradableCryptos(), function ($acc, $crypto) {
            $acc[$crypto->getSymbol()] = $crypto;

            return $acc;
        }, []);

        foreach ($enabledPairs as $pair) {
            if (!isset($tradableCryptosMap[$pair['base']], $tradableCryptosMap[$pair['quote']])) {
                continue;
            }

            $markets[] = $this->create(
                $tradableCryptosMap[$pair['base']],
                $tradableCryptosMap[$pair['quote']]
            );
        }

        return $markets;
    }

    /** {@inheritdoc} */
    public function getMintMeCoinMarkets(): array
    {
        $markets = [];
        $enabledPairs = $this->marketPairsConfig->getParsedEnabledPairs();
        $tradableCryptosMap = array_reduce($this->getTradableCryptos(), static function ($acc, $crypto) {
            $acc[$crypto->getSymbol()] = $crypto;

            return $acc;
        }, []);

        foreach ($enabledPairs as $pair) {
            if (!isset($tradableCryptosMap[$pair['base']], $tradableCryptosMap[$pair['quote']])) {
                continue;
            }

            if (Symbols::WEB !== $pair['quote'] && Symbols::WEB !== $pair['base']) {
                continue;
            }

            $markets[] = $this->create(
                $tradableCryptosMap[$pair['base']],
                $tradableCryptosMap[$pair['quote']]
            );
        }

        return $markets;
    }

    /**
     * @param TradableInterface[] $cryptos
     * @param TradableInterface[] $tokens
     * @return Market[]
     */
    private function getTokenMarkets(array $cryptos, array $tokens): array
    {
        $markets = [];
        $cryptosBySymbol = [];

        /** @var Crypto $crypto */
        foreach ($cryptos as $crypto) {
            $cryptosBySymbol[$crypto->getSymbol()] = $crypto;
        }

        /** @var Token $token */
        foreach ($tokens as $token) {
            /** @var TokenCrypto $tokensCrypto */
            foreach ($token->getExchangeCryptos()->toArray() as $tokensCrypto) {
                $exchangeSymbol = $tokensCrypto->getCrypto()->getSymbol();

                if (isset($cryptosBySymbol[$exchangeSymbol])) {
                    $markets[] = $this->create(
                        $cryptosBySymbol[$exchangeSymbol],
                        $token
                    );
                }
            }
        }

        return $markets;
    }
}
