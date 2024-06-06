<?php declare(strict_types = 1);

namespace App\Tests\Manager;

use App\Entity\Token\Token;
use App\Entity\User;
use App\Entity\UserToken;
use App\Manager\UserTokenManager;
use App\Repository\UserTokenRepository;
use Doctrine\ORM\EntityManagerInterface;
use Money\Money;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UserTokenManagerTest extends TestCase
{
    public function testFindByUser(): void
    {
        $user = $this->mockUser();

        $userToken = $this->mockUserToken();

        $userTokenRepository = $this->mockUserTokenRepository();
        $userTokenRepository
            ->expects($this->once())
            ->method('findBy')
            ->with(
                [
                    'user' => $user,
                    'isRemoved' => false,
                ]
            )
            ->willReturn([$userToken]);

        $entityManager = $this->mockEntityManager();

        $userTokenManager = new UserTokenManager(
            $entityManager,
            $userTokenRepository
        );

        $this->assertEquals([$userToken], $userTokenManager->findByUser($user));
    }

    public function testFindByUserToken(): void
    {
        $user = $this->mockUser();
        $user
            ->expects($this->exactly(2))
            ->method('getId')
            ->willReturn(1);

        $token = $this->mockToken();
        $token
            ->expects($this->exactly(2))
            ->method('getId')
            ->willReturn(1);

        $userToken = $this->mockUserToken();

        $userTokenRepository = $this->mockUserTokenRepository();
        $userTokenRepository
            ->expects($this->exactly(2))
            ->method('findByUserToken')
            ->with(1, 1)
            ->willReturnOnConsecutiveCalls($userToken, null);

        $entityManager = $this->mockEntityManager();

        $userTokenManager = new UserTokenManager(
            $entityManager,
            $userTokenRepository
        );

        $this->assertEquals($userToken, $userTokenManager->findByUserToken($user, $token));
        $this->assertNull($userTokenManager->findByUserToken($user, $token));
    }

    public function testUpdateRelation(): void
    {
        $user = $this->mockUser();
        $user
            ->expects($this->once())
            ->method('addToken');
        $user
            ->expects($this->once())
            ->method('getId')
            ->willReturn(1);

        $token = $this->mockToken();
        $token
            ->expects($this->once())
            ->method('getId')
            ->willReturn(1);

        $entityManager = $this->mockEntityManager();
        $entityManager
            ->expects($this->exactly(2))
            ->method('persist');
        $entityManager
            ->expects($this->once())
            ->method('flush');

        $userTokenRepository = $this->mockUserTokenRepository();
        $userTokenRepository
            ->expects($this->once())
            ->method('findByUserToken')
            ->willReturn(null);

        $userTokenManager = new UserTokenManager(
            $entityManager,
            $userTokenRepository
        );

        $userTokenManager->updateRelation(
            $user,
            $token,
            Money::USD(50)
        );
    }

    /** @return EntityManagerInterface|MockObject */
    private function mockEntityManager(): EntityManagerInterface
    {
        return $this->createMock(EntityManagerInterface::class);
    }

    /** @return Token|MockObject */
    private function mockToken(): Token
    {
        return $this->createMock(Token::class);
    }

    /** @return UserTokenRepository|MockObject */
    private function mockUserTokenRepository(): UserTokenRepository
    {
        return $this->createMock(UserTokenRepository::class);
    }

    /** @return UserToken|MockObject */
    private function mockUserToken(): UserToken
    {
        return $this->createMock(UserToken::class);
    }

    /** @return User|MockObject */
    private function mockUser(): User
    {
        return $this->createMock(User::class);
    }
}
