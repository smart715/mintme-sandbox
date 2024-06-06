<?php declare(strict_types = 1);

namespace App\Tests\Manager;

use App\Entity\Token\Token;
use App\Entity\TokenSignupBonusCode;
use App\Entity\TokenSignupHistory;
use App\Entity\User;
use App\Exception\ApiBadRequestException;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Exchange\Balance\Model\BalanceResult;
use App\Exchange\Config\TokenSignupBonusConfig;
use App\Manager\TokenManagerInterface;
use App\Manager\TokenSignupBonusCodeManager;
use App\Repository\TokenSignupHistoryRepository;
use App\Utils\Symbols;
use App\Wallet\Money\MoneyWrapperInterface;
use Doctrine\ORM\EntityManagerInterface;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class TokenSignupBonusCodeManagerTest extends TestCase
{
    public function testCreateTokenSignupBonusLink(): void
    {
        $manager = $this->createTokenSignupBonusCodeManager();

        $token = $this->mockToken();

        $this->expectException(ApiBadRequestException::class);

        $tokenSignUpBonusCode = $manager->createTokenSignupBonusLink(
            $token,
            new Money(100, new Currency(Symbols::TOK)),
            10
        );

        $moneyWrapper = $this->mockMoneyWrapper();

        $this->assertSame('10', $moneyWrapper->format($tokenSignUpBonusCode->getAmount()));
        $this->assertSame(10, $tokenSignUpBonusCode->getParticipants());
        $this->assertTrue($tokenSignUpBonusCode->getLockedAmount()->equals(
            new Money(105, new Currency(Symbols::TOK))
        ));
    }

    public function testWithdrawTokenSignupBonus(): void
    {
        $user = $this->createMock(User::class);
        $manager = $this->createTokenSignupBonusCodeManager();
        $token = $this->mockToken(1, 1);
        $manager->withdrawTokenSignupBonus($token, $user, new Money('9999', new Currency(Symbols::TOK)));
    }

    public function testClaimTokenSignupBonus(): void
    {
        $user = $this->createMock(User::class);
        $manager = $this->createTokenSignupBonusCodeManager();

        $token = $this->mockToken();

        $manager->claimTokenSignupBonus($token, $user, new Money('9999', new Currency(Symbols::TOK)));
    }

    private function createTokenSignupBonusCodeManager(): TokenSignupBonusCodeManager
    {
        $tokenSignupHistory = $this->createMock(TokenSignupHistory::class);

        return new TokenSignupBonusCodeManager(
            $this->createMock(EntityManagerInterface::class),
            $this->mockBalanceHandler(),
            $this->mockTokenManager(),
            $this->createMock(TranslatorInterface::class),
            $this->mockMoneyWrapper(),
            $this->mockTokenSignupHistoryRepository($tokenSignupHistory),
            $this->mockTokenSignupBonusConfig()
        );
    }

    private function mockToken(int $lock = 0, int $participants = 0): Token
    {
        $tokenSignUpBonusCode = $this->createMock(TokenSignupBonusCode::class);
        $tokenSignUpBonusCode->expects($this->exactly($lock))
            ->method('setLockedAmount');
        $tokenSignUpBonusCode->expects($this->exactly($participants))
            ->method('setParticipants');
        $tokenSignUpBonusCode->method('getLockedAmount')
            ->willReturn(new Money(100, new Currency(Symbols::TOK)));
        $tokenSignUpBonusCode->method('getParticipants')
            ->willReturn(10);

        $mock = $this->createMock(Token::class);
        $mock->method('getOwner')->willReturn($this->createMock(User::class));
        $mock->method('getSignUpBonusCode')->willReturn($tokenSignUpBonusCode);

        return $mock;
    }

    private function mockBalanceHandler(): BalanceHandlerInterface
    {
        $mock = $this->createMock(BalanceHandlerInterface::class);
        $mock->method('balance')->willReturn($this->createMock(BalanceResult::class));

        return $mock;
    }

    private function mockTokenManager(): TokenManagerInterface
    {
        $balanceMock = $this->createMock(BalanceResult::class);
        $balanceMock->method('getAvailable')->willReturn(
            new Money(99999, new Currency(Symbols::TOK))
        );

        $mock = $this->createMock(TokenManagerInterface::class);
        $mock->method('getRealBalance')->willReturn($balanceMock);

        return $mock;
    }

    private function mockMoneyWrapper(): MoneyWrapperInterface
    {
        $mock = $this->createMock(MoneyWrapperInterface::class);
        $mock->method('parse')
            ->willReturnCallback(fn($amount) => new Money($amount, new Currency(Symbols::TOK)));
        $mock->method('format')
            ->willReturnCallback(fn($amount) => $amount->getAmount());

        return $mock;
    }

    private function mockTokenSignupHistoryRepository(TokenSignupHistory $tokenSignupHistory): TokenSignupHistoryRepository
    {
        $mock = $this->createMock(TokenSignupHistoryRepository::class);
        $mock->method('findOneByUserAndToken')
            ->willReturn($tokenSignupHistory);

        return $mock;
    }

    private function mockTokenSignupBonusConfig(): TokenSignupBonusConfig
    {
        return $this->createMock(TokenSignupBonusConfig::class);
    }
}
