<?php declare(strict_types = 1);

namespace App\Tests\Validator\Constraints;

use App\Manager\TokenManagerInterface;
use App\Validator\Constraints\DisallowedWordValidator;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class DisallowedWordValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): DisallowedWordValidator
    {
        $tokenManager = $this->createMock(TokenManagerInterface::class);

        return new DisallowedWordValidator($tokenManager);
    }

    /** @dataProvider getValidWords */
    public function testValidWords(string $word): void
    {
        $this->validator->validate($word, $this->constraint);

        $this->assertNoViolation();
    }

    /** @dataProvider getInvalidWords */
    public function testInvalidWords(string $word): void
    {
        $this->validator->validate($word, $this->constraint);

        $this->buildViolation($this->constraint->message)
            ->assertRaised();
    }

    public function testNullIsValid(): void
    {
        $this->validator->validate(null, $this->constraint);

        $this->assertNoViolation();
    }

    public function testEmptyStringIsValid(): void
    {
        $this->validator->validate('', $this->constraint);

        $this->assertNoViolation();
    }

    public function getValidWords(): array
    {
        return [
            ['foo'],
            ['bar'],
            ['baz'],
            ['coinss'],
            ['tokenss'],
            ['TestToken'],
            ['TestCoin'],
        ];
    }


    public function getInvalidWords(): array
    {
        return [
            ['coin bar'],
            ['bar token'],
            ['foo coins bar'],
            ['coin'],
            ['coins'],
            ['token'],
            ['tokens'],
        ];
    }
}
