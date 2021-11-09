<?php declare(strict_types = 1);

namespace App\Tests\Validator\Constraints;

use App\Validator\Constraints\UserEmailSymbols;
use App\Validator\Constraints\UserEmailSymbolsValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class UserEmailSymbolsValidatorTest extends TestCase
{
    public function testValidate(): void
    {
        $email = 'test@foo.baz';
        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects($this->once())->method('buildViolation')->willReturn(
            $this->createMock(ConstraintViolationBuilderInterface::class)
        );

        $constraint = $this->createMock(UserEmailSymbols::class);
        $constraint->message = 'test';

        $validator = new UserEmailSymbolsValidator();
        $validator->initialize($context);

        $validator->validate($email, $constraint);
        $validator->validate('uniqueemail+tag@mail.com', $constraint);
        $validator->validate(null, $constraint);
    }
}
