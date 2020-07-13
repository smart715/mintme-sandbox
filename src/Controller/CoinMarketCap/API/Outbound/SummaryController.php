<?php declare(strict_types = 1);

namespace App\Controller\CoinMarketCap\API\Outbound;

use App\Exchange\Market\MarketFetcherInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;

/**
 * @Rest\Route("/cmc/api/v1/summary")
 */
class SummaryController extends AbstractFOSRestController
{
    /** @var MarketFetcherInterface */
    private $marketFetcher;

    public function __construct(
        MarketFetcherInterface $marketFetcher
    ) {
        $this->marketFetcher = $marketFetcher;
    }

    /**
     * Get data for all tickers and all markets.
     *
     * @Rest\Get("/")
     * @Rest\View()
     */
    public function getSummary(): array
    {
        return $this->marketFetcher->getMarketList();
    }
}
