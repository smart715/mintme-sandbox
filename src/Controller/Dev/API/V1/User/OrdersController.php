<?php declare(strict_types = 1);

namespace App\Controller\Dev\API\V1\User;

use App\Controller\Dev\API\V1\DevApiController;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Exception\ApiBadRequestException;
use App\Exception\ApiNotFoundException;
use App\Exchange\ExchangerInterface;
use App\Exchange\Factory\MarketFactoryInterface;
use App\Exchange\Market;
use App\Exchange\Market\MarketHandlerInterface;
use App\Exchange\Order;
use App\Exchange\Trade\TraderInterface;
use App\Logger\UserActionLogger;
use App\Manager\CryptoManagerInterface;
use App\Manager\TokenManagerInterface;
use App\Utils\BaseQuote;
use App\Utils\Converter\RebrandingConverterInterface;
use App\Utils\Validator\MarketValidator;
use App\Utils\Validator\MaxAllowedOrdersValidator;
use App\Utils\Validator\TradebleDigitsValidator;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Swagger\Annotations as SWG;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Rest\Route(path="/dev/api/v1/user/orders")
 */
class OrdersController extends DevApiController
{
    /** @var MarketFactoryInterface */
    private $marketFactory;

    /** @var MarketHandlerInterface */
    private $marketHandler;

    /** @var UserActionLogger */
    private $userActionLogger;

    /** @var TraderInterface */
    private $trader;

    /** @var RebrandingConverterInterface */
    private $rebrandingConverter;

    /** @var CryptoManagerInterface */
    private $cryptoManager;

    /** @var TokenManagerInterface */
    private $tokenManager;

    private TranslatorInterface $translator;

    public function __construct(
        MarketFactoryInterface $marketFactory,
        MarketHandlerInterface $marketHandler,
        UserActionLogger $userActionLogger,
        TraderInterface $trader,
        RebrandingConverterInterface $rebrandingConverter,
        CryptoManagerInterface $cryptoManager,
        TokenManagerInterface $tokenManager,
        TranslatorInterFace $translator
    ) {
        $this->marketFactory = $marketFactory;
        $this->marketHandler = $marketHandler;
        $this->userActionLogger = $userActionLogger;
        $this->trader = $trader;
        $this->rebrandingConverter = $rebrandingConverter;
        $this->cryptoManager = $cryptoManager;
        $this->tokenManager = $tokenManager;
        $this->translator = $translator;
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
     * @SWG\Parameter(name="offset", in="query", type="integer", description="Results offset [>=0], required=true")
     * @SWG\Parameter(name="limit", in="query", type="integer", description="Results limit [1-101], required=true")
     * @SWG\Tag(name="User Orders")
     * @Cache(smaxage=15, mustRevalidate=true)
     */
    public function getActiveOrders(ParamFetcherInterface $request, bool $reverseBaseQuote = false): array
    {
        /** @var User $user*/
        $user = $this->getUser();
        $markets = $this->marketFactory->createUserRelated($user);

        if (!$markets) {
            return [];
        }

        return array_map(function ($order) {
            return $this->rebrandingConverter->convertOrder($order);
        }, $this->marketHandler->getPendingOrdersByUser(
            $user,
            $markets,
            (int)$request->get('offset'),
            (int)$request->get('limit'),
            $reverseBaseQuote
        ));
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
     * @SWG\Parameter(name="offset", in="query", type="integer", description="Results offset [>=0], required=true")
     * @SWG\Parameter(name="limit", in="query", type="integer", description="Results limit [1-101], required=true")
     * @SWG\Tag(name="User Orders")
     * @Cache(smaxage=15, mustRevalidate=true)
     */
    public function getFinishedOrders(ParamFetcherInterface $request, bool $reverseBaseQuote = false): array
    {
        /** @var User $user */
        $user = $this->getUser();

        $markets = $this->marketFactory->createUserRelated($user);

        if (!$markets) {
            return [];
        }

        return array_map(function ($order) {
            return $this->rebrandingConverter->convertOrder($order);
        }, $this->marketHandler->getUserExecutedHistory(
            $user,
            $markets,
            (int)$request->get('offset'),
            (int)$request->get('limit'),
            $reverseBaseQuote
        ));
    }

    /**
     * Place an order on a specific market
     *
     * @Rest\View()
     * @Rest\Post()
     * @Rest\RequestParam(name="base", allowBlank=false)
     * @Rest\RequestParam(name="quote", allowBlank=false)
     * @Rest\RequestParam(
     *     name="priceInput",
     *     allowBlank=false,
     *     requirements=@Assert\LessThanOrEqual(99999999.9999)
     * )
     * @Rest\RequestParam(
     *     name="amountInput",
     *     allowBlank=false,
     *     requirements=@Assert\LessThanOrEqual(99999999.9999)
     * )
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
     *          @SWG\Property(property="base", type="string", example="MINTME", description="Base name"),
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
        ParamFetcherInterface $request,
        ExchangerInterface $exchanger,
        bool $reverseBaseQuote = false
    ): View {
        $this->denyAccessUnlessGranted('new-trades');
        $this->denyAccessUnlessGranted('trading');

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

        if (!$base || !$quote
            || !(new MarketValidator($market = new Market($base, $quote)))->validate()) {
            throw new ApiNotFoundException('Market not found');
        }

        $this->denyAccessUnlessGranted('not-blocked', $quote);

        if (!$this->isGranted('make-order', $market)) {
            return $this->view([
                'message' => $this->translator->trans('api.add_phone_number_message'),
            ], Response::HTTP_OK);
        }

        /** @var User $user*/
        $user = $this->getUser();

        $maxAllowedOrders = $this->getParameter('max_allowed_active_orders');
        $maxAllowedValidator = new MaxAllowedOrdersValidator(
            $maxAllowedOrders,
            $user,
            $this->marketHandler,
            $this->marketFactory,
        );

        if (!$maxAllowedValidator->validate()) {
            throw new ApiBadRequestException($maxAllowedValidator->getMessage());
        }

        $amount = (string)$request->get('amountInput');
        $price = (string)$request->get('priceInput');

        if (!($validator = new TradebleDigitsValidator($price, $base))->validate()
            || !($validator = new TradebleDigitsValidator($amount, $quote))->validate()) {
            throw new ApiBadRequestException($validator->getMessage());
        }

        $tradeResult = $exchanger->placeOrder(
            $user,
            $market,
            $amount,
            $price,
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
     * @SWG\Response(response="202", description="Order successfully removed")
     * @SWG\Response(response="400", description="Invalid request")
     * @SWG\Response(response="403", description="Access denied")
     * @SWG\Response(response="404", description="Market not found")
     * @Rest\QueryParam(name="base", allowBlank=false, strict=true)
     * @Rest\QueryParam(name="quote", allowBlank=false, strict=true)
     * @SWG\Parameter(name="base", in="query", description="Base name", type="string", required=true)
     * @SWG\Parameter(name="quote", in="query", description="Quote name", type="string", required=true)
     * @SWG\Parameter(name="id", in="path", description="Order identifier", type="integer", required=true)
     * @SWG\Tag(name="User Orders")
     */
    public function cancelOrder(ParamFetcherInterface $request, int $id, bool $reverseBaseQuote = false): View
    {
        $this->denyAccessUnlessGranted('new-trades');
        $this->denyAccessUnlessGranted('trading');

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

        if (!$base || !$quote
            || !(new MarketValidator($market = new Market($base, $quote)))->validate()) {
            throw new ApiNotFoundException('Market not found');
        }

        /** @var User $user*/
        $user = $this->getUser();

        if ($quote instanceof Token && $user === $quote->getOwner()) {
            $this->denyAccessUnlessGranted('not-blocked', $quote);
        }

        $order = Order::createCancelOrder($id, $user, new Market($base, $quote));

        $tradeResult = $this->trader->cancelOrder($order);

        if ($tradeResult->getResult() === $tradeResult::ORDER_NOT_FOUND) {
            throw new ApiBadRequestException('Invalid request');
        }

        if ($tradeResult->getResult() === $tradeResult::USER_NOT_MATCH) {
            $this->userActionLogger->info('[API] Access denied for cancel order', ['id' => $order->getId()]);

            return $this->view([
                'message' => 'Access denied',
            ], Response::HTTP_FORBIDDEN);
        }

        $this->userActionLogger->info('[API] Cancel order', ['id' => $order->getId()]);

        return $this->view([
            'message' => 'Order successfully removed',
        ], Response::HTTP_ACCEPTED);
    }
}
