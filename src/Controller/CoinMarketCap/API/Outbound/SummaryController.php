<?php declare(strict_types = 1);

namespace App\Controller\CoinMarketCap\API\Outbound;

use App\Exchange\Factory\MarketFactoryInterface;
use App\Exchange\Market\MarketHandlerInterface;
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

    /** @var MarketHandlerInterface */
    private $marketHandler;

    /** @var MarketFactoryInterface */
    private $marketFactory;

    /** @var TraderInterface */
    private $trader;

    public function __construct(
        MarketStatusManagerInterface $marketStatusManager,
        MarketHandlerInterface $marketHandler,
        MarketFactoryInterface $marketFactory,
        TraderInterface $trader
    ) {
        $this->marketStatusManager = $marketStatusManager;
        $this->marketHandler = $marketHandler;
        $this->marketFactory = $marketFactory;
        $this->trader = $trader;
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
                $marketStatusToday = $this->marketHandler->getMarketStatus($market);

                return [
                    'trading_pairs' => $market->getQuote()->getSymbol().'_'.$market->getBase()->getSymbol(),
                    'last_price' => $marketStatusToday['last'],
                    'base_currency' => $market->getBase()->getSymbol(),
                    'quote_currency' => $market->getQuote()->getSymbol(),
                    'lowest_ask' => $orderDepth['asks'] ? min($orderDepth['asks'])[0] : '',
                    'highest_bid' => $orderDepth['bids'] ? max($orderDepth['bids'])[0] : '',
                    'base_volume' => $marketStatusToday['deal'],
                    'quote_volume' => $marketStatusToday['volume'],
                    'price_change_percent_24h' => $marketStatusToday['open'] ?
                        ($marketStatusToday['last'] - $marketStatusToday['open']) * 100 / $marketStatusToday['open']
                        :
                        0,
                    'highest_price_24h' => $marketStatusToday['high'],
                    'lowest_price_24h' => $marketStatusToday['low'],
                ];
            },
            array_values($marketStatuses)
        );
    }
}
