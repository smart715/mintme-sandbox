<?php declare(strict_types = 1);

namespace App\Tests\Validator\Constraints;

use App\Entity\ValidationCode\ValidationCode;
use App\Services\TranslatorService\TranslatorInterface;
use App\Validator\Constraints\ValidationCodeLimits;
use App\Validator\Constraints\ValidationCodeLimitsValidator;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\Rule\InvokedCount;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class ValidationCodeLimitsValidatorTest extends ConstraintValidatorTestCase
{
    private const DAILY_LIMIT = 10;
    private const WEEKLY_LIMIT = 100;
    private const MONTHLY_LIMIT = 1000;

    protected function createValidator(): ValidationCodeLimitsValidator
    {
        return new ValidationCodeLimitsValidator(
            $this->createMock(TranslatorInterface::class),
            $this->createMock(EntityManagerInterface::class),
        );
    }

    public function testValidateWithWrongConstraint(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate('', $this->createMock(Constraint::class));
    }

    public function testValidateWithNotPhoneCode(): void
    {
        $this->validator->validate('', $this->getConstraint());
        $this->assertNoViolation();
    }

    /** @dataProvider provider */
    public function testValidate(
        InvokedCount $entityManagerInvokedCount,
        bool $isToday,
        int $dailyAttempts,
        int $weeklyAttempts,
        int $monthlyAttempts,
        bool $isValid
    ): void {
        $constraint = $this->getConstraint();
        $phoneCode = $this->mockPhoneCode(
            $isToday,
            $dailyAttempts,
            $weeklyAttempts,
            $monthlyAttempts
        );

        $this->validator = new ValidationCodeLimitsValidator(
            $this->mockTranslator(),
            $this->mockEntityManager($entityManagerInvokedCount)
        );

        $this->validator->initialize($this->context);
        $this->validator->validate($phoneCode, $constraint);

        if ($isValid) {
            $this->assertNoViolation();
        } else {
            $this->buildViolation($constraint->message)
                ->setParameter('{{message}}', 'test')
                ->assertRaised();
        }
    }

    public function provider(): array
    {
        return [
            'Valid Attempts on different days' => [
                'entityManagerInvocation' => $this->once(),
                'isToday' => false,
                'dailyAttempts' => 0,
                'weeklyAttempts' => 0,
                'monthlyAttempts' => 0,
                'valid' => true,
            ],
            'Valid Attempts on same day' => [
                'entityManagerInvocation' => $this->never(),
                'isToday' => true,
                'dailyAttempts' => 0,
                'weeklyAttempts' => 0,
                'monthlyAttempts' => 0,
                'valid' => true,
            ],
            "Valid with no phone number" => [
                'entityManagerInvocation' => $this->never(),
                'isToday' => true,
                'dailyAttempts' => 0,
                'weeklyAttempts' => 0,
                'monthlyAttempts' => 0,
                'valid' => true,
            ],
            'Invalid Attempts with more daily attempts than allowed' => [
                'entityManagerInvocation' => $this->never(),
                'isToday' => true,
                'dailyAttempts' => self::DAILY_LIMIT + 1,
                'weeklyAttempts' => 1,
                'monthlyAttempts' => 1,
                'valid' => false,
            ],
            'Invalid Attempts with more weekly attempts than allowed' => [
                'entityManagerInvocation' => $this->never(),
                'isToday' => true,
                'dailyAttempts' => 1,
                'weeklyAttempts' => self::WEEKLY_LIMIT + 1,
                'monthlyAttempts' => 1,
                'valid' => false,
            ],
            'Invalid Attempts with more monthly attempts than allowed' => [
                'entityManagerInvocation' => $this->never(),
                'isToday' => true,
                'dailyAttempts' => 1,
                'weeklyAttempts' => 1,
                'monthlyAttempts' => self::MONTHLY_LIMIT + 1,
                'valid' => false,
            ],
        ];
    }

    private function mockTranslator(): TranslatorInterface
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects($this->any())
            ->method('trans')->willReturn('test');

        return $translator;
    }

    private function mockEntityManager(InvokedCount $count): EntityManagerInterface
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($count)->method('persist');

        return $entityManager;
    }


    private function mockPhoneCode(
        bool $isToday,
        int $dailyAttempts,
        int $weeklyAttempts,
        int $monthlyAttempts
    ): ValidationCode {
        $phoneCode = $this->createMock(ValidationCode::class);
        $phoneCode->method('getAttemptsDate')->willReturn(
            $isToday ? new \DateTimeImmutable() : new \DateTimeImmutable('-100000 day')
        );
        $phoneCode->method('getDailyAttempts')->willReturn($dailyAttempts);
        $phoneCode->method('getWeeklyAttempts')->willReturn($weeklyAttempts);
        $phoneCode->method('getMonthlyAttempts')->willReturn($monthlyAttempts);

        return $phoneCode;
    }

    private function getConstraint(): ValidationCodeLimits
    {
        return new ValidationCodeLimits([
            'dailyLimit' => self::DAILY_LIMIT,
            'weeklyLimit' => self::WEEKLY_LIMIT,
            'monthlyLimit' => self::MONTHLY_LIMIT,
        ]);
    }
}
