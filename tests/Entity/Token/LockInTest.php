<?php declare(strict_types = 1);

namespace App\Tests\Entity\Token;

use App\Entity\Token\LockIn;
use App\Entity\Token\Token;
use App\Wallet\Money\MoneyWrapper;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\TestCase;

class LockInTest extends TestCase
{
    public function testGetHourlyRate(): void
    {
        $li = new LockIn($this->mockToken());
        $li->setDeployed();

        $this->assertEquals('0', $li->getHourlyRate()->getAmount());
        $li->setAmountToRelease(new Money(10000000000, new Currency(MoneyWrapper::TOK_SYMBOL)));
        $this->assertEquals('1141553', $li->getHourlyRate()->getAmount());
        $li->setReleasePeriod(10);
        $this->assertEquals('114155', $li->getHourlyRate()->getAmount());
    }

    public function testGetReleasedAmount(): void
    {
        $li = new LockIn($this->mockToken());
        $li->setDeployed();

        $this->assertEquals('0', $li->getReleasedAmount()->getAmount());
        $li->setAmountToRelease(new Money(10000000000, new Currency(MoneyWrapper::TOK_SYMBOL)));
        $this->assertEquals('0', $li->getReleasedAmount()->getAmount());
        $li->setReleasedAtStart(1000000);
        $this->assertEquals('1000000', $li->getReleasedAmount()->getAmount());
    }

    public function testUpdateFrozenAmount(): void
    {
        $li = new LockIn($this->mockToken());
        $initialAmount = 1000000;
        $amountToRelease = 9000000;

        $li
            ->setDeployed()
            ->setAmountToRelease(new Money($amountToRelease, new Currency(MoneyWrapper::TOK_SYMBOL)))
            ->setReleasedAtStart($initialAmount)
            ->updateFrozenAmount();

        $this->assertEquals($initialAmount, (int)$li->getReleasedAmount()->getAmount());

        $li
            ->setDeployed((new \DateTimeImmutable())->add(new \DateInterval('P5D')))
            ->updateFrozenAmount();

        $this->assertEquals(5 * 24, $li->getCountHoursFromDeploy());
        $this->assertGreaterThan($initialAmount, $li->getReleasedAmount()->getAmount());
        $this->assertLessThan($amountToRelease, $li->getFrozenAmount()->getAmount());
    }

    private function mockToken(): Token
    {
        return $this->createMock(Token::class);
    }
}
