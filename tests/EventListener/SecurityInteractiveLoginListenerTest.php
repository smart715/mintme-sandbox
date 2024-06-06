<?php declare(strict_types = 1);

namespace App\Tests\EventListener;

use App\Entity\User;
use App\EventListener\SecurityInteractiveLoginListener;
use App\Manager\AuthAttemptsManagerInterface;
use App\Manager\BlacklistIpManagerInterface;
use App\Manager\UserManagerInterface;
use App\Mercure\Authorization as MercureAuthorization;
use App\Repository\UserRepository;
use App\Services\TranslatorService\TranslatorInterface;
use Doctrine\ORM\EntityManagerInterface;
use FOS\UserBundle\Model\UserInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

class SecurityInteractiveLoginListenerTest extends TestCase
{
    public function testInteractiveLogin(): void
    {
        $listener = new SecurityInteractiveLoginListener(
            $this->mockEntityManager(),
            $this->mockAuthAttemptsInterface(),
            $this->mockBlackListIpManager(),
            $this->mockUserManager(),
            $this->mockMercureAuthorization(),
            $this->mockTokenStorage(),
            $this->mockTranslator(),
        );
        $listener->onSecurityInteractiveLogin($this->mockInteractiveLoginEvent());
    }

    private function mockEntityManager(): EntityManagerInterface
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())
            ->method('getRepository')
            ->willReturn($this->mockUserRepository());

        $entityManager->expects($this->once())
            ->method('flush');

        return $entityManager;
    }

    private function mockInteractiveLoginEvent(): InteractiveLoginEvent
    {
        $interactiveLoginEvent = $this->createMock(InteractiveLoginEvent::class);

        $interactiveLoginEvent->expects($this->once())
            ->method('getRequest')
            ->willReturn($this->mockRequest());

        $interactiveLoginEvent->expects($this->once())
            ->method('getAuthenticationToken')
            ->willReturn($this->mockToken());

        return $interactiveLoginEvent;
    }

    private function mockRequest(): Request
    {
        $request = $this->createMock(Request::class);

        $request->expects($this->once())
            ->method('getSession')
            ->willReturn($this->mockSession());

        $request->expects($this->once())
            ->method('getRequestUri')
            ->willReturn('test');

        return $request;
    }

    private function mockSession(): SessionInterface
    {
        $session = $this->createMock(SessionInterface::class);

        $session
            ->method('getId')
            ->willReturn('test');

        return $session;
    }

    private function mockToken(): TokenInterface
    {
        $token = $this->createMock(TokenInterface::class);

        $token->expects($this->once())
            ->method('getUser')
            ->willReturn($this->mockUserInterface());

        return $token;
    }

    private function mockUserInterface(): UserInterface
    {
        $user = $this->createMock(UserInterface::class);

        $user->expects($this->once())
            ->method('getUsername');

        return $user;
    }

    private function mockUserRepository(): UserRepository
    {
        $userRepository = $this->createMock(UserRepository::class);

        $userRepository->expects($this->once())
            ->method('findOneBy')
            ->willReturn($this->mockUser());

        return $userRepository;
    }

    private function mockUser(): User
    {
        return $this->createMock(User::class);
    }

    private function mockAuthAttemptsInterface(): AuthAttemptsManagerInterface
    {
        return $this->createMock(AuthAttemptsManagerInterface::class);
    }

    private function mockBlackListIpManager(): BlacklistIpManagerInterface
    {
        return $this->createMock(BlacklistIpManagerInterface::class);
    }

    private function mockUserManager(): UserManagerInterface
    {
        return $this->createMock(UserManagerInterface::class);
    }

    private function mockMercureAuthorization(): MercureAuthorization
    {
        return $this->createMock(MercureAuthorization::class);
    }

    private function mockTokenStorage(): TokenStorageInterface
    {
        return $this->createMock(TokenStorageInterface::class);
    }

    private function mockTranslator(): TranslatorInterface
    {
        return $this->createMock(TranslatorInterface::class);
    }
}
