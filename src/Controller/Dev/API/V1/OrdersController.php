<?php declare(strict_types = 1);

namespace App\Controller\Dev\API\V1;

use App\Exception\ApiNotFoundException;
use App\Exchange\Market;
use App\Exchange\Market\MarketHandlerInterface;
use App\Exchange\Order;
use App\Manager\CryptoManagerInterface;
use App\Manager\TokenManagerInterface;
use App\Utils\BaseQuote;
use App\Utils\Converter\RebrandingConverterInterface;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcherInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Swagger\Annotations as SWG;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @Cache(smaxage=15, mustRevalidate=true)
 * @Rest\Route(path="/dev/api/v1/orders")
 */
class OrdersController extends DevApiController
{
    /** @var MarketHandlerInterface */
    private $marketHandler;

    /** @var RebrandingConverterInterface */
    private $rebrandingConverter;

    /** @var CryptoManagerInterface */
    private $cryptoManager;

    /** @var TokenManagerInterface */
    private $tokenManager;

    public function __construct(
        MarketHandlerInterface $marketHandler,
        RebrandingConverterInterface $rebrandingConverter,
        CryptoManagerInterface $cryptoManager,
        TokenManagerInterface $tokenManager
    ) {
        $this->marketHandler = $marketHandler;
        $this->rebrandingConverter = $rebrandingConverter;
        $this->cryptoManager = $cryptoManager;
        $this->tokenManager = $tokenManager;
    }

    /**
     * List active orders of a specific market
     *
     * @Rest\View(serializerGroups={"dev"})
     * @Rest\Get("/active")
     * @SWG\Response(
     *     response="200",
     *     description="Returns active orders related to user",
     *     @SWG\Schema(
     *         type="array",
     *         @SWG\Items(ref="#/definitions/Order")
     *     )
     * )
     * @SWG\Response(response="404",description="Market not found")
     * @SWG\Response(response="400",description="Bad request")
     * @Rest\QueryParam(name="base", allowBlank=false, strict=true)
     * @Rest\QueryParam(name="quote", allowBlank=false, strict=true)
     * @Rest\QueryParam(
     *     name="offset",
     *     requirements=@Assert\Range(min="0"),
     *     nullable=false,
     *     allowBlank=false,
     *     strict=true
     * )
     * @Rest\QueryParam(
     *     name="limit",
     *     requirements=@Assert\Range(min="1", max="101"),
     *     nullable=false,
     *     allowBlank=false,
     *     strict=true
     * )
     * @Rest\QueryParam(
     *     name="side",
     *     requirements="(sell|buy)",
     *     allowBlank=false,
     *     nullable=false,
     *     strict=true
     * )
     * @SWG\Parameter(name="base", in="query", description="Base name", type="string", required=true)
     * @SWG\Parameter(name="quote", in="query", description="Quote name", type="string", required=true)
     * @SWG\Parameter(name="offset", in="query", type="integer", description="Results offset [>=0]")
     * @SWG\Parameter(name="limit", in="query", type="integer", description="Results limit [1-101]")
     * @SWG\Parameter(name="side", in="query", type="string", description="Order side (sell|buy)")
     * @SWG\Tag(name="Orders")
     */
    public function getActiveOrders(ParamFetcherInterface $request, bool $reverseBaseQuote = false): array
    {
        $base = $request->get('base');
        $quote = $request->get('quote');

        $this->checkForDisallowedValues($base, $quote);

        $base = $this->rebrandingConverter->reverseConvert($base);
        $quote = $this->rebrandingConverter->reverseConvert($quote);

        if ($reverseBaseQuote) {
            [$base, $quote] = BaseQuote::reverse($base, $quote);
        }

        $base = $this->cryptoManager->findBySymbol($base);
        $quote = $this->cryptoManager->findBySymbol($quote) ?? $this->tokenManager->findByName($quote);

        if (is_null($base) || is_null($quote)) {
            throw new ApiNotFoundException('Market not found');
        }

        $market = new Market($base, $quote);
        $method = Order::BUY_SIDE === Order::SIDE_MAP[$request->get('side')]
            ? 'getPendingBuyOrders'
            : 'getPendingSellOrders';

        return array_map(function ($order) {
            return $this->rebrandingConverter->convertOrder($order);
        }, $this->marketHandler->$method(
            $market,
            (int)$request->get('offset'),
            (int)$request->get('limit'),
            $reverseBaseQuote
        ));
    }

    /**
     * List finished orders of a specific market
     *
     * @Rest\View(serializerGroups={"dev"})
     * @Rest\Get("/finished")
     * @SWG\Response(
     *     response="200",
     *     description="Returns finished orders related to user",
     *     @SWG\Schema(
     *         type="array",
     *         @SWG\Items(ref="#/definitions/Order")
     *     )
     * )
     * @SWG\Response(response="404",description="Market not found")
     * @SWG\Response(response="400",description="Bad request")
     * @Rest\QueryParam(name="base", allowBlank=false, strict=true)
     * @Rest\QueryParam(name="quote", allowBlank=false, strict=true)
     * @Rest\QueryParam(name="lastId", requirements="^[0-9]*$", default="0", strict=true)
     * @Rest\QueryParam(
     *     name="limit",
     *     requirements=@Assert\Range(min="1", max="500"),
     *     nullable=false,
     *     allowBlank=false,
     *     strict=true
     * )
     * @SWG\Parameter(name="base", in="query", description="Base name", type="string", required=true)
     * @SWG\Parameter(name="quote", in="query", description="Quote name", type="string", required=true)
     * @SWG\Parameter(name="lastId", in="query", type="integer", description="Identifier of last order [>=0]")
     * @SWG\Parameter(name="limit", in="query", type="integer", description="Results limit [1-500]")
     * @SWG\Tag(name="Orders")
     */
    public function getFinishedOrders(ParamFetcherInterface $request, bool $reverseBaseQuote = false): array
    {
        $base = $request->get('base');
        $quote = $request->get('quote');

        $this->checkForDisallowedValues($base, $quote);

        $base = $this->rebrandingConverter->reverseConvert($base);
        $quote = $this->rebrandingConverter->reverseConvert($quote);

        if ($reverseBaseQuote) {
            [$base, $quote] = BaseQuote::reverse($base, $quote);
        }

        $base = $this->cryptoManager->findBySymbol($base);
        $quote = $this->cryptoManager->findBySymbol($quote) ?? $this->tokenManager->findByName($quote);

        if (is_null($base) || is_null($quote)) {
            throw new ApiNotFoundException('Market not found');
        }

        return array_map(function ($order) {
            return $this->rebrandingConverter->convertOrder($order);
        }, $this->marketHandler->getExecutedOrders(
            new Market($base, $quote),
            (int)$request->get('lastId'),
            (int)$request->get('limit'),
            $reverseBaseQuote
        ));
    }
}
