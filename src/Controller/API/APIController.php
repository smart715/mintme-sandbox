<?php declare(strict_types = 1);

namespace App\Controller\API;

use App\Exchange\Factory\MarketFactoryInterface;
use App\Exchange\Market;
use App\Manager\CryptoManagerInterface;
use App\Manager\TokenManagerInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;

abstract class APIController extends AbstractFOSRestController
{
    protected TokenManagerInterface $tokenManager;
    protected CryptoManagerInterface $cryptoManager;
    protected MarketFactoryInterface $marketFactory;

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

    /**
     * @param array<string>|null $intProps
     * @param array<string>|null $floatProps
     * @param \Throwable|null $exception
     */
    protected function validateProperties(?array $intProps = null, ?array $floatProps = null, ?\Throwable $exception = null): void
    {
        $intProps = $intProps ?? ['0'];
        $floatProps = $floatProps ?? ['0'];
        $exception = $exception ?? new \InvalidArgumentException('Invalid arguments');

        foreach ($intProps as $value) {
            if (!preg_match('/^\d+$/', (string) $value)) {
                throw $exception;
            }
        }

        foreach ($floatProps as $value) {
            if (!preg_match('/^\d+(\.\d+)?$/', (string) $value)) {
                throw $exception;
            }
        }
    }
}
