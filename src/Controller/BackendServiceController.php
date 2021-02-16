<?php declare(strict_types = 1);

namespace App\Controller;

use App\Services\BackendService\BackendContainerBuilderInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/backend_service")
 */
class BackendServiceController extends AbstractController
{
    private BackendContainerBuilderInterface $backendContainerBuilder;

    public function __construct(BackendContainerBuilderInterface $backendContainerBuilder)
    {
        $this->backendContainerBuilder = $backendContainerBuilder;
    }

    /**
     * @Route("/create-container", name="create_container", options={"expose"=true})
     * @param Request $request
     * @return RedirectResponse
     */
    public function createContainer(Request $request): RedirectResponse
    {
        $branch = $request->get('branch');

        /** @var string $referer */
        $referer = $request->headers->get('referer');

        $this->backendContainerBuilder->createContainer($branch);

        return $this->redirect($referer);
    }

    /**
     * @Route("/delete-container", name="delete_container", options={"expose"=true})
     * @param Request $request
     * @return RedirectResponse
     */
    public function deleteContainer(Request $request): RedirectResponse
    {
        $branch = $request->get('branch');

        /** @var string $referer */
        $referer = $request->headers->get('referer');

        $this->backendContainerBuilder->deleteContainer($branch);

        return $this->redirect($referer);
    }

    /**
     * @Route("/status-container", name="status_container", options={"expose"=true})
     * @param Request $request
     * @return RedirectResponse
     */
    public function statusContainer(Request $request): RedirectResponse
    {
        $branch = $request->get('branch');

        /** @var string $referer */
        $referer = $request->headers->get('referer');

        $this->backendContainerBuilder->getStatusContainer($branch);

        return $this->redirect($referer);
    }
}
