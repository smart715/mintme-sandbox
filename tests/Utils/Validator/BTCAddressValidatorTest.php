<?php declare(strict_types = 1);

namespace App\Tests\Utils\Validator;

use App\Utils\Validator\BTCAddressValidator;
use PHPUnit\Framework\TestCase;

class BTCAddressValidatorTest extends TestCase
{
    /** @dataProvider validateProvider */
    public function testValid(
        string $address,
        bool $isValid
    ): void {
        $validator = new BTCAddressValidator($address);

        $this->assertEquals($isValid, $validator->validate());
        $this->assertEquals('Invalid BTC address', $validator->getMessage());
    }

    public function validateProvider(): array
    {
        return [
            "valid BTC address less than 36 more than 24" => [
                "address" => "1A1zP1eP5QGefi2DMPTfTL5SLmv7DivfNa",
                "isValid" => true,
            ],
            "invalid BTC address less than 25" => [
                "address" => "1A1zP1eP5QGefi",
                "isValid" => false,
            ],
            "valid BTC address more than 36" => [
                "address" => "1A1zP1eP5QGefi2DMPTfTL5SLmv7DivfNsa2",
                "isValid" => true,
            ],
            "valid BTC address (specific #9139)" => [
                "address" => "bc1q5prwspze9j6wf3xj72w26cz9dte2jcjaxa9ug4",
                "isValid" => true,
            ],
            "invalid BTC address more than 62" => [
                "address" => "bc1q5prwspze9j6wf3xj72w26cz9dte2jcjaxa9ug4f3xj72w26cz9dte2jcjaxa9ug4",
                "isValid" => false,
            ],
        ];
    }
}
