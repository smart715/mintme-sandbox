<?php declare(strict_types = 1);

namespace App\Tests\Manager;

use App\Entity\Token\Token;
use App\Entity\TopHolder;
use App\Entity\User;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Exchange\Balance\Factory\TraderBalanceView;
use App\Exchange\Balance\Model\BalanceResult;
use App\Manager\TokenManagerInterface;
use App\Manager\TopHolderManager;
use App\Repository\TopHolderRepository;
use App\Tests\Mocks\MockMoneyWrapper;
use App\Utils\Symbols;
use Doctrine\ORM\EntityManagerInterface;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\TestCase;

class TopHolderManagerTest extends TestCase
{

    use MockMoneyWrapper;

    public function testUpdateTopHolders(): void
    {
        $user = $this->createMock(User::class);

        $topHoldersFromExchange = [
            (new TraderBalanceView($user, '1', null))->setRank(4),
            (new TraderBalanceView($user, '2', null))->setRank(5),
            (new TraderBalanceView($user, '3', null))->setRank(7),
        ];

        $bh = $this->createMock(BalanceHandlerInterface::class);

        $bh
            ->expects($this->exactly(1))
            ->method('topHolders')
            ->willReturn($topHoldersFromExchange);

        $moneyWrapper = $this->mockMoneyWrapper();

        $topHolderManager = new TopHolderManager(
            $this->mockRepository(0, null, 0, [], [], 1),
            $this->mockEM(3, 1),
            $bh,
            $moneyWrapper,
            $this->createMock(TokenManagerInterface::class),
            10
        );

        $topHolderManager->updateTopHolders($this->createMock(Token::class));
    }

    /** @dataProvider providerShouldUpdateTopHolders */
    public function testShouldUpdateTopHolders(
        User $user,
        Token $token,
        int $timesBalanceCalled,
        int $timesFindOneByCalled,
        Money $balanceAmount,
        ?TopHolder $topHolder,
        bool $result
    ): void {
        $balanceResult = $this->createMock(BalanceResult::class);
        $balanceResult
            ->method('getAvailable')
            ->willReturn($balanceAmount);

        $bh = $this->createMock(BalanceHandlerInterface::class);
        $bh
            ->expects($this->exactly($timesBalanceCalled))
            ->method('balance')
            ->willReturn($balanceResult);

        $moneyWrapper = $this->mockMoneyWrapper();

        $topHolderManager = new TopHolderManager(
            $this->mockRepository($timesFindOneByCalled, $topHolder),
            $this->mockEM(0, 0),
            $bh,
            $moneyWrapper,
            $this->createMock(TokenManagerInterface::class),
            10
        );

        $this->assertSame($result, $topHolderManager->shouldUpdateTopHolders($user, $token));
    }

    public function providerShouldUpdateTopHolders(): array
    {
        $user = $this->createMock(User::class);
        $user
            ->method('getId')
            ->willReturn(100);

        $tokenWithOwner = $this->createMock(Token::class);
        $tokenWithOwner
            ->method('getOwner')
            ->willReturn($user);

        $tokenNotOwner = $this->createMock(Token::class);

        $moneyOne = new Money('1', new Currency(Symbols::TOK));
        $moneyTwo = new Money('2', new Currency(Symbols::TOK));

        $topHolderOne = $this->createMock(TopHolder::class);
        $topHolderOne->method('getAmount')->willReturn($moneyOne);

        $topHolderTwo = $this->createMock(TopHolder::class);
        $topHolderTwo->method('getAmount')->willReturn($moneyTwo);

        return [
            'case when user is owner' => [$user, $tokenWithOwner, 0, 0, $moneyOne, $topHolderOne, false],
            'case when last holder doesn\'t exists' => [$user, $tokenNotOwner, 1, 1, $moneyOne, null, true],
            'case when last balance from viabtc more than from last holder' =>
                [$user, $tokenNotOwner, 1, 1, $moneyTwo, $topHolderOne, true],
            'case when last balance from viabtc less than from last holder' =>
                [$user, $tokenNotOwner, 1, 1, $moneyOne, $topHolderTwo, false],
            'case when last balance from viabtc equals to amount from last holder' =>
                [$user, $tokenNotOwner, 1, 1, $moneyOne, $topHolderOne, false],
        ];
    }

    public function testGetTopHoldersByOwner(): void
    {
        $tokens = [
            $this->createMock(Token::class),
            $this->createMock(Token::class),
            $this->createMock(Token::class),
        ];

        $tokenManager = $this->createMock(TokenManagerInterface::class);
        $tokenManager
            ->expects($this->once())
            ->method('getOwnTokens')
            ->willReturn($tokens);

        $topHolders = [
            $this->createMock(TopHolder::class),
            $this->createMock(TopHolder::class),
            $this->createMock(TopHolder::class),
        ];


        $topHolderManager = new TopHolderManager(
            $this->mockRepository(0, null, 1, $tokens, $topHolders),
            $this->mockEM(0, 0),
            $this->createMock(BalanceHandlerInterface::class),
            $this->mockMoneyWrapper(),
            $tokenManager,
            10
        );

        $this->assertSame($topHolders, $topHolderManager->getOwnTopHolders());
    }

    /** @dataProvider getTopHolderByUserAndTokenProvider */
    public function testGetTopHolderByUserAndToken(User $user, Token $token, ?TopHolder $result): void
    {
        $repository = $this->mockRepository(
            1,
            $result
        );

        $topHolderManager = new TopHolderManager(
            $repository,
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(BalanceHandlerInterface::class),
            $this->mockMoneyWrapper(),
            $this->createMock(TokenManagerInterface::class),
            10
        );

        $this->assertSame($result, $topHolderManager->getTopHolderByUserAndToken($user, $token));
    }

    public function getTopHolderByUserAndTokenProvider(): array
    {
        $user = $this->createMock(User::class);
        $token = $this->createMock(Token::class);
        $topHolder = (new TopHolder())
            ->setUser($user)
            ->setToken($token)
            ->setRank(1);

        return [
            'existing user token return real top holder' => [$user, $token, $topHolder],
            'non-existent user or token in db return null' => [
                $this->createMock(User::class),
                $this->createMock(Token::class),
                null,
            ],
        ];
    }

    public function mockRepository(
        int $findOneByCount,
        ?TopHolder $topHolder = null,
        int $findByTokensCount = 0,
        array $tokensFindByTokens = [],
        array $findByTokensRes = [],
        int $findByCalled = 0,
        array $findByResult = []
    ): TopHolderRepository {
        $thRepo = $this->createMock(TopHolderRepository::class);

        $thRepo
            ->expects($this->exactly($findOneByCount))
            ->method('findOneBy')
            ->willReturn($topHolder);

        $thRepo
            ->expects($this->exactly($findByTokensCount))
            ->method('findByTokens')
            ->with($tokensFindByTokens)
            ->willReturn($findByTokensRes);

        $thRepo
            ->expects($this->exactly($findByCalled))
            ->method('findBy')
            ->willReturn($findByResult);

        return $thRepo;
    }

    public function mockEM(int $persistCount, int $flushCount): EntityManagerInterface
    {
        $em = $this->createMock(EntityManagerInterface::class);

        $em
            ->expects($this->exactly($persistCount))
            ->method('persist');

        $em
            ->expects($this->exactly($flushCount))
            ->method('flush');

        return $em;
    }
}
