<?php declare(strict_types = 1);

namespace App\Tests\TwigExtensions;

use App\TwigExtension\AbsoluteUrlExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class AbsoluteUrlExtensionTest extends TestCase
{
    /**
     * @dataProvider getUrls
     */
    public function testAbsoluteUrl(string $url, string $schemeAndHttpHost, bool $isUrlAbsolute, string $expected): void
    {
        $extension = new AbsoluteUrlExtension(
            $this->mockRequestStack(
                $schemeAndHttpHost,
                $isUrlAbsolute
            )
        );

        $this->assertSame($expected, $extension->doAbsoluteUrl($url));
    }

    public function getUrls(): array
    {
        return [
            "An absolute url should get return without getting processed" => [
                "url" => "https://example.com/foo",
                "schemeAndHttpHost" => "https://example.com",
                "isUrlAbsolute" => true,
                "expected" => "https://example.com/foo",
            ],
            "A relative url should get processed and changed to absolute url" => [
                "url" => "/foo",
                "schemeAndHttpHost" => "https://www.example.com",
                "isUrlAbsolute" => false,
                "expected" => "https://www.example.com/foo",
            ],
        ];
    }

    private function mockRequestStack(string $schemeAndHttpHost, bool $isUrlAbsolute): RequestStack
    {
        $requestStack =  $this->createMock(RequestStack::class);

        $requestStack->expects($isUrlAbsolute ? $this->never() : $this->once())
            ->method('getCurrentRequest')
            ->willReturn($this->mockRequest($schemeAndHttpHost));

        return $requestStack;
    }

    private function mockRequest(string $schemeAndHttpHost): Request
    {
        $request = $this->createMock(Request::class);

        $request->method('getSchemeAndHttpHost')
            ->willReturn($schemeAndHttpHost);

        return $request;
    }
}
