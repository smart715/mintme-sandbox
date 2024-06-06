<?php declare(strict_types = 1);

namespace App\Tests\EventSubscriber;

use App\Entity\User;
use App\EventSubscriber\LocaleSubscriber;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\Matcher\InvokedCount;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class LocaleSubscriberTest extends TestCase
{
    /** @dataProvider localesDataProvider */
    public function testOnKernelRequest(
        string $reguestsLocale,
        array $locales,
        ?User $user,
        int $emCalls
    ): void {
        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenInterface = $this->createMock(TokenInterface::class);
        $tokenInterface->method('getUser')->willReturn($user);
        $tokenStorage
            ->method('getToken')
            ->willReturn($tokenInterface);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->exactly($emCalls))->method('persist')->withAnyParameters();
        $em->expects($this->exactly($emCalls))->method('flush');

        $sub = new LocaleSubscriber(
            $tokenStorage,
            $em,
            'en',
            $locales,
            ['es']
        );

        $sub->onKernelRequest(
            $this->mockEvent('foo', '/foo', $reguestsLocale)
        );
    }

    public function localesDataProvider(): array
    {
        $locale = 'en';
        $user1 = (new User())->setLocale($locale);
        $user2 = (new User())->setLocale($locale);
        $user3 = (new User())->setLocale($locale);
        $user4 = null;

        return [
            ['en', ['en', 'es'], $user1, 0], // don't change in db if locales are the same
            ['es', ['en', 'es'], $user2, 1], // change because locale was changedd
            ['pl', ['en', 'es'], $user3, 0], // locale doesn't exists
            ['fr', ['en', 'es', 'fr'], $user4, 0], // when user is anon
        ];
    }

    /** @return RequestEvent|MockObject */
    private function mockEvent(
        ?string $csrf,
        string $path,
        string $locale
    ): RequestEvent {
        $event = $this->createMock(RequestEvent::class);
        $req = $this->createMock(Request::class);

        $hb = $this->createMock(HeaderBag::class);
        $hb->method('get')->willReturn($csrf);

        $pb = $this->createMock(ParameterBag::class);
        $pb->method('get')->with('_locale')->willReturn($locale);

        $si = $this->createMock(SessionInterface::class);
        $si->method('get')->with('_locale')->willReturn('en');

        $req->headers = $hb;
        $req->method('getPathInfo')->willReturn($path);
        $req->method('getSession')->willReturn($si);
        $req->method('hasPreviousSession')->willReturn(true);
        $req->attributes = $pb;

        $event->method('getRequest')->willReturn($req);

        return $event;
    }
}
