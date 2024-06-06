<?php declare(strict_types = 1);

namespace App\Tests\Validator\Constraints;

use App\Validator\Constraints\ApiKeyAuthenticator;
use App\Validator\Constraints\ApiKeyAuthenticatorValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class ApiKeyAuthenticatorValidatorTest extends ConstraintValidatorTestCase
{
    private const LENGTH = 64;
    protected function createValidator(): ApiKeyAuthenticatorValidator
    {
        return new ApiKeyAuthenticatorValidator();
    }

    public function testValidateUnexpectedConstraint(): void
    {
        $constraint = $this->createMock(Constraint::class);

        $this->expectException(UnexpectedTypeException::class);
        $this->validator->validate(123, $constraint);
    }

    public function testValidateNotString(): void
    {
        $constraint = $this->getConstraint();
        $this->validator->validate(123, $constraint);

        $this->buildViolation($constraint->message)
            ->setParameter('{{ reason }}', 'not string')
            ->assertRaised();
    }

    public function testValidateInvalidLength(): void
    {
        $constraint = $this->getConstraint();
        $this->validator->validate('123', $constraint);

        $this->buildViolation($constraint->message)
            ->setParameter('{{ reason }}', 'invalid length')
            ->assertRaised();
    }

    public function testValidateNullIsNotAllowed(): void
    {
        $constraint = $this->getConstraint();
        $this->validator->validate(null, $constraint);

        $this->buildViolation($constraint->message)
            ->setParameter('{{ reason }}', 'can not be null')
            ->assertRaised();
    }

    public function testValidateNullIsAllowed(): void
    {
        $constraint = $this->getConstraint(true);
        $this->validator->validate(null, $constraint);

        $this->assertNoViolation();
    }

    public function testValidateSuccess(): void
    {
        $key = str_repeat('a', self::LENGTH);
        $constraint = $this->getConstraint();
        $this->validator->validate($key, $constraint);

        $this->assertNoViolation();
    }

    private function getConstraint(bool $allowNull = false): ApiKeyAuthenticator
    {
        return new ApiKeyAuthenticator([
            'length' => self::LENGTH,
            'allowNull' => $allowNull,
        ]);
    }
}
