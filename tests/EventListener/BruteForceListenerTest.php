<?php declare(strict_types = 1);

namespace App\Tests\EventListener;

use App\EventListener\BruteForceListener;
use App\Logger\UserActionLogger;
use App\Services\TranslatorService\TranslatorInterface;
use PHPUnit\Framework\MockObject\Rule\InvokedCount;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class BruteForceListenerTest extends TestCase
{
    public function testOnSchebtwofactorAuthenticationSuccessWithAttempts(): void
    {
        $session = $this->createMock(Session::class);
        $session->method('get')->willReturn(1);
        $session->expects($this->once())->method('remove');

        $listener = new BruteForceListener(
            $this->mockLogger(),
            $session,
            $this->mockTokenStorage($this->never()),
            'foo',
            $this->mockTranslator()
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
            'foo',
            $this->mockTranslator()
        );

        $listener->onSchebtwofactorAuthenticationSuccess();
    }

    public function testOnSchebtwofactorAuthenticationFailure(): void
    {
        $session = $this->mockSession(1, 1);

        /** @phpstan-ignore-next-line */
        $session->expects($this->once())->method('set')->with($this->anything(), 2);

        $listener = new BruteForceListener(
            $this->mockLogger(),
            $session,
            $this->mockTokenStorage($this->never()),
            'foo',
            $this->mockTranslator()
        );

        $listener->onSchebtwofactorAuthenticationFailure();
    }

    public function testOnSchebtwofactorAuthenticationFailureWithException(): void
    {
        $session = $this->mockSession(1, 10);

        /** @phpstan-ignore-next-line */
        $session->expects($this->once())->method('set')->with($this->anything(), 11);

        $listener = new BruteForceListener(
            $this->mockLogger(),
            $session,
            $this->mockTokenStorage($this->once()),
            'foo',
            $this->mockTranslator()
        );

        $this->expectException(AuthenticationException::class);
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

    private function mockTokenStorage(InvokedCount $invocation): TokenStorageInterface
    {
        $ts = $this->createMock(TokenStorageInterface::class);
        $ts->expects($invocation)->method('setToken');

        return $ts;
    }

    private function mockTranslator(): TranslatorInterface
    {
        return $this->createMock(TranslatorInterface::class);
    }
}
