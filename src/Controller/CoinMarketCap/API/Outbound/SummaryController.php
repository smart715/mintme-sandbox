<?php declare(strict_types = 1);

namespace App\Controller\CoinMarketCap\API\Outbound;

use App\Exchange\Factory\MarketFactoryInterface;
use App\Exchange\Market\MarketFetcherInterface;
use App\Exchange\Market\MarketHandlerInterface;
use App\Exchange\Trade\TraderFetcherInterface;
use App\Exchange\Trade\TraderInterface;
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

    /** @var MarketFactoryInterface */
    private $marketFactory;

    /** @var TraderInterface */
    private $trader;

    /** @var TraderFetcherInterface */
    private $traderFetcher;

    public function __construct(
        MarketStatusManagerInterface $marketStatusManager,
        MarketFetcherInterface $marketFetcher,
        MarketHandlerInterface $marketHandler,
        MarketFactoryInterface $marketFactory,
        TraderInterface $trader,
        TraderFetcherInterface $traderFetcher
    ) {
        $this->marketStatusManager = $marketStatusManager;
        $this->marketFetcher = $marketFetcher;
        $this->marketHandler = $marketHandler;
        $this->marketFactory = $marketFactory;
        $this->trader = $trader;
        $this->traderFetcher = $traderFetcher;
    }

    /**
     * Get data for all tickers and all markets.
     *
     * @Rest\Get("/")
     * @Rest\View()
     */
    public function getSummary(): array
    {
        $marketStatuses = $this->marketStatusManager->getAllMarketsInfo();
        return array_map(
            function($marketStatus) {
                $market = $this->marketFactory->create($marketStatus->getCrypto(), $marketStatus->getQuote());
                $orderDepth = $this->trader->getOrderDepth($market);
                return [
                    'trading_pairs' => $market->getQuote()->getSymbol().'_'.$market->getBase()->getSymbol(),
                    'last_price' => $marketStatus->getLastPrice(),
                    'base_currency' => $market->getBase()->getSymbol(),
                    'quote_currency' => $market->getQuote()->getSymbol(),
                    'lowest_ask' => $orderDepth['asks'] ? min($orderDepth['asks'])[0] : '',
                    'highest_bid' => $orderDepth['bids'] ? max($orderDepth['bids'])[0] : '',
                    'base_volume' => '',
                    'quote_volume' => $marketStatus->getDayVolume(),
                    'price_change_percent_24h' => '',
                    'highest_price_24h' => '',
                    'lowest_price_24h' => '',
                ];
            },
            array_values($marketStatuses)
        );
    }
}
