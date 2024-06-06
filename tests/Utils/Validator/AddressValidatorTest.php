<?php declare(strict_types = 1);

namespace App\Tests\Utils\Validator;

use App\Entity\Crypto;
use App\Utils\Validator\AddressValidator;
use PHPUnit\Framework\TestCase;

class AddressValidatorTest extends TestCase
{
    /** @dataProvider validateProvider */
    public function testValid(
        string $address,
        string $symbol,
        bool $isValid
    ): void {
        
        $validator = new AddressValidator($this->mockCryptoNetwork($symbol), $address);
        $this->assertEquals($isValid, $validator->validate());
    }

    public function validateProvider(): array
    {
        return [
            "valid BTC address" => [
                "address" => "1A1zP1eP5QGefi2DMPTfTL5SLmv7DivfNa",
                "symbol" => "BTC",
                "isValid" => true,
            ],
            "valid ETH address" => [
                "address" => "0x7a9f3cd060ab180f36c17fe6bdf9974f577d77aa",
                "symbol" => "ETH",
                "isValid" => true,
            ],
            "invalid BTC address" => [
                "address" => "1A1zP1eP5QGefi",
                "symbol" => "BTC",
                "isValid" => false,
            ],
            "invalid ETH address" => [
                "address" => "0x7a9f3cd060ab180f36c17fe6bdf9974f577d77a",
                "symbol" => "ETH",
                "isValid" => false,
            ],
            // We're using Cronos and not Crypto.org chain for now
            "valid CRO address" => [
                "address" => "0x7a9f3cd060ab180f36c17fe6bdf9974f577d77aa",
                "symbol" => "CRO",
                "isValid" => true,
            ],
            "invalid CRO address" => [
                "address" => "tcro1lqzmd9lhjrmp5f054943f2gcu6zegve8gfrd28",
                "symbol" => "CRO",
                "isValid" => false,
            ],
        ];
    }
    
    private function mockCryptoNetwork(string $symbol = 'ETH'): Crypto
    {
        $tradable = $this->createMock(Crypto::class);
        $tradable->expects($this->exactly(2))->method('getSymbol')
            ->willReturn($symbol);
        
        return $tradable;
    }
}
