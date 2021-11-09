<?php declare(strict_types = 1);

namespace App\EventListener;

use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

class NoCacheListener
{
    public function onKernelResponse(FilterResponseEvent $event): void
    {
        $response = $event->getResponse();

        $headers = $response->headers->all();

        if (!isset($headers['cache-control'])) {
            $response->headers->addCacheControlDirective('no-cache', true);
            $response->headers->addCacheControlDirective('max-age', 0);
            $response->headers->addCacheControlDirective('must-revalidate', true);
            $response->headers->addCacheControlDirective('no-store', true);
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', '0');
        }
    }
}
