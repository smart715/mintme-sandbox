<?php declare(strict_types = 1);

namespace App\Tests\Exchange\Balance\Factory;

use App\Entity\Token\Token;
use App\Entity\User;
use App\Entity\UserToken;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Exchange\Balance\Factory\TraderBalanceView;
use App\Exchange\Balance\Factory\TraderBalanceViewFactory;
use App\Exchange\Config\Config;
use App\Manager\UserManagerInterface;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class TraderBalanceViewFactoryTest extends TestCase
{
    public function testCreate(): void
    {
        $user1 = $this->mockUser(1);
        $user2 = $this->mockUser(2);
        $user3 = $this->mockUser(3);

        $date1 = $this->mockDate();
        $date2 = $this->mockDate();
        $date3 = $this->mockDate();

        $factory = new TraderBalanceViewFactory($this->mockUserManager([
            $this->mockUserToken($user1, $date1),
            $this->mockUserToken($user2, $date2),
            $this->mockUserToken($user3, $date3),
        ]), $this->mockConfig());

        $balances = [
            [1, '999'],
            [2, '99'],
            [3, '9'],
        ];

        $token = $this->mockToken();
        $balanceHandler = $this->mockBalanceHandler();
        $balanceHandler->expects($this->never())->method('topTraders');

        /** @var TraderBalanceView[] $result */
        $result = $factory->create(
            $balanceHandler,
            $balances,
            $token,
            2,
            3,
            1
        );

        $this->assertCount(2, $result);

        $this->assertEquals([
            [$result[0]->getUser(), $result[0]->getBalance(), $result[0]->getDate()],
            [$result[1]->getUser(), $result[1]->getBalance(), $result[1]->getDate()],
        ], [
            [$user1, '999', $date1],
            [$user2, '99', $date2],
        ]);
    }

    public function testCreateWhenTokenIdIsNull(): void
    {
        $user1 = $this->mockUser(1);
        $user2 = $this->mockUser(2);
        $user3 = $this->mockUser(3);

        $date1 = $this->mockDate();
        $date2 = $this->mockDate();
        $date3 = $this->mockDate();

        $userManager = $this->mockUserManager([
            $this->mockUserToken($user1, $date1),
            $this->mockUserToken($user2, $date2),
            $this->mockUserToken($user3, $date3),
        ]);

        $userManager->expects($this->never())->method('getUserToken');

        $factory = new TraderBalanceViewFactory($userManager, $this->mockConfig());

        $balances = [
            [1, '999'],
            [2, '99'],
            [3, '9'],
        ];

        $token = $this->mockToken(false);
        $balanceHandler = $this->mockBalanceHandler();
        $balanceHandler->expects($this->never())->method('topTraders');

        /** @var TraderBalanceView[] $result */
        $result = $factory->create(
            $balanceHandler,
            $balances,
            $token,
            2,
            3,
            1
        );

        $this->assertCount(0, $result);
    }

    public function testCreateWithManyFetch(): void
    {
        $user1 = $this->mockUser(1);

        $date1 = $this->mockDate();

        $factory = new TraderBalanceViewFactory($this->mockUserManager([
            $this->mockUserToken($user1, $date1),
        ]), $this->mockConfig());

        $balances = [
            [1, '999'],
            [2, '99'],
            [3, '9'],
        ];

        $token = $this->mockToken();
        $balanceHandler = $this->mockBalanceHandler();
        $balanceHandler->expects($this->once())->method('topTraders')->with($token, 2, 4, 1);

        /** @var TraderBalanceView[] $result */
        $factory->create(
            $balanceHandler,
            $balances,
            $token,
            2,
            3,
            1
        );
    }

    public function testCreateWithMaxBalances(): void
    {
        $user1 = $this->mockUser(1);

        $date1 = $this->mockDate();

        $factory = new TraderBalanceViewFactory($this->mockUserManager([
            $this->mockUserToken($user1, $date1),
        ]), $this->mockConfig());

        $balances = [
            [1, '999'],
            [2, '99'],
        ];

        $token = $this->mockToken();
        $balanceHandler = $this->mockBalanceHandler();
        $balanceHandler->expects($this->never())->method('topTraders');

        /** @var TraderBalanceView[] $result */
        $result = $factory->create(
            $balanceHandler,
            $balances,
            $token,
            2,
            3,
            1
        );

        $this->assertCount(1, $result);

        $this->assertEquals([
            [$result[0]->getUser(), $result[0]->getBalance(), $result[0]->getDate()],
        ], [
            [$user1, '999', $date1],
        ]);
    }

    /**
     * @return UserManagerInterface|MockObject
     */
    private function mockUserManager(array $UsersTokens): UserManagerInterface
    {
        $manager = $this->createMock(UserManagerInterface::class);
        $manager->method('getUserToken')->willReturn($UsersTokens);

        return $manager;
    }

    /**
     * @return UserToken|MockObject
     */
    private function mockUserToken(MockObject $user, MockObject $date): UserToken
    {
        $userToken = $this->createMock(UserToken::class);
        $userToken->method('getUser')->willReturn($user);
        $userToken->method('getCreated')->willReturn($date);

        return $userToken;
    }

    /**
     * @return Config|MockObject
     */
    private function mockConfig(): Config
    {
        return $this->createMock(Config::class);
    }

    /**
     * @return User|MockObject
     */
    private function mockUser(int $id): User
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn($id);

        return $user;
    }

    /**
     * @return Token|MockObject
     */
    private function mockToken(bool $withId = true): Token
    {
        $token = $this->createMock(Token::class);
        $token->method('getId')->willReturn($withId ? 1: null);

        return $token;
    }

    /**
     * @return BalanceHandlerInterface|MockObject
     */
    private function mockBalanceHandler(): BalanceHandlerInterface
    {
        return $this->createMock(BalanceHandlerInterface::class);
    }

    /**
     * @return DateTimeImmutable|MockObject
     */
    private function mockDate(): DateTimeImmutable
    {
        return $this->createMock(DateTimeImmutable::class);
    }
}
