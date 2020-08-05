<?php declare(strict_types = 1);

namespace App\Controller\Dev\API\V2;

use App\Controller\Traits\BaseQuoteOrderTrait;
use App\Exception\ApiNotFoundException;
use App\Exchange\Market\MarketFinderInterface;
use App\Exchange\Trade\TraderInterface;
use App\Utils\Converter\RebrandingConverterInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcherInterface;
use Safe\DateTimeImmutable;
use Swagger\Annotations as SWG;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @Rest\Route("/dev/api/v2/public/orderbook")
 */
class OrderbookController extends AbstractFOSRestController
{

    use BaseQuoteOrderTrait;

    /** @var TraderInterface */
    private $trader;

    /** @var MarketFinderInterface */
    private $marketFinder;

    /** @var RebrandingConverterInterface */
    private $rebrandingConverter;

    public function __construct(
        TraderInterface $trader,
        MarketFinderInterface $marketFinder,
        RebrandingConverterInterface $rebrandingConverter
    ) {
        $this->trader = $trader;
        $this->marketFinder = $marketFinder;
        $this->rebrandingConverter = $rebrandingConverter;
    }
    /**
     * Get order book
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
     * @Rest\View(serializerGroups={"dev"})
     * @SWG\Response(
     *     response="200",
     *     description="Returns complete level 2 order book (arranged by best asks/bids) with full depth returned for a given market pair."
     * )
     * @SWG\Response(response="400",description="Bad request")
     * @SWG\Tag(name="Public")
     */
    public function getOrderbook(ParamFetcherInterface $request, string $market_pair): array
    {
        $marketPair = explode('_', $market_pair);
        $base = $marketPair[0] ?? '';
        $quote = $marketPair[1] ?? '';

        $base = $this->rebrandingConverter->reverseConvert(mb_strtolower($base));
        $quote = $this->rebrandingConverter->reverseConvert(mb_strtolower($quote));

        $market = $this->marketFinder->find($base, $quote);

        if (is_null($market)) {
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
