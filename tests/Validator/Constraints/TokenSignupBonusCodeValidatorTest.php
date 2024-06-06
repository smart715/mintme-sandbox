<?php declare(strict_types = 1);

namespace App\Tests\Validator\Constraints;

use App\Entity\TokenSignupBonusCode;
use App\Repository\TokenSignupBonusCodeRepository;
use App\Services\TranslatorService\Translator;
use App\Validator\Constraints\TokenSignupBonusCodeValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class TokenSignupBonusCodeValidatorTest extends ConstraintValidatorTestCase
{
    private const VALID_CODE_WITHOUT_PARTICIPANTS = '1';
    private const VALID_CODE_WITH_PARTICIPANTS = '2';
    protected function createValidator(): TokenSignupBonusCodeValidator
    {
        return new TokenSignupBonusCodeValidator(
            $this->mockRepository(),
            $this->mockTranslator(),
        );
    }

    public function testValidateInvalidConstraint(): void
    {
        $constraint = $this->createMock(Constraint::class);

        $this->expectException(UnexpectedTypeException::class);
        $this->validator->validate(123, $constraint);
    }

    public function testValidateEmptyString(): void
    {
        $constraint = $this->getConstraint();

        $this->validator->validate('', $constraint);
        $this->assertNoViolation();
    }

    public function testValidateCodeNotFound(): void
    {
        $constraint = $this->getConstraint();

        $this->validator->validate('not found code', $constraint);
        $this->assertNoViolation();
    }

    public function testValidateCodeWithParticipants(): void
    {
        $constraint = $this->getConstraint();

        $this->validator->validate(self::VALID_CODE_WITH_PARTICIPANTS, $constraint);
        $this->assertNoViolation();
    }

    public function testValidateCodeWithoutParticipants(): void
    {
        $constraint = $this->getConstraint();

        $this->validator->validate(self::VALID_CODE_WITHOUT_PARTICIPANTS, $constraint);
        $this->buildViolation($constraint->message)
            ->assertRaised();
    }

    private function mockRepository(): TokenSignupBonusCodeRepository
    {
        $repository = $this->createMock(TokenSignupBonusCodeRepository::class);
        $repository
            ->method('findByCode')
            ->willReturnCallback(function (string $code): ?TokenSignupBonusCode {
                if (self::VALID_CODE_WITHOUT_PARTICIPANTS === $code) {
                    return $this->mockCode(0);
                }

                if (self::VALID_CODE_WITH_PARTICIPANTS === $code) {
                    return $this->mockCode(1);
                }

                return null;
            });

        return $repository;
    }

    private function mockCode(int $participantsAmount): TokenSignupBonusCode
    {
        $code = $this->createMock(TokenSignupBonusCode::class);
        $code
            ->method('getParticipants')
            ->willReturn($participantsAmount);

        return $code;
    }
    private function mockTranslator(): Translator
    {
        $translator = $this->createMock(Translator::class);
        $translator
            ->method('trans')
            ->willReturnCallback(function (string $key): string {
                return $key;
            });

        return $translator;
    }

    private function getConstraint(): \App\Validator\Constraints\TokenSignupBonusCode
    {
        return new \App\Validator\Constraints\TokenSignupBonusCode();
    }
}
