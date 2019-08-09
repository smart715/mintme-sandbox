<?php declare(strict_types = 1);

namespace App\Tests\Exchange\Balance\Factory;

use App\Entity\User;
use App\Entity\UserToken;
use App\Exchange\Balance\Factory\TraderBalanceView;
use App\Exchange\Balance\Factory\TraderBalanceViewFactory;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class TraderBalanceViewFactoryTest extends TestCase
{
    public function testCreateWithSort(): void
    {
        $factory = new TraderBalanceViewFactory();

        /** @var TraderBalanceView[] $result */
        $result = $factory->create(
            [
                $this->mockUserToken($this->mockUser(2), $this->mockDate()),
                $this->mockUserToken($this->mockUser(3), $this->mockDate()),
                $this->mockUserToken($this->mockUser(1), $this->mockDate()),
            ],
            [
                1 => '999',
                3 => '9',
                2 => '99',
            ]
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

    public function testCreateWithUndefinedOffset(): void
    {
        $factory = new TraderBalanceViewFactory();

        /** @var TraderBalanceView[] $result */
        $result = $factory->create(
            [
                $this->mockUserToken($this->mockUser(1), $this->mockDate()),
                $this->mockUserToken($this->mockUser(2), $this->mockDate()),
                $this->mockUserToken($this->mockUser(3), $this->mockDate()),
            ],
            [
                1 => '999',
                2 => '99',
            ]
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
     * @return DateTimeImmutable|MockObject
     */
    private function mockDate(): DateTimeImmutable
    {
        return $this->createMock(DateTimeImmutable::class);
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
}
