<?php declare(strict_types = 1);

namespace App\Tests\Utils\Validator;

use App\Entity\Crypto;
use App\Entity\TradableInterface;
use App\Utils\Validator\MinAmountValidator;
use PHPUnit\Framework\TestCase;

class MinAmountValidatorTest extends TestCase
{
    /** @dataProvider validateProvider */
    public function testValid(
        string $amount,
        bool $isCrypto,
        bool $isValid,
        string $message
    ): void {
        $validator = new MinAmountValidator($this->mockTradable($isCrypto), $amount);

        $this->assertEquals($isValid, $validator->validate());
        $this->assertEquals($message, $validator->getMessage());
    }

    public function validateProvider(): array
    {
        return [
            "valid if amount is larger or equal to the smallest subunit" => [
                "amount" => "0.001",
                "isCrypto" => true,
                "isValid" => true,
                "message" => "Minimum amount is 0.001 MINTME",
            ],
            "invalid if amount is larger or equal to the smallest subunit" => [
                "amount" => "0.0001",
                "isCrypto" => false,
                "isValid" => false,
                "message" => "Minimum amount is 0.001 MINTME",
            ],
        ];
    }

    private function mockTradable(bool $isCrypto): TradableInterface
    {
        $tradable = $isCrypto
            ? $this->createMock(Crypto::class)
            : $this->createMock(TradableInterface::class);
        $tradable
            ->expects($isCrypto ? $this->once() : $this->never())
            ->method('getShowSubunit')
            ->willReturn(4);
        $tradable
            ->method('getSymbol')
            ->willReturn('WEB');

        return $tradable;
    }
}
