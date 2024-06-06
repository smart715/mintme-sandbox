<?php declare(strict_types = 1);

namespace App\Tests\Entity\Token;

use App\Entity\Token\LockIn;
use App\Entity\Token\Token;
use App\Entity\Token\TokenDeploy;
use App\Utils\Symbols;
use App\Wallet\Money\MoneyWrapper;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class LockInTest extends TestCase
{
    public function testGetHourlyRate(): void
    {
        $li = new LockIn($this->mockToken());

        $this->assertEquals('0', $li->getHourlyRate()->getAmount());
        $li->setAmountToRelease(new Money(10000000000, new Currency(Symbols::TOK)));
        $this->assertEquals('1141553', $li->getHourlyRate()->getAmount());
        $li->setReleasePeriod(10);
        $this->assertEquals('114155', $li->getHourlyRate()->getAmount());
    }

    public function testGetReleasedAmount(): void
    {
        $li = new LockIn($this->mockToken());

        $this->assertEquals('0', $li->getReleasedAmount()->getAmount());
        $li->setAmountToRelease(new Money(10000000000, new Currency(Symbols::TOK)));
        $this->assertEquals('0', $li->getReleasedAmount()->getAmount());
        $li->setReleasedAtStart('1000000');
        $this->assertEquals('1000000', $li->getReleasedAmount()->getAmount());
    }

    public function testGetReleasedAmountForDeployedToken(): void
    {
        /** @var Token|MockObject $token */
        $token = $this->mockToken();
        $li = new LockIn($token);
        $releasedAtStart = '1000000';

        $token
            ->expects($this->once())
            ->method('isDeployed')
            ->willReturn(true);

        $releasedAtStartObj = new Money($releasedAtStart, new Currency(Symbols::TOK));
        $li->setReleasedAtStart($releasedAtStart);

        $this->assertEquals(
            $releasedAtStartObj->add($li->getEarnedMoneyFromDeploy()),
            $li->getReleasedAmount()
        );
    }

    public function testUpdateFrozenAmount(): void
    {
        $li = new LockIn($this->mockToken());
        $amountToRelease = new Money('9000000', new Currency(Symbols::TOK));

        $li
            ->setAmountToRelease($amountToRelease)
            ->updateFrozenAmount();

        $this->assertEquals(
            $amountToRelease->subtract($li->getHourlyRate())->getAmount(),
            $li->getFrozenAmount()->getAmount()
        );
    }

    public function testUpdateFrozenAmountForDeployedToken(): void
    {
        /** @var Token|MockObject $token */
        $token = $this->mockToken();
        $li = new LockIn($token);
        $initialAmount = '1000000';
        $amountToRelease = new Money(9000000, new Currency(Symbols::TOK));

        $token
            ->expects($this->any())
            ->method('isDeployed')->willReturn(true);

        $li
            ->setAmountToRelease($amountToRelease)
            ->setReleasedAtStart($initialAmount)
            ->updateFrozenAmount();


        $this->assertEquals($initialAmount, $li->getReleasedAmount()->getAmount());

        $this->assertEquals($amountToRelease->getAmount(), $li->getFrozenAmount()->getAmount());

        /** @var Token|MockObject $token */
        $deploy = $this->mockTokenDeploy((new \DateTimeImmutable())->add(new \DateInterval('P5D')));
        $token = $this->mockToken($deploy);

        $li = new LockIn($token);

        $li
            ->setAmountToRelease($amountToRelease)
            ->setReleasedAtStart($initialAmount)
            ->updateFrozenAmount();

        $this->assertEquals(
            $amountToRelease->subtract($li->getHourlyRate())->getAmount(),
            $li->getFrozenAmount()->getAmount()
        );

        $this->assertEquals(5 * 24, $li->getCountHoursFromDeploy());
        $this->assertGreaterThan($initialAmount, $li->getReleasedAmount()->getAmount());
        $this->assertLessThan($amountToRelease, $li->getFrozenAmount()->getAmount());
    }

    private function mockToken(?TokenDeploy $deploy = null): Token
    {
        $token = $this->createMock(Token::class);
        $token
            ->method('getMainDeploy')
            ->willReturn($deploy ?? $this->mockTokenDeploy());

        return $token;
    }

    private function mockTokenDeploy(?\DateTimeImmutable $date = null): TokenDeploy
    {
        $deploy = $this->createMock(TokenDeploy::class);
        $deploy
            ->method('getDeployDate')
            ->willReturn($date ?? new \DateTimeImmutable());

        return $deploy;
    }
}
