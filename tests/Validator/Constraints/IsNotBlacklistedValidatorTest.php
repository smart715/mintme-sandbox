<?php declare(strict_types = 1);

namespace App\Tests\Validator\Constraints;

use App\Manager\BlacklistManagerInterface;
use App\Validator\Constraints\IsNotBlacklisted;
use App\Validator\Constraints\IsNotBlacklistedValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class IsNotBlacklistedValidatorTest extends TestCase
{
    public function testValidate(): void
    {
//        $bm = $this->createMock(BlacklistManagerInterface::class);
//        $bm->method('isBlacklisted')->willReturn(true);
//
//        $context = $this->createMock(ExecutionContextInterface::class);
//        $context->expects($this->once())->method('buildViolation')->willReturn(
//            $this->createMock(ConstraintViolationBuilderInterface::class)
//        );
//
//        $constraint = $this->createMock(IsNotBlacklisted::class);
//        $constraint->message = 'test';
//        $constraint->type = 'foo';
//        $constraint->caseSensetive = true;
//
//        $validator = new IsNotBlacklistedValidator($bm);
//        $validator->initialize($context);
//        $validator->validate('123', $constraint);
//
//        $this->expectException(UnexpectedTypeException::class);
//        $validator->validate(123, $constraint);
    }
}
