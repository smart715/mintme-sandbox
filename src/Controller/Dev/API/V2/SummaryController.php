<?php declare(strict_types = 1);

namespace App\Controller\Dev\API\V2;

use App\Exchange\Factory\MarketFactoryInterface;
use App\Exchange\Market\MarketHandlerInterface;
use App\Manager\MarketStatusManagerInterface;
use App\Utils\BaseQuote;
use App\Utils\Converter\RebrandingConverterInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcherInterface;
use Nelmio\ApiDocBundle\Annotation\Security;
use Swagger\Annotations as SWG;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @Rest\Route("/dev/api/v2/open/summary")
 */
class SummaryController extends AbstractFOSRestController
{
    private MarketStatusManagerInterface $marketStatusManager;
    private MarketHandlerInterface $marketHandler;
    private MarketFactoryInterface $marketFactory;
    private RebrandingConverterInterface $rebrandingConverter;

    public function __construct(
        MarketStatusManagerInterface $marketStatusManager,
        MarketHandlerInterface $marketHandler,
        MarketFactoryInterface $marketFactory,
        RebrandingConverterInterface $rebrandingConverter
    ) {
        $this->marketStatusManager = $marketStatusManager;
        $this->marketHandler = $marketHandler;
        $this->marketFactory = $marketFactory;
        $this->rebrandingConverter = $rebrandingConverter;
    }

    /**
     * List summary for all markets
     *
     * @Rest\Get("/")
     * @Rest\View(serializerGroups={"dev"})
     * @Rest\QueryParam(
     *     name="offset",
     *     strict=true,
     *     nullable=true,
     *     requirements=@Assert\Range(min="0"),
     *     default=0
     * )
     * @Rest\QueryParam(
     *     name="limit",
     *     strict=true,
     *     nullable=true,
     *     requirements=@Assert\Range(min="1", max="101"),
     *     default=101
     * )
     * @SWG\Parameter(name="offset", in="query", type="integer", description="Results offset [>=0]")
     * @SWG\Parameter(name="limit", in="query", type="integer", description="Results limit [1-101]")
     * @SWG\Response(
     *     response="200",
     *     description="Returns data for all tickers and all markets with crypto or deployed tokens."
     * )
     * @SWG\Response(response="400",description="Bad request")
     * @SWG\Tag(name="Open")
     * @Security(name="")
     */
    public function getSummary(ParamFetcherInterface $request): array
    {
        $offset = (int)$request->get('offset');
        $limit = (int)$request->get('limit');

        $marketStatuses = $this->marketStatusManager->getCryptoAndDeployedMarketsInfo($offset, $limit);

        return array_map(
            function ($marketStatus) {
                $market = $this->marketFactory->create($marketStatus->getCrypto(), $marketStatus->getQuote());

                $marketStatusToday = $this->marketHandler->getMarketStatus($market);

                $bids = [];
                $asks = [];

                $bids = array_map(
                    static fn ($order) => [$order->getPrice(), $order->getAmount()],
                    $this->marketHandler->getPendingBuyOrders($market)
                );

                $asks = array_map(
                    static fn ($order) => [$order->getPrice(), $order->getAmount()],
                    $this->marketHandler->getPendingSellOrders($market)
                );

                $market = BaseQuote::reverseMarket($market);

                $rebrandedBaseSymbol = $this->rebrandingConverter->convert($market->getBase()->getSymbol());
                $rebrandedQuoteSymbol = $this->rebrandingConverter->convert($market->getQuote()->getSymbol());

                return [
                    'trading_pairs' => $rebrandedBaseSymbol . '_' . $rebrandedQuoteSymbol,
                    'last_price' => $marketStatusToday['last'],
                    'base_currency' => $rebrandedBaseSymbol,
                    'quote_currency' => $rebrandedQuoteSymbol,
                    'lowest_ask' => $asks ? min($asks)[0] : 0,
                    'highest_bid' => $bids ? max($bids)[0] : 0,
                    'base_volume' => $marketStatusToday['deal'],
                    'quote_volume' => $marketStatusToday['volume'],
                    'price_change_percent_24h' =>
                        '0' !== $marketStatusToday['open'] ?
                            round(
                                (
                                    ($marketStatusToday['last'] - $marketStatusToday['open']) *
                                    100 /
                                    $marketStatusToday['open']
                                ),
                                2
                            ) :
                            0,
                    'highest_price_24h' => $marketStatusToday['high'],
                    'lowest_price_24h' => $marketStatusToday['low'],
                ];
            },
            array_values($marketStatuses)
        );
    }
}
