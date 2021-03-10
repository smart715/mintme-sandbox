<?php declare(strict_types = 1);

namespace App\Tests\Validator\Constraints;

use App\Entity\Token\Token;
use App\Manager\BlacklistManagerInterface;
use App\Manager\TokenManagerInterface;
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
        $bm = $this->createMock(BlacklistManagerInterface::class);
        $bm->method('isBlacklisted')->willReturn(true);

        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects($this->once())->method('buildViolation')->willReturn(
            $this->createMock(ConstraintViolationBuilderInterface::class)
        );

        $constraint = $this->createMock(IsNotBlacklisted::class);
        $constraint->message = 'test';
        $constraint->type = 'foo';
        $constraint->caseSensetive = true;

        $validator = new IsNotBlacklistedValidator($bm, $this->mockTokenManager());
        $validator->initialize($context);
        $validator->validate('123', $constraint);

        $this->expectException(UnexpectedTypeException::class);
        $validator->validate(123, $constraint);
    }

    public function testValidateTokenBlacklisted(): void
    {
        $bm = $this->createMock(BlacklistManagerInterface::class);
        $bm->method('isBlacklisted')->willReturn(true);

        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects($this->once())->method('buildViolation')->willReturn(
            $this->createMock(ConstraintViolationBuilderInterface::class)
        );

        $constraint = $this->createMock(IsNotBlacklisted::class);
        $constraint->message = 'test';
        $constraint->type = 'token';
        $constraint->caseSensetive = true;

        $validator = new IsNotBlacklistedValidator(
            $bm,
            $this->mockTokenManager($this->createMock(Token::class))
        );
        $validator->initialize($context);
        $validator->validate('123', $constraint);
    }

    public function testValidateIgnoreTokenValidation(): void
    {
        $bm = $this->createMock(BlacklistManagerInterface::class);
        $bm->method('isBlacklisted')->willReturn(true);

        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects($this->never())->method('buildViolation')->willReturn(
            $this->createMock(ConstraintViolationBuilderInterface::class)
        );

        $constraint = $this->createMock(IsNotBlacklisted::class);
        $constraint->message = 'test';
        $constraint->type = 'token';
        $constraint->caseSensetive = true;

        $validator = new IsNotBlacklistedValidator(
            $bm,
            $this->mockTokenManager(null)
        );
        $validator->initialize($context);
        $validator->validate('123', $constraint);
    }

    private function mockTokenManager(?Token $token = null): TokenManagerInterface
    {
        $manager = $this->createMock(TokenManagerInterface::class);
        $manager->method('findByName')->willReturn($token);

        return $manager;
    }
}
