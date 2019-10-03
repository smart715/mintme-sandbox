<?php declare(strict_types = 1);

namespace App\Controller\Dev\API;

use App\Entity\Token\Token;
use App\Exception\ApiNotFoundException;
use App\Manager\TokenManagerInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Swagger\Annotations as SWG;

/**
 * @Security(expression="is_granted('prelaunch')")
 * @Rest\Route(path="/dev/api/v1/currencies")
 * @Cache(smaxage=15, mustRevalidate=true)
 */
class CurrenciesController extends AbstractFOSRestController
{
    /** @var TokenManagerInterface */
    private $tokenManager;

    public function __construct(TokenManagerInterface $tokenManager)
    {
        $this->tokenManager = $tokenManager;
    }

    /**
     * List currencies
     *
     * @Rest\View(serializerGroups={"dev"})
     * @Rest\Get()
     * @SWG\Response(
     *     response="200",
     *     description="Returns currencies list",
     *     @SWG\Schema(type="array", @SWG\Items(ref="#/definitions/Сurrency"))
     * )
     * @SWG\Response(response="400",description="Bad request")
     * @Rest\QueryParam(name="offset", requirements="\d+", default="0")
     * @Rest\QueryParam(name="limit", requirements="\d+", default="100")
     * @SWG\Parameter(name="offset", in="query", type="integer", description="Results offset [>0]")
     * @SWG\Parameter(name="limit", in="query", type="integer", description="Results limit [1-500]")
     * @SWG\Tag(name="Currencies")
     */
    public function getCurrencies(): array
    {
        return $this->tokenManager->findAll();
    }

    /**
     * Get currency info
     *
     * @Rest\View(serializerGroups={"dev"})
     * @Rest\Get("/{name}")
     * @SWG\Response(
     *     response="200",
     *     description="Returns tokens info",
     *     @SWG\Schema(ref="#/definitions/Сurrency")
     * )
     * @SWG\Response(response="404",description="Currency not found")
     * @SWG\Response(response="400",description="Bad request")
     * @SWG\Parameter(name="name", in="path", description="Currency name", type="string")
     * @SWG\Tag(name="Currencies")
     */
    public function getCurrency(string $name): Token
    {
        $token = $this->tokenManager->findByName($name);

        if (!$token) {
            throw new ApiNotFoundException("Currency not found");
        }

        return $token;
    }
}
