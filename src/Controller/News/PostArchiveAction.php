<?php declare(strict_types = 1);

namespace App\Controller\News;

use Sonata\NewsBundle\Action\AbstractPostArchiveAction;
use Sonata\NewsBundle\Action\PostArchiveAction as SonataPostArchiveAction;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PostArchiveAction extends AbstractPostArchiveAction
{
    private SonataPostArchiveAction $action;
    private int $perPage;

    public function __construct(SonataPostArchiveAction $action, int $perPage = 12)
    {
        $this->action = $action;
        $this->perPage = $perPage;
    }

    public function __invoke(Request $request): Response
    {
        $queryParam = $request->query->get('page');

        if ($queryParam) {
            return $this->redirectToRoute('sonata_news_home', ['page' => $queryParam], Response::HTTP_MOVED_PERMANENTLY);
        }

        return $this->invoke($request);
    }

    private function invoke(Request $request): Response
    {
        return $this->renderArchiveOur($request);
    }

    private function renderArchiveOur(Request $request, array $criteria = [], array $parameters = []): Response
    {
        $pager = $this->action->getPostManager()->getPager(
            $criteria,
            intval($request->get('page', 1)),
            $this->perPage
        );

        $parameters = array_merge([
            'pager' => $pager,
            'blog' => $this->action->getBlog(),
            'tag' => false,
            'collection' => false,
            'route' => $request->get('_route'),
            'route_parameters' => $request->get('_route_params'),
        ], $parameters);

        $response = $this->action->render(
            sprintf('@SonataNews/Post/archive.%s.twig', $request->getRequestFormat()),
            $parameters
        );

        if ('rss' === $request->getRequestFormat()) {
            $response->headers->set('Content-Type', 'application/rss+xml');
        }

        return $response;
    }
}
