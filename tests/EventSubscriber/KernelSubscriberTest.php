<?php declare(strict_types = 1);

namespace App\Tests\EventSubscriber;

use App\Entity\User;
use App\EventSubscriber\KernelSubscriber;
use App\Manager\ProfileManagerInterface;
use PHPUnit\Framework\MockObject\Matcher\Invocation;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class KernelSubscriberTest extends TestCase
{
    public function testOnRequest(): void
    {
        $sub = new KernelSubscriber(
            true,
            $this->mockProfileManager($this->once()),
            $this->mockTokenStorage($this->mockToken($this->createMock(User::class))),
            $this->mockCsrfTokenManager(true)
        );

        $sub->onRequest(
            $this->mockEvent('foo', '/api/foo', false, false)
        );
    }

    public function testOnRequestTokIsNotString(): void
    {
        $sub = new KernelSubscriber(
            true,
            $this->mockProfileManager($this->never()),
            $this->mockTokenStorage($this->mockToken($this->createMock(User::class))),
            $this->mockCsrfTokenManager(true)
        );

        $event = $this->mockEvent(null, '/foo/bar', true, true);
        $event->expects($this->once())->method('setResponse');

        $sub->onRequest($event);
    }

    public function testOnRequestTokIsNotValidWithApi(): void
    {
        $sub = new KernelSubscriber(
            true,
            $this->mockProfileManager($this->never()),
            $this->mockTokenStorage($this->mockToken($this->createMock(User::class))),
            $this->mockCsrfTokenManager(false)
        );

        $event = $this->mockEvent('foo', '/api/bar', true, true);
        $event->expects($this->once())->method('setResponse');

        $sub->onRequest($event);
    }

    /** @return TokenInterface|MockObject */
    private function mockToken(User $user): TokenInterface
    {
        $tok = $this->createMock(TokenInterface::class);
        $tok->method('getUser')->willReturn($user);

        return $tok;
    }

    /** @return RequestEvent|MockObject */
    private function mockEvent(?string $csrf, string $path, bool $isXml, bool $isImgFilter): RequestEvent
    {
        $event = $this->createMock(RequestEvent::class);
        $req = $this->createMock(Request::class);

        $hb = $this->createMock(HeaderBag::class);
        $hb->method('get')->willReturn($csrf);

        $req->headers = $hb;
        $req->method('getPathInfo')->willReturn($path);
        $req->method('isXmlHttpRequest')->willReturn($isXml);
        $req->method('isImgFilterRequest')->willReturn($isImgFilter);

        $event->method('getRequest')->willReturn($req);

        return $event;
    }

    /** @return ProfileManagerInterface|MockObject */
    private function mockProfileManager(Invocation $invocation): ProfileManagerInterface
    {
        $pm = $this->createMock(ProfileManagerInterface::class);
        $pm->expects($invocation)->method('createHash');

        return $pm;
    }

    /** @return TokenStorageInterface|MockObject */
    private function mockTokenStorage(?TokenInterface $token = null): TokenStorageInterface
    {
        $ts = $this->createMock(TokenStorageInterface::class);
        $ts->method('getToken')->willReturn($token);

        return $ts;
    }

    /** @return CsrfTokenManagerInterface|MockObject */
    private function mockCsrfTokenManager(bool $isValid): CsrfTokenManagerInterface
    {
        $ctm = $this->createMock(CsrfTokenManagerInterface::class);
        $ctm->method('isTokenValid')->willReturn($isValid);

        return $ctm;
    }
}
