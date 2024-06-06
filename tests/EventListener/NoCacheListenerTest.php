<?php declare(strict_types = 1);

namespace App\Tests\EventListener;

use App\EventListener\NoCacheListener;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

class NoCacheListenerTest extends TestCase
{
    private const IGNORED_ROUTE = 'ignored_route';

    /** @dataProvider getTestCases */
    public function testOnKernelResponse(bool $isRouteIgnored): void
    {
        $listener = new NoCacheListener($isRouteIgnored ? [self::IGNORED_ROUTE] : []);
        $event = $this->mockEvent($isRouteIgnored);
        $listener->onKernelResponse($event);
    }

    public function getTestCases(): array
    {
        return [
            "If route is ignored, then do nothing" => ["isRouteIgnored" => false],
            "If route isn't ignored, then set cache headers" => ["isRouteIgnored" => false],
        ];
    }

    public function mockEvent(bool $isRouteIgnored): FilterResponseEvent
    {
        $event = $this->createMock(FilterResponseEvent::class);
        $event->expects($this->once())
            ->method('getResponse')
            ->willReturn($this->mockResponse($isRouteIgnored));

        $event->expects($this->once())
            ->method('getRequest')
            ->willReturn($this->mockRequest($isRouteIgnored));

        return $event;
    }

    private function mockRequest(bool $isRouteIgnored): Request
    {
        $event = $this->createMock(Request::class);
        $event->expects($this->once())
            ->method('get')
            ->with('_route')
            ->willReturn($isRouteIgnored ? self::IGNORED_ROUTE : '');

        return $event;
    }

    public function mockResponse(bool $isRouteIgnored): Response
    {
        $response = $this->createMock(Response::class);
        $response->headers = $this->mockResponseHeaderBag($isRouteIgnored);

        return $response;
    }

    public function mockResponseHeaderBag(bool $isRouteIgnored): ResponseHeaderBag
    {
        $responseHeaderBag = $this->createMock(ResponseHeaderBag::class);

        $responseHeaderBag->expects($isRouteIgnored ? $this->never() : $this->exactly(4))
            ->method('addCacheControlDirective');

        $responseHeaderBag->expects($isRouteIgnored ? $this->never() : $this->exactly(2))
            ->method('set');

        return $responseHeaderBag;
    }
}
