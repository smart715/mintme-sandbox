<?php declare(strict_types = 1);

namespace App\Controller\Dev\API\V2\User;

use App\Controller\Dev\API\V1\DevApiController;
use App\Exchange\ExchangerInterface;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcherInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Swagger\Annotations as SWG;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @Rest\Route(path="/dev/api/v2/auth/user/orders")
 */
class OrdersController extends DevApiController
{
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
    public function getActiveOrders(ParamFetcherInterface $request): Response
    {
        return $this->forward(
            'App\Controller\Dev\API\V1\User\OrdersController::getActiveOrders',
            [
                'request' => $request,
                'reverseBaseQuote' => true,
            ],
            [
                'offset' => (int)$request->get('offset'),
                'limit' => (int)$request->get('limit'),
            ]
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
    public function getFinishedOrders(ParamFetcherInterface $request): Response
    {
        return $this->forward(
            'App\Controller\Dev\API\V1\User\OrdersController::getFinishedOrders',
            [
                'request' => $request,
                'reverseBaseQuote' => true,
            ],
            [
                'offset' => (int)$request->get('offset'),
                'limit' => (int)$request->get('limit'),
            ]
        );
    }

    /**
     * Place an order on a specific market
     *
     * @Rest\View()
     * @Rest\Post(defaults={"_private_key_required"=true})
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
     *          @SWG\Property(property="base", type="string", example="MY_TOKEN", description="Base name"),
     *          @SWG\Property(property="quote", type="string", example="MINTME", description="Quote name"),
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
     * @SWG\Response(response="403", description="Access denied or Please wait at least some seconds before placing a new order")
     * @SWG\Response(response="400",description="Bad request")
     * @SWG\Tag(name="User Orders")
     */
    public function placeOrder(ParamFetcherInterface $request, ExchangerInterface $exchanger): Response
    {
        $base = $request->get('base');
        $quote = $request->get('quote');

        return $this->forward(
            'App\Controller\Dev\API\V1\User\OrdersController::placeOrder',
            [
                'request' => $request,
                'exchanger' => $exchanger,
                'reverseBaseQuote' => true,
            ],
            [
                'base' => $base,
                'quote' => $quote,
                'priceInput' => $request->get('priceInput'),
                'amountInput' => $request->get('amountInput'),
                'marketPrice' => $request->get('marketPrice'),
                'action' => $request->get('action'),
            ]
        );
    }

    /**
     * Remove order of specific market
     *
     * @Rest\View()
     * @Rest\Delete(
     *     "/{id}",
     *     requirements={"id"="\d+"},
     *     defaults={"_private_key_required"=true}
     * )
     * @SWG\Response(response="202", description="Order successfully removed")
     * @SWG\Response(response="400", description="Invalid request")
     * @SWG\Response(response="403", description="Access denied or Please wait at least some seconds before canceling an order")
     * @SWG\Response(response="404", description="Market not found")
     * @Rest\QueryParam(name="base", allowBlank=false, strict=true)
     * @Rest\QueryParam(name="quote", allowBlank=false, strict=true)
     * @SWG\Parameter(name="base", in="query", description="Base name", type="string", required=true)
     * @SWG\Parameter(name="quote", in="query", description="Quote name", type="string", required=true)
     * @SWG\Parameter(name="id", in="path", description="Order identifier", type="integer", required=true)
     * @SWG\Tag(name="User Orders")
     */
    public function cancelOrder(ParamFetcherInterface $request, int $id): Response
    {
        $base = $request->get('base');
        $quote = $request->get('quote');

        return $this->forward(
            'App\Controller\Dev\API\V1\User\OrdersController::cancelOrder',
            [
                'request' => $request,
                'id' => $id,
                'reverseBaseQuote' => true,
            ],
            [
                'base' => $base,
                'quote' => $quote,
            ]
        );
    }
}
