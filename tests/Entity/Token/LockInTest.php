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

        $this->assertEquals('0', $li->getHourlyRate()->getAmount());
        $li->setAmountToRelease(new Money(10000000000, new Currency(MoneyWrapper::TOK_SYMBOL)));
        $this->assertEquals('1141553', $li->getHourlyRate()->getAmount());
        $li->setReleasePeriod(10);
        $this->assertEquals('114155', $li->getHourlyRate()->getAmount());
    }

    public function testGetReleasedAmount(): void
    {
        $li = new LockIn($this->mockToken());

        $this->assertEquals('0', $li->getReleasedAmount()->getAmount());
        $li->setAmountToRelease(new Money(10000000000, new Currency(MoneyWrapper::TOK_SYMBOL)));
        $this->assertEquals('0', $li->getReleasedAmount()->getAmount());
        $li->setReleasedAtStart(1000000);
        $this->assertEquals('1000000', $li->getReleasedAmount()->getAmount());
    }

    /** @runInSeparateProcess */
    public function testUpdateFrozenAmount(): void
    {
        $li = new LockIn($this->mockToken());

        $li->setAmountToRelease(new Money(10000000000, new Currency(MoneyWrapper::TOK_SYMBOL)))
            ->setReleasedAtStart(1000000)
            ->updateFrozenAmount();

        $this->assertEquals('2141553', $li->getReleasedAmount()->getAmount());

        array_map(function () use (&$li): void {
            $li->updateFrozenAmount();
        }, range(1, 8765)); // 8760 hours in year

        $this->assertEquals('10001000000', $li->getReleasedAmount()->getAmount());
    }

    private function mockToken(): Token
    {
        return $this->createMock(Token::class);
    }
}
