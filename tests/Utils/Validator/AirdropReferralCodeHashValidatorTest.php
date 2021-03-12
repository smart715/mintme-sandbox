<?php declare(strict_types = 1);

namespace App\Tests\Utils\Validator;

use App\Utils\Validator\AirdropReferralCodeHashValidator;
use PHPUnit\Framework\TestCase;

class AirdropReferralCodeHashValidatorTest extends TestCase
{
    /** @dataProvider validateDataProvider */
    public function testValidate(string $hash, bool $result): void
    {
        $v = new AirdropReferralCodeHashValidator($hash);

        $this->assertEquals($result, $v->validate());
    }

    public function validateDataProvider(): array
    {
        return [
            ['+', false],
            ['/', false],
            ['E', true],
            ['AAAAAAAAAAAA', false],
            ['EEEEEEEEEEE', true],
            ['abcdefghijk', true],
            ['lmnopqrstuv', true],
            ['wxyz1234567', true],
            ['890ABCDEFGH', true],
            ['IJKLMNOPQRS', true],
            ['TUVWXYZ-_', true],
            ['!', false],
            ['@', false],
            ['#', false],
            ['$', false],
            ['%', false],
            ['^', false],
            ['&', false],
            ['*', false],
            ['=', false],
            ['\\', false],
            ['~', false],
            ['(', false],
            [')', false],
            ['?', false],
        ];
    }
}
