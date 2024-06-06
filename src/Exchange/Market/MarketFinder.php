<?php declare(strict_types = 1);

namespace App\Exchange\Market;

use App\Entity\Token\Token;
use App\Exception\NotDeployedTokenException;
use App\Exchange\Factory\MarketFactoryInterface;
use App\Exchange\Market;
use App\Manager\CryptoManagerInterface;
use App\Manager\TokenManagerInterface;

class MarketFinder implements MarketFinderInterface
{
    /** @var CryptoManagerInterface */
    private $cryptoManager;

    /** @var TokenManagerInterface */
    private $tokenManager;

    /** @var MarketFactoryInterface */
    private $marketFactory;

    public function __construct(
        CryptoManagerInterface $cryptoManager,
        TokenManagerInterface $tokenManager,
        MarketFactoryInterface $marketFactory
    ) {
        $this->cryptoManager = $cryptoManager;
        $this->tokenManager = $tokenManager;
        $this->marketFactory = $marketFactory;
    }

    public function find(string $base, string $quote, bool $onlyDeployed = false): ?Market
    {
        $base = $this->cryptoManager->findBySymbol($base) ?? $this->tokenManager->findByName($base);
        $quote =  $this->cryptoManager->findBySymbol($quote) ?? $this->tokenManager->findByName($quote);

        $isTokenAndNotDeployed = ($base instanceof Token && !$base->isDeployed()) ||
            ($quote instanceof Token && !$quote->isDeployed());

        if ($onlyDeployed && $isTokenAndNotDeployed) {
            throw new NotDeployedTokenException();
        }

        return ($base && $quote) && ($base !== $quote)
            ? $this->marketFactory->create($base, $quote)
            : null;
    }
}
