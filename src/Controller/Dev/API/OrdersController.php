<?php declare(strict_types = 1);

namespace App\Controller\Dev\API;

use App\Exchange\Market;
use App\Exchange\Market\MarketHandlerInterface;
use App\Exchange\Order;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcherInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Swagger\Annotations as SWG;

/**
 * @Cache(smaxage=15, mustRevalidate=true)
 * @Rest\Route(path="/dev/api/v1/orders")
 * @Security(expression="is_granted('prelaunch')")
 */
class OrdersController extends AbstractFOSRestController
{
    /** @var MarketHandlerInterface */
    private $marketHandler;

    public function __construct(
        MarketHandlerInterface $marketHandler
    ) {
        $this->marketHandler = $marketHandler;
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
     * @Rest\QueryParam(name="base", allowBlank=false)
     * @Rest\QueryParam(name="quote", allowBlank=false)
     * @Rest\QueryParam(name="offset", requirements="\d+", default="0")
     * @Rest\QueryParam(name="limit", requirements="\d+", default="100")
     * @Rest\QueryParam(name="side", requirements="(sell|buy)", allowBlank=false, nullable=false)
     * @SWG\Parameter(name="base", in="query", description="Base name", type="string", required=true)
     * @SWG\Parameter(name="quote", in="query", description="Quote name", type="string", required=true)
     * @SWG\Parameter(name="offset", in="query", type="integer", description="Results offset [>0]")
     * @SWG\Parameter(name="limit", in="query", type="integer", description="Results limit [1-500]")
     * @SWG\Parameter(name="side", in="query", type="string", description="Order side (sell|buy)")
     * @SWG\Tag(name="Orders")
     */
    public function getActiveOrders(Market $market, ParamFetcherInterface $fetcher): array
    {
        return Order::BUY_SIDE === Order::SIDE_MAP[$fetcher->get('side')] ?
            $this->marketHandler->getPendingBuyOrders(
                $market,
                (int)$fetcher->get('offset'),
                (int)$fetcher->get('limit')
            ) :
            $this->marketHandler->getPendingSellOrders(
                $market,
                (int)$fetcher->get('offset'),
                (int)$fetcher->get('limit')
            );
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
     * @Rest\QueryParam(name="base", allowBlank=false)
     * @Rest\QueryParam(name="quote", allowBlank=false)
     * @Rest\QueryParam(name="lastId", requirements="\d+", default="0")
     * @Rest\QueryParam(name="limit", requirements="\d+", default="100")
     * @SWG\Parameter(name="base", in="query", description="Base name", type="string", required=true)
     * @SWG\Parameter(name="quote", in="query", description="Quote name", type="string", required=true)
     * @SWG\Parameter(name="lastId", in="query", type="integer", description="Identifier of last order [>0]")
     * @SWG\Parameter(name="limit", in="query", type="integer", description="Results limit [1-500]")
     * @SWG\Tag(name="Orders")
     */
    public function getFinishedOrders(Market $market, ParamFetcherInterface $fetcher): array
    {
        return $this->marketHandler->getExecutedOrders(
            $market,
            (int)$fetcher->get('lastId'),
            (int)$fetcher->get('limit')
        );
    }
}
