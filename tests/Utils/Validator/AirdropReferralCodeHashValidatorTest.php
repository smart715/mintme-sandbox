<?php declare(strict_types = 1);

namespace App\Tests\Utils\Validator;

use App\Utils\Validator\AirdropReferralCodeHashValidator;
use PHPUnit\Framework\TestCase;

class AirdropReferralCodeHashValidatorTest extends TestCase
{
    /** @dataProvider validateDataProvider */
    public function testValidate(string $hash, bool $result, string $message): void
    {
        $validator = new AirdropReferralCodeHashValidator($hash);

        $this->assertEquals($result, $validator->validate());
        $this->assertEquals($message, $validator->getMessage());
    }

    public function validateDataProvider(): array
    {
        $onlyCharactersMessage = 'Hash must contain only characters in the url safe base64 alphabet';
        $validLengthMessage = 'Hash length must be more than 0 and less than or equal to 11';

        return [
            ['E', true, ''],
            ['EEEEEEEEEEE', true, ''],
            ['abcdefghijk', true, ''],
            ['lmnopqrstuv', true, ''],
            ['wxyz1234567', true, ''],
            ['890ABCDEFGH', true, ''],
            ['IJKLMNOPQRS', true, ''],
            ['TUVWXYZ-_', true, ''],
            ['+', false, $onlyCharactersMessage],
            ['/', false, $onlyCharactersMessage],
            ['!', false, $onlyCharactersMessage],
            ['@', false, $onlyCharactersMessage],
            ['#', false, $onlyCharactersMessage],
            ['$', false, $onlyCharactersMessage],
            ['%', false, $onlyCharactersMessage],
            ['^', false, $onlyCharactersMessage],
            ['&', false, $onlyCharactersMessage],
            ['*', false, $onlyCharactersMessage],
            ['=', false, $onlyCharactersMessage],
            ['~', false, $onlyCharactersMessage],
            ['(', false, $onlyCharactersMessage],
            [')', false, $onlyCharactersMessage],
            ['?', false, $onlyCharactersMessage],
            ['\\', false, $onlyCharactersMessage],
            ['', false, $validLengthMessage],
            ['AAAAAAAAAAAA', false, $validLengthMessage],
        ];
    }
}
