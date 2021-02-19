<?php declare(strict_types = 1);

namespace App\Controller\API;

use App\Services\BackendService\BackendContainerBuilderInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Rest\Route("/api/backend_service")
 */
class BackendServiceController extends AbstractFOSRestController
{
    private BackendContainerBuilderInterface $backendContainerBuilder;

    public function __construct(BackendContainerBuilderInterface $backendContainerBuilder)
    {
        $this->backendContainerBuilder = $backendContainerBuilder;
    }

    /**
     * @Rest\View()
     * @Rest\Post(
     *     "/create-container",
     *     name="create_container",
     *     options={"expose"=true}
     *     )
     * @param Request $request
     * @return string|null
     */
    public function createContainer(Request $request): ?string
    {
        return $this->backendContainerBuilder->createContainer($request);
    }

    /**
     * @Rest\View()
     * @Rest\Post(
     *     "/delete-container",
     *     name="delete_container",
     *     options={"expose"=true}
     *     )
     * @param Request $request
     * @return string|null
     */
    public function deleteContainer(Request $request): ?string
    {
        return $this->backendContainerBuilder->deleteContainer($request);
    }

    /**
     * @Rest\View()
     * @Rest\get(
     *     "/status-container",
     *     name="status_container",
     *     options={"expose"=true}
     *     )
     * @param Request $request
     * @return int|null
     */
    public function statusContainer(Request $request): ?int
    {
        return $this->backendContainerBuilder->getStatusContainer($request);
    }
}
