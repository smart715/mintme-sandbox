<?php declare(strict_types = 1);

namespace App\Tests;

use App\Wallet\Money\MoneyWrapperInterface;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\Exception;
use PHPUnit\Framework\MockObject\MockObject;

trait MockMoneyWrapper
{
    /**
     * Returns a test double for the specified class.
     *
     * @param mixed $originalClassName
     * @return MockObject|mixed
     * @throws Exception
     */
    abstract protected function createMock($originalClassName);

    private function mockMoneyWrapper(): MoneyWrapperInterface
    {
        $wrapper = $this->createMock(MoneyWrapperInterface::class);

        $wrapper->method('parse')->willReturnCallback(function (string $amount, string $symbol) {
            return new Money($amount, new Currency($symbol));
        });

        $wrapper->method('format')->willReturnCallback(function (Money $money) {
            return $money->getAmount();
        });

        return $wrapper;
    }
}
