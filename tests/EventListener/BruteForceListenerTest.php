<?php declare(strict_types = 1);

namespace App\Tests\EventListener;

use App\EventListener\BruteForceListener;
use App\Logger\UserActionLogger;
use PHPUnit\Framework\MockObject\Matcher\Invocation;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class BruteForceListenerTest extends TestCase
{
    public function testOnSchebtwofactorAuthenticationAttempt(): void
    {
        $listener = new BruteForceListener(
            $this->mockLogger(),
            $this->mockSession(0, 0),
            $this->mockTokenStorage($this->never()),
            'foo'
        );

        $listener->onSchebtwofactorAuthenticationAttempt();
    }

    public function testOnSchebtwofactorAuthenticationAttemptWithException(): void
    {
        $listener = new BruteForceListener(
            $this->mockLogger(),
            $this->mockSession(0, 10),
            $this->mockTokenStorage($this->once()),
            'foo'
        );

        $this->expectException(AuthenticationException::class);
        $listener->onSchebtwofactorAuthenticationAttempt();
    }

    public function testOnSchebtwofactorAuthenticationSuccessWithAttempts(): void
    {
        $session = $this->createMock(Session::class);
        $session->method('get')->willReturn(1);
        $session->expects($this->once())->method('remove');

        $listener = new BruteForceListener(
            $this->mockLogger(),
            $session,
            $this->mockTokenStorage($this->never()),
            'foo'
        );

        $listener->onSchebtwofactorAuthenticationSuccess();
    }

    public function testOnSchebtwofactorAuthenticationSuccessWithoutAttempts(): void
    {
        $session = $this->createMock(Session::class);
        $session->method('get')->willReturn(0);
        $session->expects($this->never())->method('remove');

        $listener = new BruteForceListener(
            $this->mockLogger(),
            $session,
            $this->mockTokenStorage($this->never()),
            'foo'
        );

        $listener->onSchebtwofactorAuthenticationSuccess();
    }

    public function testOnSchebtwofactorAuthenticationFailure(): void
    {
        $session = $this->createMock(Session::class);
        $session->method('get')->willReturn(1);
        $session->expects($this->once())->method('set')->with($this->anything(), 2);

        $listener = new BruteForceListener(
            $this->mockLogger(),
            $session,
            $this->mockTokenStorage($this->never()),
            'foo'
        );

        $listener->onSchebtwofactorAuthenticationFailure();
    }

    private function mockLogger(): UserActionLogger
    {
        return $this->createMock(UserActionLogger::class);
    }

    private function mockSession(int $set, int $get): Session
    {
        $session = $this->createMock(Session::class);
        $session->method('get')->willReturn($get);

        $flash = $this->createMock(FlashBagInterface::class);
        $flash->method('set');

        $session->method('getFlashBag')->willReturn($flash);

        return $session;
    }

    private function mockTokenStorage(Invocation $invocation): TokenStorageInterface
    {
        $ts = $this->createMock(TokenStorageInterface::class);
        $ts->expects($invocation)->method('setToken');

        return $ts;
    }
}
