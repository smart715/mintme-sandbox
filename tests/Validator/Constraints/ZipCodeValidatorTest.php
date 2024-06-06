<?php declare(strict_types = 1);

namespace App\Tests\Validator\Constraints;

use App\Validator\Constraints\ZipCode;
use App\Validator\Constraints\ZipCodeValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;
use Symfony\Component\Validator\Exception\MissingOptionsException;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class ZipCodeValidatorTest extends TestCase
{
    protected ZipCodeValidator $validator;

    public function setUp(): void
    {
        $this->validator = new ZipCodeValidator();
    }

    /** @dataProvider validZipCodes */
    public function testValidate(?string $isoCode, string $value, bool $isValid = true, bool $throwsError = false): void
    {
        $context = $this->createMock(ExecutionContextInterface::class);
        $constraintViolationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $constraintViolationBuilder->expects($this->exactly($isValid ? 0 : 1))
            ->method('setParameter')->willReturn($constraintViolationBuilder);
        $context->expects($isValid ? $this->never() : $this->once())->method('buildViolation')
            ->willReturn($constraintViolationBuilder);

        $this->validator->initialize($context);
        $this->validator->validate(
            $value,
            new ZipCode(['iso' => $isoCode, 'getter' => ''])
        );
    }

    /**
     * @dataProvider
     */
    public function validZipCodes(): array
    {
        return [
            ['CH', '3007'],
            ['HK', '999077'],
            ['KE', '12345'],
            ['TEST', '12345'],
            ['RU', '153251'],
            ['NL', 'test', false],
            ['NL', '1234 AB'],
            ['PN', 'fAke 1ZZ', false],
            ['', 'fAke 1ZZ'],
            ['PN', ''],
        ];
    }

    public function testValidateWithConstraintDefinitionException(): void
    {
        $context = $this->createMock(ExecutionContextInterface::class);
        $constraintViolationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);

        $context->expects($this->never())->method('buildViolation')
            ->willReturn($constraintViolationBuilder);

        $context->method('getObject')->willReturn((new ZipCode(['iso' => null, 'getter' => ''])));

        $this->expectException(ConstraintDefinitionException::class);

        $this->validator->initialize($context);
        $this->validator->validate(
            'TEST',
            new ZipCode(['iso' => null, 'getter' => ''])
        );
    }

    public function testValidateWithMissingOptionsException(): void
    {
        $context = $this->createMock(ExecutionContextInterface::class);
        $constraintViolationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);

        $context->expects($this->never())->method('buildViolation')
            ->willReturn($constraintViolationBuilder);

        $this->expectException(MissingOptionsException::class);

        $this->validator->initialize($context);
        $this->validator->validate(
            'TEST',
            new ZipCode()
        );
    }

    public function testValidateWithInvalidConstraintType(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate(
            'TEST',
            $this->createMock(Constraint::class)
        );
    }
}
