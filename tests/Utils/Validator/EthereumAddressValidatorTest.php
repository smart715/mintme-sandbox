<?php declare(strict_types = 1);

namespace App\Tests\Utils\Validator;

use App\Utils\Validator\EthereumAddressValidator;
use PHPUnit\Framework\TestCase;

class EthereumAddressValidatorTest extends TestCase
{
    /** @dataProvider validateProvider */
    public function testValid(
        string $address,
        bool $isValid
    ): void {
        $validator = new EthereumAddressValidator($address);

        $this->assertEquals($isValid, $validator->validate());
        $this->assertEquals('Invalid ethereum address', $validator->getMessage());
    }

    public function validateProvider(): array
    {
        return [
            "valid ETH address" => [
                "address" => "0x5aAeb6053F3E94C9b9A09f33669435E7Ef1BeAed",
                "isValid" => true,
            ],
            "invalid ETH address with more than 42 chars" => [
                "address" => "0x5aAeb6053F3E94C9b9A09f33669435E7Ef1BeAed1",
                "isValid" => false,
            ],
            "invalid ETH address with less than 42 chars" => [
                "address" => "0x5aAeb6053F3E94C9b9A09f33669435E7Ef1BeA",
                "isValid" => false,
            ],
            "invalid ETH address without 0x at the start" => [
                "address" => "5aAeb6053F3E94C9b9A09f33669435E7Ef1BeAed",
                "isValid" => false,
            ],
        ];
    }
}
