<?php

namespace App\Controller\API;

use App\Entity\Token\Token;
use App\Entity\User;
use App\Exchange\Market;
use App\Exchange\Market\MarketHandlerInterface;
use App\Exchange\Order;
use App\Exchange\Trade\TraderInterface;
use App\Manager\CryptoManagerInterface;
use App\Manager\MarketManagerInterface;
use App\Manager\TokenManagerInterface;
use App\Utils\MarketNameParserInterface;
use App\Wallet\Money\MoneyWrapper;
use App\Wallet\Money\MoneyWrapperInterface;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;
use Money\Currency;
use Money\Money;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * @Rest\Route("/api/orders")
 * @Security(expression="is_granted('prelaunch')")
 */
class OrdersAPIController extends FOSRestController
{
    /** @var TraderInterface */
    private $trader;

    /** @var CryptoManagerInterface */
    private $cryptoManager;

    /** @var TokenManagerInterface */
    private $tokenManager;

    /** @var MarketNameParserInterface */
    private $marketParser;

    /** @var MarketHandlerInterface */
    private $marketHandler;

    /** @var MarketManagerInterface */
    private $marketManager;

    public function __construct(
        TraderInterface $trader,
        CryptoManagerInterface $cryptoManager,
        TokenManagerInterface $tokenManager,
        MarketNameParserInterface $marketParser,
        MarketHandlerInterface $marketHandler,
        MarketManagerInterface $marketManager
    ) {
        $this->trader = $trader;
        $this->cryptoManager = $cryptoManager;
        $this->tokenManager = $tokenManager;
        $this->marketParser = $marketParser;
        $this->marketHandler = $marketHandler;
        $this->marketManager = $marketManager;
    }

    /**
     * @Rest\Delete("/cancel/{market}/{ids}", name="orders_cancel", options={"expose"=true})
     * @Rest\View()
     */
    public function cancelOrders(string $market, string $ids, ParamFetcherInterface $request): View
    {
        if (!$this->getUser()) {
            throw new AccessDeniedHttpException();
        }
        $response = [
                'result' => [],
                'message' => [],
            ];
        foreach (json_decode($ids) as $id) {
            $crypto = $this->cryptoManager->findBySymbol($this->marketParser->parseSymbol($market));
            $token = $this->tokenManager->findByHiddenName($this->marketParser->parseName($market));

            if (!$token || !$crypto) {
                throw new \InvalidArgumentException();
            }

            $order = new Order(
                $id,
                $this->getUser(),
                null,
                new Market($crypto, $token),
                new Money('0', new Currency($crypto->getSymbol())),
                1,
                new Money('0', new Currency($crypto->getSymbol())),
                ""
            );

            $tradeResult = $this->trader->cancelOrder($order);
            array_push($response['result'], $tradeResult->getResult());
            array_push($response['message'], $tradeResult->getMessage());
        }
        return $this->view([
            'result' => $response['result'],
            'message' => $response['message'],
        ]);
    }

    /**
     * @Rest\View()
     * @Rest\Post("/{tokenName}/place-order", name="token_place_order")
     * @Rest\RequestParam(name="tokenName", allowBlank=false)
     * @Rest\RequestParam(name="priceInput", allowBlank=false)
     * @Rest\RequestParam(name="amountInput", allowBlank=false)
     * @Rest\RequestParam(name="action", allowBlank=false, requirements="(sell|buy|all)")
     */
    public function placeOrder(
        ParamFetcherInterface $request,
        TraderInterface $trader,
        MarketManagerInterface $marketManager,
        MoneyWrapperInterface $moneyWrapper
    ): View {
        if (!$this->getUser()) {
            throw new AccessDeniedHttpException();
        }

        $token = $this->tokenManager->findByName($request->get('tokenName'));
        $crypto = $this->cryptoManager->findBySymbol(Token::WEB_SYMBOL);

        if (null === $token || null === $crypto) {
            throw $this->createNotFoundException('Token or Crypto not found.');
        }

        $market = $marketManager->getMarket($crypto, $token);

        if (null === $market) {
            throw $this->createNotFoundException('Market not found.');
        }

        $order = new Order(
            null,
            $this->getUser(),
            null,
            $market,
            $moneyWrapper->parse(
                $request->get('amountInput'),
                MoneyWrapper::TOK_SYMBOL
            ),
            Order::SIDE_MAP[$request->get('action')],
            $moneyWrapper->parse(
                $request->get('priceInput'),
                $crypto->getSymbol()
            ),
            Order::PENDING_STATUS,
            Order::SELL_SIDE === Order::SIDE_MAP[$request->get('action')]
                ? $this->getParameter('maker_fee_rate')
                : $this->getParameter('taker_fee_rate'),
            null,
            $this->getUser()->getReferrencer() ?
                $this->getUser()->getReferrencer()->getId() :
                0
        );

        $tradeResult = $trader->placeOrder($order);

        return $this->view(
            [
                'result' => $tradeResult->getResult(),
                'message' => $tradeResult->getMessage(),
            ],
            Response::HTTP_ACCEPTED
        );
    }

    /**
     * @Rest\Get("/{tokenName}/pending", name="pending_orders", options={"expose"=true})
     * @Rest\View()
     * @return mixed[]
     */
    public function getPendingOrders(String $tokenName): array
    {
        $market = $this->getMarket($tokenName);

        $pendingBuyOrders = $market
            ? ['buy' => $this->marketHandler->getPendingBuyOrders($market)]
            : [];

        $pendingSellOrders = $market
            ? ['sell' => $this->marketHandler->getPendingSellOrders($market)]
            : [];

        return array_merge($pendingBuyOrders, $pendingSellOrders);
    }

    /**
     * @Rest\Get("/{tokenName}/executed", name="executed_orders", options={"expose"=true})
     * @Rest\View()
     * @return Order[]
     */
    public function getExecutedOrders(String $tokenName): array
    {
        $market = $this->getMarket($tokenName);

        return $market
            ? $this->marketHandler->getExecutedOrders($market)
            : [];
    }

    /**
     * @Rest\Get("/executed", name="executed_user_orders", options={"expose"=true})
     * @Rest\View()
     * @return Order[]
     */
    public function getExecutedUserOrders(): array
    {
        if (!$this->getUser()) {
            throw new AccessDeniedHttpException();
        }

        $user = $this->getUser();
        $markets = $this->marketManager->getUserRelatedMarkets($user);

        if (!$markets) {
            return [];
        }

        return $this->marketHandler->getUserExecutedHistory(
            $user,
            $markets
        );
    }

    /**
     * @Rest\Get("/pending", name="orders", options={"expose"=true})
     * @Rest\View()
     * @return Order[]
     */
    public function getPendingUserOrders(): array
    {
        if (!$this->getUser()) {
            throw new AccessDeniedHttpException();
        }

        /** @var User $user */
        $user = $this->getUser();

        return $this->marketHandler->getPendingOrdersByUser(
            $user,
            $this->marketManager->getUserRelatedMarkets($user)
        );
    }

    private function getMarket(string $tokenName): ?Market
    {
        $token = $this->tokenManager->findByName($tokenName);
        $webCrypto = $this->cryptoManager->findBySymbol(Token::WEB_SYMBOL);

        return $webCrypto && $token
            ? $this->marketManager->getMarket($webCrypto, $token)
            : null;
    }
}
