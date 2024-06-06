<?php declare(strict_types = 1);

namespace App\Tests\EventListener;

use App\Config\FailedLoginConfig;
use App\Entity\Blacklist\BlacklistIp;
use App\Entity\User;
use App\EventListener\LoginListener;
use App\Mailer\MailerInterface;
use App\Manager\AuthAttemptsManagerInterface;
use App\Manager\BlacklistIpManagerInterface;
use App\Manager\UserManager;
use App\Services\TranslatorService\TranslatorInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Event\AuthenticationFailureEvent;

class LoginListenerTest extends TestCase
{
    /** @dataProvider onAuthenticationFailureProvider */
    public function testOnAuthenticationFailure(
        bool $isBlacklisted,
        bool $userExist,
        bool $canDecrementChances,
        int $chances = 1
    ): void {
        $listener = new LoginListener(
            $this->mockUserManager($isBlacklisted, $userExist),
            $this->mockFlashBag($isBlacklisted, $userExist),
            $this->mockTranslator(),
            $this->mockAuthAttemptsManager($canDecrementChances, $chances),
            $this->mockFailedLoginConfig(),
            $this->mockMailer($chances),
            $this->mockBlacklistIpManager($isBlacklisted),
            $this->mockRequestStack()
        );

        $event = $this->mockAuthenticationFailureEvent();

        $listener->onAuthenticationFailure($event);
    }

    public function onAuthenticationFailureProvider(): array
    {
        return [
            "user is blacklisted" => [
                'isBlacklisted' => true,
                'userExist' => true,
                'canDecrementChances' => true,
            ],
            "user does not exist" => [
                'isBlacklisted' => false,
                'userExist' => false,
                'canDecrementChances' => true,
            ],
            "user exist but can't decrement chances" => [
                'isBlacklisted' => false,
                'userExist' => true,
                'canDecrementChances' => false,
            ],
            "user exist and can decrement chances" => [
                'isBlacklisted' => false,
                'userExist' => true,
                'canDecrementChances' => true,
                'chances' => 2,
            ],
            "user exist but have no chances" => [
                'isBlacklisted' => false,
                'userExist' => true,
                'canDecrementChances' => true,
                'chances' => 0,
            ],
            "user exist and have more than zero chances" => [
                'isBlacklisted' => false,
                'userExist' => true,
                'canDecrementChances' => true,
                'chances' => 1,
            ],
        ];
    }

    private function mockUserManager(bool $isBlacklisted, ?bool $userExist = null): UserManager
    {
        $userManager = $this->createMock(UserManager::class);
        $userManager->expects($isBlacklisted ? $this->never() : $this->once())
            ->method('findUserByEmail')
            ->willReturn($userExist ? $this->mockUser() : null);

        return $userManager;
    }

    private function mockFlashBag(?bool $isBlacklisted = null, ?bool $userExist = null): FlashBagInterface
    {
        $flashBag = $this->createMock(FlashBagInterface::class);
        $flashBag->expects(!$isBlacklisted && $userExist ? $this->once() : $this->never())
            ->method('set');

        return $flashBag;
    }

    private function mockTranslator(): TranslatorInterface
    {
        return $this->createMock(TranslatorInterface::class);
    }

    private function mockAuthAttemptsManager(
        bool $canDecrementChances,
        int $chances = 1
    ): AuthAttemptsManagerInterface {
        $authAttemptsManager = $this->createMock(AuthAttemptsManagerInterface::class);

            $authAttemptsManager->method('canDecrementChances')->willReturn($canDecrementChances);
            $authAttemptsManager->method('decrementChances')->willReturn($chances);

        return $authAttemptsManager;
    }

    private function mockFailedLoginConfig(): FailedLoginConfig
    {
        return $this->createMock(FailedLoginConfig::class);
    }

    private function mockMailer(?int $chances = null): MailerInterface
    {
        $mailer = $this->createMock(MailerInterface::class);

        if (0 === $chances) {
            $mailer->expects($this->once())->method('sendFailedLoginBlock');
        } elseif (null === $chances) {
            $mailer->expects($this->never())->method('sendFailedLoginBlock');
        }

        return $mailer;
    }

    private function mockBlacklistIpManager(bool $isBlacklisted): BlacklistIpManagerInterface
    {
        $blacklistIpManager = $this->createMock(BlacklistIpManagerInterface::class);
        $blacklistIpManager->expects($this->once())
            ->method('getBlacklistIpByAddress')
            ->willReturn($this->mockBlacklist());

        $blacklistIpManager->expects($this->once())
            ->method('decrementChances');

        $blacklistIpManager->expects($this->once())
            ->method('isBlacklistedIp')
            ->willReturn($isBlacklisted);

        return $blacklistIpManager;
    }

    private function mockRequestStack(): RequestStack
    {
        $requestStack = $this->createMock(RequestStack::class);
        $requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($this->mockRequest());

        return $requestStack;
    }

    private function mockRequest(): Request
    {
        $request = $this->createMock(Request::class);
        $request->expects($this->once())->method('getClientIp')->willReturn('TEST');

        return $request;
    }

    private function mockBlacklist(): BlacklistIp
    {
        return $this->createMock(BlacklistIp::class);
    }

    private function mockAuthenticationFailureEvent(): AuthenticationFailureEvent
    {
        $authenticationFailureEvent = $this->createMock(AuthenticationFailureEvent::class);
        $authenticationFailureEvent->method('getAuthenticationToken')->willReturn($this->mockTokenInterface());

        return $authenticationFailureEvent;
    }

    private function mockUser(): User
    {
        return $this->createMock(User::class);
    }

    private function mockTokenInterface(): TokenInterface
    {
        return $this->createMock(TokenInterface::class);
    }
}
