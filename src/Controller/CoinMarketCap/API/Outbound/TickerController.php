<?php declare(strict_types = 1);

namespace App\Controller\CoinMarketCap\API\Outbound;

use App\Controller\Traits\BaseQuoteOrder;
use App\Exchange\Factory\MarketFactoryInterface;
use App\Exchange\Market\MarketHandlerInterface;
use App\Exchange\Trade\TraderInterface;
use App\Manager\MarketStatusManagerInterface;
use App\Utils\Converter\RebrandingConverterInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;

/**
 * @Rest\Route("/cmc/api/v1/ticker")
 */
class TickerController extends AbstractFOSRestController
{
    use BaseQuoteOrder;

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
     * Get 24-hour pricing and volume summary for each market pair available on the exchange.
     *
     * @Rest\Get("/")
     * @Rest\View()
     */
    public function getTicker(): array
    {
        $assets = [];
        $marketStatuses = $this->marketStatusManager->getAllMarketsInfo();

        return array_map(
            function ($marketStatus) {
                $market = $this->marketFactory->create($marketStatus->getCrypto(), $marketStatus->getQuote());

                $orderDepth = $this->trader->getOrderDepth($market);
                $marketStatusToday = $this->marketHandler->getMarketStatus($market);

                $this->fixBaseQuoteOrder($market);

                $rebrandedBaseSymbol = $this->rebrandingConverter->convert($market->getBase()->getSymbol());
                $rebrandedQuoteSymbol = $this->rebrandingConverter->convert($market->getQuote()->getSymbol());

                $assets[$rebrandedBaseSymbol . '_' . $rebrandedQuoteSymbol] = [
                    'base_id' => $market->getBase()->getId(),
                    'quote_id' => $market->getQuote()->getId(),
                    'last_price' => $marketStatusToday['last'],
                    'quote_volume' => $marketStatusToday['volume'],
                    'base_volume' => $marketStatusToday['deal'],
                    'isFrozen' => $market->getBase()->isBlocked(),
                ];

                return $assets;
            },
            array_values($marketStatuses)
        );
    }
}
