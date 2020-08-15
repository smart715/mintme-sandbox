<?php declare(strict_types = 1);

namespace App\Controller\Dev\API\V2;

use App\Exception\ApiNotFoundException;
use App\Exchange\Market\MarketFinderInterface;
use App\Exchange\Trade\TraderInterface;
use App\Manager\MarketStatusManagerInterface;
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

    /** @var TraderInterface */
    private $trader;

    /** @var MarketFinderInterface */
    private $marketFinder;

    /** @var MarketStatusManagerInterface */
    private $marketStatusManager;

    /** @var RebrandingConverterInterface */
    private $rebrandingConverter;

    public function __construct(
        TraderInterface $trader,
        MarketFinderInterface $marketFinder,
        RebrandingConverterInterface $rebrandingConverter,
        MarketStatusManagerInterface $marketStatusManager
    ) {
        $this->trader = $trader;
        $this->marketFinder = $marketFinder;
        $this->rebrandingConverter = $rebrandingConverter;
        $this->marketStatusManager = $marketStatusManager;
    }
    /**
     * Get order book
     *
     * @Rest\Get("/{base_quote}")
     * @Rest\QueryParam(
     *     name="depth",
     *     requirements=@Assert\Range(min="0", max="101"),
     *     nullable=true,
     *     description="Order depth (how many asks/bids records to show [1-101])",
     *     allowBlank=true,
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
     * @SWG\Parameter(name="base_quote", in="path", type="string")
     * @SWG\Parameter(name="depth", in="query", type="integer")
     * @SWG\Parameter(name="level", in="query", type="integer")
     * @SWG\Tag(name="Open")
     * @Security(name="")
     */
    public function getOrderbook(ParamFetcherInterface $request, string $base_quote): array
    {
        $marketPair = explode('_', $base_quote);

        $base = $marketPair[0] ?? '';
        $quote = $marketPair[1] ?? '';

        $base = $this->rebrandingConverter->reverseConvert($base);
        $quote = $this->rebrandingConverter->reverseConvert($quote);

        $market = $this->marketFinder->find($base, $quote);

        if (!$market || !$this->marketStatusManager->isValid($market)) {
            throw new ApiNotFoundException('Market pair not found');
        }

        $depth =
            !empty($request->get('depth')) ?
            $request->get('depth') :
            101;

        $orderDepth = $this->trader->getOrderDepth(
            $market,
            [
                'limit' => (int)$depth,
            ],
            true
        );

        $level = $request->get('level');

        if (self::ONLY_BEST === $level) {
            $orderDepth['asks'] = max($orderDepth['asks']);
            $orderDepth['bids'] = min($orderDepth['bids']);
        } elseif (self::ARRANGED_BY_BEST == $level) {
            sort($orderDepth['asks']);
            rsort($orderDepth['bids']);
        }

        $date = new DateTimeImmutable();
        $orderDepth['timestamp'] = $date->getTimestamp();

        return $orderDepth;
    }
}
