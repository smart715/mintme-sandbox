<?php declare(strict_types = 1);

namespace App\Controller\CoinMarketCap\API\Outbound;

use App\Exchange\Market\MarketFetcherInterface;
use App\Exchange\Market\MarketHandlerInterface;
use App\Manager\MarketStatusManagerInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;

/**
 * @Rest\Route("/cmc/api/v1/summary")
 */
class SummaryController extends AbstractFOSRestController
{
    /** @var MarketStatusManagerInterface */
    private $marketStatusManager;

    /** @var MarketFetcherInterface */
    private $marketFetcher;

    /** @var MarketHandlerInterface */
    private $marketHandler;

    public function __construct(
        MarketStatusManagerInterface $marketStatusManager,
        MarketFetcherInterface $marketFetcher,
        MarketHandlerInterface $marketHandler
    ) {
        $this->marketStatusManager = $marketStatusManager;
        $this->marketFetcher = $marketFetcher;
        $this->marketHandler = $marketHandler;
    }

    /**
     * Get data for all tickers and all markets.
     *
     * @Rest\Get("/")
     * @Rest\View()
     */
    public function getSummary(): array
    {
        $markets = $this->marketStatusManager->getAllMarketsInfo();

        return $markets;
    }
}
