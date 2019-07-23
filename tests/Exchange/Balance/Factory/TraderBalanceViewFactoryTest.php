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
        $factory = new TraderBalanceViewFactory($this->mockUserManager([
            $this->mockUserToken($this->mockUser(1), $this->mockDate()),
            $this->mockUserToken($this->mockUser(2), $this->mockDate()),
            $this->mockUserToken($this->mockUser(3), $this->mockDate()),
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
            1,
            5
        );

        $this->assertCount(2, $result);

        $this->assertEquals([
            [$result[0]->getUser()->getId(), $result[0]->getBalance()],
            [$result[1]->getUser()->getId(), $result[1]->getBalance()],
        ], [
            [1, '999'],
            [2, '99'],
        ]);
    }

    public function testCreateWithEmptyBalances(): void
    {
        $userManager = $this->mockUserManager([]);
        $userManager->expects($this->never())->method('getUserToken');
        $userManager->expects($this->never())->method('getUserCrypto');

        $factory = new TraderBalanceViewFactory($userManager, $this->mockConfig());

        $balances = [];

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
            1,
            5
        );

        $this->assertEquals($result, []);
    }

    public function testCreateWithSort(): void
    {
        $factory = new TraderBalanceViewFactory($this->mockUserManager([
            $this->mockUserToken($this->mockUser(2), $this->mockDate()),
            $this->mockUserToken($this->mockUser(1), $this->mockDate()),
            $this->mockUserToken($this->mockUser(3), $this->mockDate()),
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
            3,
            3,
            1,
            5
        );

        $this->assertCount(3, $result);

        $this->assertEquals([
            [$result[0]->getUser()->getId(), $result[0]->getBalance()],
            [$result[1]->getUser()->getId(), $result[1]->getBalance()],
            [$result[2]->getUser()->getId(), $result[2]->getBalance()],
        ], [
            [1, '999'],
            [2, '99'],
            [3, '9'],
        ]);
    }

    public function testCreateWhenTokenIdIsNull(): void
    {
        $userManager = $this->mockUserManager([
            $this->mockUserToken($this->mockUser(1), $this->mockDate()),
            $this->mockUserToken($this->mockUser(2), $this->mockDate()),
            $this->mockUserToken($this->mockUser(3), $this->mockDate()),
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
            1,
            5
        );

        $this->assertCount(0, $result);
    }

    public function testCreateWithManyFetch(): void
    {
        $factory = new TraderBalanceViewFactory($this->mockUserManager([
            $this->mockUserToken($this->mockUser(1), $this->mockDate()),
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
            1,
            5
        );
    }

    public function testCreateWithMaxBalances(): void
    {
        $factory = new TraderBalanceViewFactory($this->mockUserManager([
            $this->mockUserToken($this->mockUser(1), $this->mockDate()),
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
            1,
            5
        );

        $this->assertCount(1, $result);

        $this->assertEquals([
            [$result[0]->getUser()->getId(), $result[0]->getBalance()],
        ], [
            [1, '999'],
        ]);
    }

    public function testCreateMaxArgument(): void
    {
        $factory = new TraderBalanceViewFactory($this->mockUserManager([
            $this->mockUserToken($this->mockUser(1), $this->mockDate()),
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
            1,
            3
        );

        $this->assertCount(1, $result);

        $this->assertEquals([
            [$result[0]->getUser()->getId(), $result[0]->getBalance()],
        ], [
            [1, '999'],
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
