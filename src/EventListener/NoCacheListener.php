<?php declare(strict_types = 1);

namespace App\EventListener;

use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

class NoCacheListener
{
    private const IGNORE_ROUTES = [
        'translations-ui',
    ];

    private array $ignoredRoutes;

    public function __construct(array $ignoredRoutes = self::IGNORE_ROUTES)
    {
        $this->ignoredRoutes = $ignoredRoutes;
    }

    public function onKernelResponse(FilterResponseEvent $event): void
    {
        $response = $event->getResponse();
        $route = $event->getRequest()->get('_route');

        if (!in_array($route, $this->ignoredRoutes)) {
            $response->headers->addCacheControlDirective('no-cache', true);
            $response->headers->addCacheControlDirective('max-age', false);
            $response->headers->addCacheControlDirective('must-revalidate', true);
            $response->headers->addCacheControlDirective('no-store', true);
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', '0');
        }
    }
}
