<?php declare(strict_types = 1);

namespace App\Controller\Dev\API\V2;

use App\Controller\Traits\BaseQuoteOrderTrait;
use App\Exchange\Factory\MarketFactoryInterface;
use App\Exchange\Market\MarketHandlerInterface;
use App\Exchange\Trade\TraderInterface;
use App\Manager\MarketStatusManagerInterface;
use App\Utils\Converter\RebrandingConverterInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;

/**
 * @Rest\Route("/dev/api/v2/public/summary")
 */
class SummaryController extends AbstractFOSRestController
{

    use BaseQuoteOrderTrait;

    /** @var MarketStatusManagerInterface */
    private $marketStatusManager;

    /** @var MarketHandlerInterface */
    private $marketHandler;

    /** @var MarketFactoryInterface */
    private $marketFactory;

    /** @var TraderInterface */
    private $trader;

    /** @var RebrandingConverterInterface */
    private $rebrandingConverter;

    public function __construct(
        MarketStatusManagerInterface $marketStatusManager,
        MarketHandlerInterface $marketHandler,
        MarketFactoryInterface $marketFactory,
        TraderInterface $trader,
        RebrandingConverterInterface $rebrandingConverter
    ) {
        $this->marketStatusManager = $marketStatusManager;
        $this->marketHandler = $marketHandler;
        $this->marketFactory = $marketFactory;
        $this->trader = $trader;
        $this->rebrandingConverter = $rebrandingConverter;
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
            function ($marketStatus) {
                $market = $this->marketFactory->create($marketStatus->getCrypto(), $marketStatus->getQuote());

                $orderDepth = $this->trader->getOrderDepth($market);
                $marketStatusToday = $this->marketHandler->getMarketStatus($market);

                $this->fixBaseQuoteOrder($market);

                $rebrandedBaseSymbol = $this->rebrandingConverter->convert($market->getBase()->getSymbol());
                $rebrandedQuoteSymbol = $this->rebrandingConverter->convert($market->getQuote()->getSymbol());

                return [
                    'trading_pairs' => $rebrandedBaseSymbol . '_' . $rebrandedQuoteSymbol,
                    'last_price' => $marketStatusToday['last'],
                    'base_currency' => $rebrandedBaseSymbol,
                    'quote_currency' => $rebrandedQuoteSymbol,
                    'lowest_ask' => $orderDepth['asks'] ? min($orderDepth['asks'])[0] : '',
                    'highest_bid' => $orderDepth['bids'] ? max($orderDepth['bids'])[0] : '',
                    'base_volume' => $marketStatusToday['deal'],
                    'quote_volume' => $marketStatusToday['volume'],
                    'price_change_percent_24h' =>
                        $marketStatusToday['open'] ?
                            ($marketStatusToday['last'] - $marketStatusToday['open']) * 100 / $marketStatusToday['open'] :
                            0,
                    'highest_price_24h' => $marketStatusToday['high'],
                    'lowest_price_24h' => $marketStatusToday['low'],
                ];
            },
            array_values($marketStatuses)
        );
    }
}
