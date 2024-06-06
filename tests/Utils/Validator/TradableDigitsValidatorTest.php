<?php declare(strict_types = 1);

namespace App\Tests\Utils\Validator;

use App\Entity\Crypto;
use App\Entity\Token\Token;
use App\Entity\TradableInterface;
use App\Utils\Validator\TradableDigitsValidator;
use PHPUnit\Framework\TestCase;

class TradableDigitsValidatorTest extends TestCase
{
    /** @dataProvider validateProvider */
    public function testValid(
        string $amount,
        TradableInterface $tradableDecimals,
        bool $isValid,
        string $message
    ): void {
        $validator = new TradableDigitsValidator($amount, $tradableDecimals);

        $this->assertEquals($isValid, $validator->validate());
        $this->assertEquals($message, $validator->getMessage());
    }

    public function validateProvider(): array
    {
        return [
            "valid crypto case" => [
                "amount" => "1.0000",
                "tradable" => $this->mockTradable(4, "crypto"),
                "isValid" => true,
                "message" => "Allowed digits is 4",
            ],
            "valid crypto when digits are less" => [
                "amount" => "1.0000",
                "tradable" => $this->mockTradable(5, "crypto"),
                "isValid" => true,
                "message" => "Allowed digits is 5",
            ],
            "invalid crypto case" => [
                "amount" => "1.0000",
                "tradable" => $this->mockTradable(3, "crypto"),
                "isValid" => false,
                "message" => "Allowed digits is 3",
            ],
            "valid token case" => [
                "amount" => "1.0000",
                "tradable" => $this->mockTradable(4, "token"),
                "isValid" => true,
                "message" => "Allowed digits is 4",
            ],
            "valid token when digits are less" => [
                "amount" => "1.0000",
                "tradable" => $this->mockTradable(5, "token"),
                "isValid" => true,
                "message" => "Allowed digits is 4",
            ],
            "invalid token case" => [
                "amount" => "1.0000",
                "tradable" => $this->mockTradable(3, "token"),
                "isValid" => false,
                "message" => "Allowed digits is 3",
            ],
        ];
    }

    private function mockTradable(int $tradableDecimals, string $type): TradableInterface
    {
        if ("crypto" === $type) {
            $tradable = $this->createMock(Crypto::class);
            $tradable->expects($this->once())
                ->method('getShowSubunit')
                ->willReturn($tradableDecimals);
        } else {
            $tradable = $this->createMock(Token::class);
            $tradable->expects($this->any())
                ->method('getDecimals')
                ->willReturn($tradableDecimals);
        }

        return $tradable;
    }
}
