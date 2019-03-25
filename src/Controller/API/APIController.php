<?php declare(strict_types = 1);

namespace App\Controller\API;

use App\Exchange\Factory\MarketFactoryInterface;
use App\Exchange\Market;
use App\Manager\CryptoManagerInterface;
use App\Manager\TokenManagerInterface;
use FOS\RestBundle\Controller\FOSRestController;

abstract class APIController extends FOSRestController
{
    /** @var TokenManagerInterface */
    private $tokenManager;

    /** @var CryptoManagerInterface */
    private $cryptoManager;

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

    protected function getMarket(string $base, string $quote): ?Market
    {
        $base = $this->cryptoManager->findBySymbol($base) ?? $this->tokenManager->findByName($base);
        $quote = $this->cryptoManager->findBySymbol($quote) ?? $this->tokenManager->findByName($quote);

        return ($base && $quote) && ($base !== $quote)
            ? $this->marketFactory->create($base, $quote)
            : null;
    }
}
