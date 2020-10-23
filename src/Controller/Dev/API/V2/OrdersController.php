<?php declare(strict_types = 1);

namespace App\Controller\Dev\API\V2;

use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcherInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Swagger\Annotations as SWG;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @Cache(smaxage=15, mustRevalidate=true)
 * @Rest\Route(path="/dev/api/v2/auth/orders")
 */
class OrdersController extends AbstractFOSRestController
{
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
    public function getActiveOrders(ParamFetcherInterface $request): Response
    {
        return $this->forward(
            'App\Controller\Dev\API\V1\OrdersController::getActiveOrders',
            [
                'request' => $request,
                'reverseBaseQuote' => true,
            ],
            [
                'base' => $request->get('base'),
                'quote' => $request->get('quote'),
                'offset' => $request->get('offset'),
                'limit' => $request->get('limit'),
                'side' => $request->get('side'),
            ]
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
    public function getFinishedOrders(ParamFetcherInterface $request): Response
    {
        return $this->forward(
            'App\Controller\Dev\API\V1\OrdersController::getFinishedOrders',
            [
                'request' => $request,
                'reverseBaseQuote' => true,
            ],
            [
                'base' => $request->get('base'),
                'quote' => $request->get('quote'),
                'lastId' => $request->get('lastId'),
                'limit' => $request->get('limit'),
            ]
        );
    }
}
