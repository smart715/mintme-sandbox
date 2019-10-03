<?php declare(strict_types = 1);

namespace App\Controller\Dev\API\User;

use App\Exchange\ExchangerInterface;
use App\Exchange\Factory\MarketFactoryInterface;
use App\Exchange\Market;
use App\Exchange\Market\MarketHandlerInterface;
use App\Exchange\Order;
use App\Exchange\Trade\TraderInterface;
use App\Logger\UserActionLogger;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Swagger\Annotations as SWG;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Rest\Route(path="/dev/api/v1/user/orders")
 * @Security(expression="is_granted('prelaunch')")
 */
class OrdersController extends AbstractFOSRestController
{
    /** @var MarketFactoryInterface */
    private $marketFactory;

    /** @var MarketHandlerInterface */
    private $marketHandler;

    /** @var UserActionLogger */
    private $userActionLogger;

    /** @var TraderInterface */
    private $trader;

    public function __construct(
        MarketFactoryInterface $marketFactory,
        MarketHandlerInterface $marketHandler,
        UserActionLogger $userActionLogger,
        TraderInterface $trader
    ) {
        $this->marketFactory = $marketFactory;
        $this->marketHandler = $marketHandler;
        $this->userActionLogger = $userActionLogger;
        $this->trader = $trader;
    }

    /**
     * List users active orders
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
     * @SWG\Response(response="400",description="Bad request")
     * @Rest\QueryParam(name="offset", requirements="\d+", default="0")
     * @Rest\QueryParam(name="limit", requirements="\d+", default="100")
     * @SWG\Parameter(name="offset", in="query", type="integer", description="Results offset [>0]")
     * @SWG\Parameter(name="limit", in="query", type="integer", description="Results limit [1-500]")
     * @SWG\Tag(name="User Orders")
     * @Cache(smaxage=15, mustRevalidate=true)
     */
    public function getActiveOrders(ParamFetcherInterface $fetcher): array
    {
        $user = $this->getUser();
        $markets = $this->marketFactory->createUserRelated($user);

        if (!$markets) {
            return [];
        }

        return $this->marketHandler->getPendingOrdersByUser(
            $user,
            $markets,
            (int)$fetcher->get('offset'),
            (int)$fetcher->get('limit')
        );
    }

    /**
     * List users finished orders
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
     * @SWG\Response(response="400",description="Bad request")
     * @Rest\QueryParam(name="offset", requirements="\d+", default="0")
     * @Rest\QueryParam(name="limit", requirements="\d+", default="100")
     * @SWG\Parameter(name="offset", in="query", type="integer", description="Results offset [>0]")
     * @SWG\Parameter(name="limit", in="query", type="integer", description="Results limit [1-500]")
     * @SWG\Tag(name="User Orders")
     * @Cache(smaxage=15, mustRevalidate=true)
     */
    public function getFinishedOrders(ParamFetcherInterface $fetcher): array
    {
        $user = $this->getUser();
        $markets = $this->marketFactory->createUserRelated($user);

        if (!$markets) {
            return [];
        }

        return $this->marketHandler->getUserExecutedHistory(
            $user,
            $markets,
            (int)$fetcher->get('offset'),
            (int)$fetcher->get('limit')
        );
    }

    /**
     * Place an order on a specific market
     *
     * @Rest\View()
     * @Rest\Post()
     * @Rest\RequestParam(name="base", allowBlank=false)
     * @Rest\RequestParam(name="quote", allowBlank=false)
     * @Rest\RequestParam(name="priceInput", allowBlank=false)
     * @Rest\RequestParam(name="amountInput", allowBlank=false)
     * @Rest\RequestParam(name="marketPrice", default=false)
     * @Rest\RequestParam(name="action", allowBlank=false, requirements="(sell|buy)")
     * @SWG\Parameter(
     *      name="body",
     *      in="body",
     *      description="JSON Payload",
     *      required=true,
     *      format="application/json",
     *      @SWG\Schema(
     *          type="object",
     *          @SWG\Property(property="base", type="string", example="WEB", description="Base name"),
     *          @SWG\Property(property="quote", type="string", example="MY_TOKEN", description="Quote name"),
     *          @SWG\Property(property="priceInput", type="string", example="5", description="Price to place"),
     *          @SWG\Property(property="amountInput", type="string", example="12.33", description="Amount to order"),
     *          @SWG\Property(property="marketPrice", type="boolean", example=false, description="Use market price"),
     *          @SWG\Property(
     *              property="action", type="string", example="buy", description="Order type"
     *          ),
     *      )
     * ),
     * @SWG\Response(response="201",description="Returns success message",)
     * @SWG\Response(response="404",description="Market not found")
     * @SWG\Response(response="400",description="Bad request")
     * @SWG\Tag(name="User Orders")
     */
    public function placeOrder(
        Market $market,
        ParamFetcherInterface $request,
        ExchangerInterface $exchanger
    ): View {
        $tradeResult = $exchanger->placeOrder(
            $this->getUser(),
            $market,
            (string)$request->get('amountInput'),
            (string)$request->get('priceInput'),
            filter_var($request->get('marketPrice'), FILTER_VALIDATE_BOOLEAN),
            Order::SIDE_MAP[$request->get('action')]
        );

        return $this->view([
            'result' => $tradeResult->getResult(),
            'message' => $tradeResult->getMessage(),
        ], Response::HTTP_ACCEPTED);
    }

    /**
     * Remove order of specific market
     *
     * @Rest\View()
     * @Rest\Delete("/{id}", requirements={"id"="\d+"})
     * @SWG\Response(response="204", description="Order succsessfully removed",)
     * @SWG\Response(response="400", description="Invalid request")
     * @SWG\Response(response="404", description="Market not found")
     * @Rest\QueryParam(name="base", allowBlank=false)
     * @Rest\QueryParam(name="quote", allowBlank=false)
     * @SWG\Parameter(name="base", in="query", description="Base name", type="string", required=true)
     * @SWG\Parameter(name="quote", in="query", description="Quote name", type="string", required=true)
     * @SWG\Parameter(name="id", in="path", description="Order identifier", type="integer")
     * @SWG\Tag(name="User Orders")
     */
    public function cancelOrder(int $id, Market $market): View
    {
        $order = Order::createCancelOrder($id, $this->getUser(), $market);

        $this->trader->cancelOrder($order);
        $this->userActionLogger->info('[API] Cancel order', ['id' => $order->getId()]);

        return $this->view(Response::HTTP_OK);
    }
}
