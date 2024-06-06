<?php declare(strict_types = 1);

namespace App\Tests\Validator\Constraints;

use App\Entity\Token\Token;
use App\Validator\Constraints\TokenDescription;
use App\Validator\Constraints\TokenDescriptionValidator;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class TokenDescriptionValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): TokenDescriptionValidator
    {
        return new TokenDescriptionValidator();
    }

    /** @dataProvider getValidValues */
    public function testValidInputs(string $value): void
    {
        $this->setObject($this->mockToken(true));
        $constraint = new TokenDescription([
            'min' => 2,
            'max' => 10,
        ]);
        $this->validator->validate($value, $constraint);
        $this->assertNoViolation();
    }

    /** @dataProvider getInvalidValues */
    public function testInvalidInputs(string $value): void
    {
        $this->setObject($this->mockToken(true));
        $constraint = new TokenDescription([
            'min' => 2,
            'max' => 10,
        ]);
        $this->validator->validate($value, $constraint);

        $this->buildViolation($constraint->message)
            ->setParameter('{{min}}', $constraint->min)
            ->setParameter('{{max}}', $constraint->max)
            ->assertRaised();
    }

    public function testTokenNotCreatedOnMintmeIsValid(): void
    {
        $this->setObject($this->mockToken(false));

        $constraint = new TokenDescription([
            'min' => 2,
            'max' => 10,
        ]);
        $this->validator->validate("Lorem ipsum dolor sit amet", $constraint);
        $this->assertNoViolation();
    }

    public function getValidValues(): array
    {
        return [
            ["foo bar"],
            ["foo bar b"],
            ["fo"],
        ];
    }

    public function getInvalidValues(): array
    {
        return [
            ["foo bar baz"],
            ["foo bar baz b"],
            ["f"],
        ];
    }

    private function mockToken(bool $isCreatedOnMintmeSite): Token
    {
        $token = $this->createMock(Token::class);
        $token->method('isCreatedOnMintmeSite')->willReturn($isCreatedOnMintmeSite);

        return $token;
    }
}
