<?php declare(strict_types = 1);

namespace App\Tests\Utils\Validator;

use App\Utils\Validator\CroAddressValidator;
use PHPUnit\Framework\TestCase;

/*
 * This implementation is for Crypto.org chain, which we won't support due to Cronos chain
 * This code is unused but kept in case of future need
 *
 * Cronos chain is ethereum compatible, so it goes throught our ethereum based classes/logic
 */
class CROAddressValidatorTest extends TestCase
{
    protected function setUp(): void
    {
        // Skipped, not used due to Cronos chain
        $this->markTestSkipped();
    }

    /** @dataProvider validateTestnetProvider */
    public function testValidTestnet(
        string $address,
        bool $isValid
    ): void {
        $validator = new CroAddressValidator($address, 'dev');
        $this->assertEquals($isValid, $validator->validate());
    }
    
    public function validateTestnetProvider(): array
    {
        return [
            "valid CRO address" => [
                "address" => "tcro1lqzmd9lhjrmp5f054943f2gcu6zegve8gfrd28",
                "isValid" => true,
            ],
            "invalid CRO address with more than 43 chars" => [
                "address" => "tcro1lqzmd9lhjrmp5f054943f2gcu6zegve8gfrd289",
                "isValid" => false,
            ],
            "invalid CRO address with less than 43 chars" => [
                "address" => "tcro1lqzmd9lhjrmp5f054943f2gcu6zegve8gfrd2",
                "isValid" => false,
            ],
            "invalid CRO address without tcro at the start" => [
                "address" => "cro1lqzmd9lhjrmp5f054943f2gcu6zegve8gfrd28",
                "isValid" => false,
            ],
        ];
    }
    
    /** @dataProvider validateMainnetProvider */
    public function testValidMainnet(
        string $address,
        bool $isValid
    ): void {
        $validator = new CroAddressValidator($address, 'prod');
        $this->assertEquals($isValid, $validator->validate());
    }
    
    public function validateMainnetProvider(): array
    {
        return [
        "valid CRO address" => [
            "address" => "cro1lqzmd9lhjrmp5f054943f2gcu6zegve8gfrd28",
            "isValid" => true,
        ],
        "invalid CRO address with more than 42 chars" => [
            "address" => "cro1lqzmd9lhjrmp5f054943f2gcu6zegve8gfrd289",
            "isValid" => false,
        ],
        "invalid CRO address with less than 42 chars" => [
            "address" => "cro1lqzmd9lhjrmp5f054943f2gcu6zegve8gfrd2",
            "isValid" => false,
        ],
        "invalid CRO address without cro at the start" => [
            "address" => "1lqzmd9lhjrmp5f054943f2gcu6zegve8gfrd28",
            "isValid" => false,
        ],
        ];
    }
}
