<?php declare(strict_types = 1);

namespace App\Tests\TwigExtension;

use App\TwigExtension\IsEmbededExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class IsEmbededExtensionTest extends TestCase
{
    public function testIsEmbededFalse(): void
    {
        $this->assertNotTrue(
            (new IsEmbededExtension($this->mockRequestStack('/foo')))->isEmbeded()
        );
    }

    public function testIsEmbededTrue(): void
    {
        $this->assertTrue(
            (new IsEmbededExtension($this->mockRequestStack('/foo/embeded')))->isEmbeded()
        );
    }

    private function mockRequestStack(string $path): RequestStack
    {
        $request = $this->createMock(Request::class);
        $request->method('getPathInfo')->willReturn($path);

        $requestStack = $this->createMock(RequestStack::class);
        $requestStack->method('getCurrentRequest')->willReturn($request);

        return $requestStack;
    }
}
