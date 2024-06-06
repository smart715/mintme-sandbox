<?php declare(strict_types = 1);

namespace App\Tests\Manager;

use App\Entity\User;
use App\Entity\UserLoginInfo;
use App\Manager\UserLoginInfoManager;
use App\Repository\UserLoginInfoRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

class UserLoginInfoManagerTest extends TestCase
{
    public function setUp(): void
    {
        $_SERVER['HTTP_USER_AGENT'] = 'TEST_AGENT';
    }

    /**
     * @dataProvider loginInfoDataProvider
     */
    public function testUpdateUserDeviceLoginInfo(
        object $user,
        ?object $loginInfo,
        int $persist,
        int $flush,
        int $dispatch
    ): void {
        $deviceIp = 'TEST_IP';

        $request = $this->mockRequest();
        $request
            ->method('getClientIp')
            ->willReturn($deviceIp);
        
        $authToken = $this->mockAuthToken();
        $authToken
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $event = $this->mockInteractiveLoginEvent();
        $event
            ->expects($this->once())
            ->method('getRequest')
            ->willReturn($request);

        $event
            ->expects($this->once())
            ->method('getAuthenticationToken')
            ->willReturn($authToken);

        $userLoginInfoRepository = $this->mockUserLoginInfoRepository();
        $userLoginInfoRepository
            ->method('getStoreUserDeviceInfo')
            ->with($user, $deviceIp)
            ->willReturn($loginInfo);

        $entityManager = $this->mockEntityManager();
        $entityManager
            ->method('getRepository')
            ->willReturn($userLoginInfoRepository);

        $entityManager
            ->expects($this->exactly($persist))
            ->method('persist');

        $entityManager
            ->expects($this->exactly($flush))
            ->method('flush');

        $eventDispatcher = $this->mockEventDispatcher();
        $eventDispatcher
            ->expects($this->exactly($dispatch))
            ->method('dispatch');

        $manager = new UserLoginInfoManager($eventDispatcher, $entityManager);

        $manager->updateUserDeviceLoginInfo($event);
    }

    public function loginInfoDataProvider(): array
    {
        return [
            'Do not update user login info' => [
                'user' => $this->mockUser(),
                'loginInfo' => $this->mockUserLoginInfo(),
                'persist' => 0,
                'flush' => 0,
                'dispatch' => 0,
            ],
            'Update user login info' => [
                'user' => $this->mockUser(),
                'loginInfo' => null,
                'persist' => 1,
                'flush' => 1,
                'dispatch' => 1,
            ],
        ];
    }

    /** @return UserLoginInfo|MockObject */
    private function mockUserLoginInfo(): UserLoginInfo
    {
        return $this->createMock(UserLoginInfo::class);
    }

    /** @return EntityManagerInterface|MockObject */
    private function mockEntityManager(): EntityManagerInterface
    {
        return $this->createMock(EntityManagerInterface::class);
    }

    /** @return EventDispatcherInterface|MockObject */
    private function mockEventDispatcher(): EventDispatcherInterface
    {
        return $this->createMock(EventDispatcherInterface::class);
    }

    /** @return InteractiveLoginEvent|MockObject */
    private function mockInteractiveLoginEvent(): InteractiveLoginEvent
    {
        return $this->createMock(InteractiveLoginEvent::class);
    }

    /** @return Request|MockObject */
    private function mockRequest(): Request
    {
        return $this->createMock(Request::class);
    }

    /** @return UserLoginInfoRepository|MockObject */
    private function mockUserLoginInfoRepository(): UserLoginInfoRepository
    {
        return $this->createMock(UserLoginInfoRepository::class);
    }

    /** @return TokenInterface|MockObject */
    private function mockAuthToken(): TokenInterface
    {
        return $this->createMock(TokenInterface::class);
    }

    /** @return User|MockObject */
    private function mockUser(): User
    {
        return $this->createMock(User::class);
    }
}
