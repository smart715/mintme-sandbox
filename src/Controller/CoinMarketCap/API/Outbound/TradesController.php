<?php declare(strict_types = 1);

namespace App\Controller\CoinMarketCap\API\Outbound;

use App\Exception\ApiNotFoundException;
use App\Exchange\Market;
use App\Exchange\Market\MarketFetcherInterface;
use App\Manager\CryptoManagerInterface;
use App\Manager\TokenManagerInterface;
use App\Utils\Converter\MarketNameConverterInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;

/**
 * @Rest\Route("/cmc/api/v1/trades")
 */
class TradesController extends AbstractFOSRestController
{
    /** @var MarketFetcherInterface */
    private $marketFetcher;

    /** @var CryptoManagerInterface */
    private $cryptoManager;

    /** @var MarketNameConverterInterface */
    private $marketNameConverter;

    /** @var TokenManagerInterface */
    private $tokenManager;

    public function __construct(
        MarketFetcherInterface $marketFetcher,
        CryptoManagerInterface $cryptoManager,
        MarketNameConverterInterface $marketNameConverter,
        TokenManagerInterface $tokenManager
    ) {
        $this->marketFetcher = $marketFetcher;
        $this->cryptoManager = $cryptoManager;
        $this->marketNameConverter = $marketNameConverter;
        $this->tokenManager = $tokenManager;
    }

    /**
     * Get data on all recently completed trades for a given market pair.
     *
     * @Rest\Get("/{market_pair}")
     * @Rest\View()
     */
    public function getTrades(
        string $market_pair
    ): array {
        $marketNames = explode('_', $market_pair);
        $base = $marketNames[0] ?? '';
        $quote = $marketNames[1] ?? '';
        $base = $this->cryptoManager->findBySymbol($base);
        $quote = $this->cryptoManager->findBySymbol($quote) ?? $this->tokenManager->findByName($quote);

        if (is_null($base) || is_null($quote)) {
            throw new ApiNotFoundException('Market not found');
        }

        $market = new Market($base, $quote);

        return $this->marketFetcher->getExecutedOrders($this->marketNameConverter->convert($market));
    }
}
