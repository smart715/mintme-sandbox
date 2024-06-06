<?php declare(strict_types = 1);

namespace App\Tests\TwigExtensions;

use App\Entity\KnowledgeBase\KnowledgeBase;
use App\TwigExtension\ArticleTranslationExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class ArticleTranslationExtensionTest extends TestCase
{
    /**
     * @dataProvider getUrls
     */
    public function testAbsoluteUrl(string $locale, string $expected): void
    {
        $extension = new ArticleTranslationExtension($this->mockRequestStack($locale));

        $this->assertSame(
            $expected,
            $extension->translate(
                $this->getCompatibleMockObject(),
                "title"
            )
        );
    }

    public function getUrls(): array
    {
        return [
            "If locale is en, it will return the original content by default" => ['en', 'test'],
            "If locale is supported, it will return translated content " => ['es', 'testo'],
            "If locale isn't supported, it will return the default content " => ['ar', 'test'],
        ];
    }

    private function mockRequestStack(string $locale): RequestStack
    {
        $requestStack =  $this->createMock(RequestStack::class);

        $requestStack->method('getCurrentRequest')
            ->willReturn($this->mockRequest($locale));

        return $requestStack;
    }

    private function mockRequest(string $locale): Request
    {
        $request = $this->createMock(Request::class);
        $request->expects($this->once())
            ->method('getLocale')
            ->willReturn($locale);

        return $request;
    }

    private function getCompatibleMockObject(): KnowledgeBase
    {
        # The class and property are irrelevant, just picked easy to test ones
        $object = $this->createMock(KnowledgeBase::class);

        $object->method('getTitle')
            ->willReturn("test");

        $object->method('getEsTitle')
            ->willReturn("testo");

        return $object;
    }
}
