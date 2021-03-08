<?php declare(strict_types = 1);

namespace App\Controller\API;

use App\Services\BackendService\BackendContainerBuilderInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * @Rest\Route("/api/backend-service")
 * @Security(expression="is_granted('hacker')")
 */
class BackendServiceController extends AbstractFOSRestController
{
    private BackendContainerBuilderInterface $backendContainerBuilder;

    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        BackendContainerBuilderInterface $backendContainerBuilder,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->backendContainerBuilder = $backendContainerBuilder;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @Rest\View()
     * @Rest\Post(
     *     "/container-create",
     *     name="create_container",
     *     options={"expose"=true}
     *     )
     * @return string
     */
    public function createContainer(): string
    {
        $this->eventDispatcher->addListener(KernelEvents::TERMINATE, function (TerminateEvent $event): void {
             $this->backendContainerBuilder->createContainer($event->getRequest());
        });

        return 'Ok';
    }

    /**
     * @Rest\View()
     * @Rest\Post(
     *     "/container-delete",
     *     name="delete_container",
     *     options={"expose"=true}
     *     )
     * @return string
     */
    public function deleteContainer(): string
    {
        $this->eventDispatcher->addListener(KernelEvents::TERMINATE, function (TerminateEvent $event): void {
            $this->backendContainerBuilder->deleteContainer($event->getRequest());
        });

        return 'Ok';
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
