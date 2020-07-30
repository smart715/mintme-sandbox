<?php declare(strict_types = 1);

namespace App\Controller\CoinMarketCap\API\Outbound;

use App\Controller\Traits\BaseQuoteOrder;
use App\Exception\ApiNotFoundException;
use App\Exchange\Market;
use App\Exchange\Market\MarketFinderInterface;
use App\Exchange\Trade\TraderInterface;
use App\Manager\CryptoManagerInterface;
use App\Manager\TokenManagerInterface;
use App\Utils\Converter\RebrandingConverterInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcherInterface;
use Safe\DateTimeImmutable;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @Rest\Route("/cmc/api/v1/orderbook")
 */
class OrderbookController extends AbstractFOSRestController
{

    use BaseQuoteOrder;

    /** @var CryptoManagerInterface */
    private $cryptoManager;

    /** @var TokenManagerInterface */
    private $tokenManager;

    /** @var TraderInterface */
    private $trader;

    /** @var MarketFinderInterface */
    private $marketFinder;

    /** @var RebrandingConverterInterface */
    private $rebrandingConverter;

    public function __construct(
        TokenManagerInterface $tokenManager,
        CryptoManagerInterface $cryptoManager,
        TraderInterface $trader,
        MarketFinderInterface $marketFinder,
        RebrandingConverterInterface $rebrandingConverter
    ) {
        $this->tokenManager = $tokenManager;
        $this->cryptoManager = $cryptoManager;
        $this->trader = $trader;
        $this->marketFinder = $marketFinder;
        $this->rebrandingConverter = $rebrandingConverter;
    }
    /**
     * Get complete level 2 order book (arranged by best asks/bids) with full depth returned for a given market pair.
     *
     * @Rest\Get("/{market_pair}")
     * @Rest\QueryParam(
     *     name="limit",
     *     default="100",
     *     requirements=@Assert\Range(min="1", max="101"),
     *     nullable=false,
     *     allowBlank=false,
     *     strict=true
     * )
     * @Rest\QueryParam(
     *     name="interval",
     *     default="0",
     *     nullable=false,
     *     allowBlank=false,
     *     strict=true
     * )
     * @Rest\View()
     */
    public function getOrderBook(ParamFetcherInterface $request, string $market_pair)
    {
        $marketPair = explode('_', $market_pair);
        $base = $marketPair[0] ?? '';
        $quote = $marketPair[1] ?? '';

        $base = $this->rebrandingConverter->reverseConvert(mb_strtolower($base));
        $quote = $this->rebrandingConverter->reverseConvert(mb_strtolower($quote));

        $market = $this->marketFinder->find($base, $quote);

        if (!$market) {
            throw new ApiNotFoundException('Market pair not found');
        }

        $this->fixBaseQuoteOrder($market);

        $orderDepth = $this->trader->getOrderDepth(
            $market,
            [
                'limit' => (int)$request->get('limit'),
                'interval' => (string)$request->get('interval'),
            ]
        );

        $date = new DateTimeImmutable();
        $timestamp = array('timestamp' => $date->getTimestamp());
        $orderDepth[] = $timestamp + $orderDepth;

        return $orderDepth;
    }
}
