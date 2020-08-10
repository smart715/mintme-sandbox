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
use Nelmio\ApiDocBundle\Annotation\Security;
use Safe\DateTimeImmutable;
use Swagger\Annotations as SWG;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @Rest\Route("/dev/api/v2/open/orderbook")
 */
class OrderbookController extends AbstractFOSRestController
{
    public const ONLY_BEST = 1;
    public const ARRANGED_BY_BEST = 2;
    public const NO_AGGREGATION = 3;

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
     * @Rest\Get("/{base_quote}")
     * @Rest\QueryParam(
     *     name="depth",
     *     requirements=@Assert\Range(min="1", max="101"),
     *     nullable=false,
     *     description="Order depth",
     *     allowBlank=false,
     *     strict=true
     * )
     * @Rest\QueryParam(
     *     name="level",
     *     requirements=@Assert\Range(min="1", max="3"),
     *     nullable=false,
     *     description="Level 1 – Only the best bid and ask.
Level 2 – Arranged by best bids and asks.
Level 3 – Complete order book, no aggregation.",
     *     allowBlank=false,
     *     strict=true
     * )
     * @Rest\View(serializerGroups={"dev"})
     * @SWG\Response(
     *     response="200",
     *     description="Returns complete level 2 order book (arranged by best asks/bids) with full depth returned for a given market pair."
     * )
     * @SWG\Response(response="400",description="Bad request")
     * @SWG\Tag(name="Open")
     * @Security(name="")
     */
    public function getOrderbook(ParamFetcherInterface $request, string $base_quote): array
    {
        $marketPair = explode('_', $base_quote);
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
                'limit' => (int)$request->get('depth'),
                'interval' => '0',
            ]
        );

        $level = $request->get('level');

        if (self::ONLY_BEST === $level) {
            $orderDepth['asks'] = max($orderDepth['asks']);
            $orderDepth['bids'] = min($orderDepth['bids']);
        } elseif (self::ARRANGED_BY_BEST == $level) {
            rsort($orderDepth['asks']);
            sort($orderDepth['bids']);
        }

        $date = new DateTimeImmutable();
        $orderDepth['timestamp'] = $date->getTimestamp();

        return $orderDepth;
    }
}
