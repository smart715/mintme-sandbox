<?php declare(strict_types = 1);

namespace App\Controller\API;

use App\Services\BackendService\BackendContainerBuilderInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Rest\Route("/api/backend-service")
 */
class BackendServiceController extends AbstractFOSRestController
{
    private BackendContainerBuilderInterface $backendContainerBuilder;

    public function __construct(
        BackendContainerBuilderInterface $backendContainerBuilder
    ) {
        $this->backendContainerBuilder = $backendContainerBuilder;
    }

    /**
     * @Rest\View()
     * @Rest\Post(
     *     "/container-create",
     *     name="create_container",
     *     options={"expose"=true}
     *     )
     * @return string|null
     */
    public function createContainer(): ?string
    {
        return $this->backendContainerBuilder->setMaintenanceMode('block');
    }

    /**
     * @Rest\View()
     * @Rest\Post(
     *     "/container-delete",
     *     name="delete_container",
     *     options={"expose"=true}
     *     )
     * @return string|null
     */
    public function deleteContainer(): ?string
    {
        return $this->backendContainerBuilder->setMaintenanceMode('block');
    }

    /**
     * @Rest\View()
     * @Rest\get(
     *     "/container-status",
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
