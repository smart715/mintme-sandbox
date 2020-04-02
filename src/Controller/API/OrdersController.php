<?php declare(strict_types = 1);

namespace App\Controller\API;

use App\Entity\User;
use App\Exchange\ExchangerInterface;
use App\Exchange\Factory\MarketFactoryInterface;
use App\Exchange\Market;
use App\Exchange\Market\MarketHandlerInterface;
use App\Exchange\Order;
use App\Exchange\Trade\TraderInterface;
use App\Logger\UserActionLogger;
use App\Manager\CryptoManagerInterface;
use App\Manager\TokenManagerInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;
use Money\Currency;
use Money\Money;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * @Rest\Route("/api/orders")
 * @Security(expression="is_granted('prelaunch')")
 */
class OrdersController extends AbstractFOSRestController
{
    private const OFFSET = 100;
    private const PENDING_OFFSET = 100;
    private const WALLET_OFFSET = 20;

    /** @var TraderInterface */
    private $trader;

    /** @var MarketHandlerInterface */
    private $marketHandler;

    /** @var MarketFactoryInterface */
    private $marketManager;

    /** @var UserActionLogger */
    private $userActionLogger;

    public function __construct(
        TraderInterface $trader,
        MarketHandlerInterface $marketHandler,
        MarketFactoryInterface $marketManager,
        UserActionLogger $userActionLogger
    ) {
        $this->trader = $trader;
        $this->marketHandler = $marketHandler;
        $this->marketManager = $marketManager;
        $this->userActionLogger = $userActionLogger;
    }

    /**
     * @Rest\Post("/cancel/{base}/{quote}", name="orders_сancel", options={"expose"=true})
     * @Rest\RequestParam(name="orderData", allowBlank=false, description="array of orders ids")
     * @Rest\View()
     */
    public function cancelOrders(Market $market, ParamFetcherInterface $request): View
    {
        if (!$this->getUser()) {
            throw new AccessDeniedHttpException();
        }

        foreach ($request->get('orderData') as $id) {
            $order = new Order(
                $id,
                $this->getUser(),
                null,
                $market,
                new Money('0', new Currency($market->getQuote()->getSymbol())),
                1,
                new Money('0', new Currency($market->getQuote()->getSymbol())),
                ""
            );

            $this->trader->cancelOrder($order);
            $this->userActionLogger->info('Cancel order', ['id' => $order->getId()]);
        }

        return $this->view(Response::HTTP_OK);
    }

    /**
     * @Rest\View()
     * @Rest\Post("/{base}/{quote}/place-order", name="token_place_order", options={"expose"=true})
     * @Rest\RequestParam(name="priceInput", allowBlank=false)
     * @Rest\RequestParam(name="amountInput", allowBlank=false)
     * @Rest\RequestParam(name="marketPrice", default="0")
     * @Rest\RequestParam(name="action", allowBlank=false, requirements="(sell|buy)")
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
            (bool)$request->get('marketPrice'),
            Order::SIDE_MAP[$request->get('action')]
        );

        return $this->view([
            'result' => $tradeResult->getResult(),
            'message' => $tradeResult->getMessage(),
        ], Response::HTTP_ACCEPTED);
    }

    /**
     * @Rest\Get(
     *     "/{base}/{quote}/pending/page/{page}", name="pending_orders", defaults={"page"=1}, options={"expose"=true}
     * )
     * @Rest\View()
     * @return mixed[]
     */
    public function getPendingOrders(Market $market, int $page): array
    {
        $pendingBuyOrders = $this->marketHandler->getPendingBuyOrders(
            $market,
            ($page - 1) * self::PENDING_OFFSET,
            self::PENDING_OFFSET
        );
        $pendingSellOrders = $this->marketHandler->getPendingSellOrders(
            $market,
            ($page - 1) * self::PENDING_OFFSET,
            self::PENDING_OFFSET
        );

        return [
            'sell' => $pendingSellOrders,
            'buy' => $pendingBuyOrders,
        ];
    }

    /**
     * @Rest\Get(
     *     "/{base}/{quote}/executed/last/{id}", name="executed_orders", defaults={"id"=0}, options={"expose"=true}
     * )
     * @Rest\View()
     */
    public function getExecutedOrders(Market $market, int $id): array
    {
        return $this->marketHandler->getExecutedOrders($market, $id, self::OFFSET);
    }

    /**
     * @Rest\Get("/executed/page/{page}", name="executed_user_orders", defaults={"page"=1}, options={"expose"=true})
     * @Rest\View()
     */
    public function getExecutedUserOrders(int $page): array
    {
        if (!$this->getUser()) {
            throw new AccessDeniedHttpException();
        }

        $user = $this->getUser();
        $markets = $this->marketManager->createUserRelated($user);

        if (!$markets) {
            return [];
        }

        return $this->marketHandler->getUserExecutedHistory(
            $user,
            $markets,
            ($page - 1) * self::WALLET_OFFSET,
            self::WALLET_OFFSET
        );
    }

    /**
     * @Rest\Get("/{base}/{quote}/executed/{id}", name="executed_order_details", options={"expose"=true})
     * @Rest\View()
     */
    public function getExecutedOrderDetails(Market $market, int $id): View
    {
        return $this->view($this->marketHandler->getExecutedOrder($market, $id, self::OFFSET));
    }

    /**
     * @Rest\Get("/{base}/{quote}/pending/{id}", name="pending_order_details", options={"expose"=true})
     * @Rest\View()
     */
    public function getPendingOrderDetails(Market $market, int $id): View
    {
        return $this->view($this->marketHandler->getPendingOrder($market, $id));
    }

    /**
     * @Rest\Get("/pending/page/{page}", name="orders", defaults={"page"=1}, options={"expose"=true})
     * @Rest\View()
     * @return Order[]
     */
    public function getPendingUserOrders(int $page): array
    {
        if (!$this->getUser()) {
            throw new AccessDeniedHttpException();
        }

        /** @var User $user */
        $user = $this->getUser();

        return $this->marketHandler->getPendingOrdersByUser(
            $user,
            $this->marketManager->createUserRelated($user),
            ($page - 1) * self::WALLET_OFFSET,
            self::WALLET_OFFSET
        );
    }
}
