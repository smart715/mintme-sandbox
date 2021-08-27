<?php declare(strict_types = 1);

namespace App\Exchange\Factory;

use App\Entity\Crypto;
use App\Entity\Token\Token;
use App\Entity\TradebleInterface;
use App\Entity\User;
use App\Exchange\Market;
use App\Manager\CryptoManagerInterface;
use App\Manager\TokenManagerInterface;
use App\Utils\Symbols;

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

    /** {@inheritdoc} */
    public function getCoinMarkets(): array
    {
        $markets = [];

        $bases = $this->getTradableCryptos();
        $quote = $this->cryptoManager->findBySymbol(Symbols::WEB);

        if (!$quote) {
            return $markets;
        }

        /** @var Crypto $base */
        foreach ($bases as $base) {
            if ($base->getSymbol() !== $quote->getSymbol()) {
                $markets[] = $this->create($base, $quote);
            }
        }

        return $markets;
    }

    /**
     * @param TradebleInterface[] $cryptos
     * @param TradebleInterface[] $tokens
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
            if (isset($cryptosBySymbol[$token->getExchangeCryptoSymbol()])) {
                $markets[] = $this->create(
                    $cryptosBySymbol[$token->getExchangeCryptoSymbol()],
                    $token
                );
            }
        }

        return $markets;
    }
}
