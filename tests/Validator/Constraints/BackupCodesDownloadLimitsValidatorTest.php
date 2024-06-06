<?php declare(strict_types = 1);

namespace App\Tests\Validator\Constraints;

use App\Config\UserLimitsConfig;
use App\Entity\User;
use App\Manager\TFACodesManagerInterface;
use App\Services\TranslatorService\Translator;
use App\Validator\Constraints\BackupCodesDownloadLimits;
use App\Validator\Constraints\BackupCodesDownloadLimitsValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class BackupCodesDownloadLimitsValidatorTest extends ConstraintValidatorTestCase
{
    private const MONTH_LIMIT = 10;
    private const USER_ID_LIMIT_REACHED = 1;
    private const USER_ID_LIMIT_NOT_REACHED = 2;
    protected function createValidator(): BackupCodesDownloadLimitsValidator
    {
        return new BackupCodesDownloadLimitsValidator(
            $this->mockTranslator(),
            $this->mockTFACodesManager(),
            $this->mockUserLimitsConfig()
        );
    }

    public function testValidateUnexpectedConstraint(): void
    {
        $constraint = $this->createMock(Constraint::class);

        $this->expectException(UnexpectedTypeException::class);
        $this->validator->validate($this->mockUser(self::USER_ID_LIMIT_NOT_REACHED), $constraint);
    }

    public function testValidateNotUserValue(): void
    {
        $constraint = $this->getConstraint();

        $this->expectException(UnexpectedTypeException::class);
        $this->validator->validate(123, $constraint);
    }

    public function testValidateLimitReached(): void
    {
        $constraint = $this->getConstraint();

        $this->validator->validate($this->mockUser(self::USER_ID_LIMIT_REACHED), $constraint);
        $this->buildViolation($constraint->message)
            ->setParameter('{{message}}', '2fa.backup_code.download.limit_month')
            ->assertRaised();
    }

    public function testValidateLimitNotReached(): void
    {
        $constraint = $this->getConstraint();

        $this->validator->validate($this->mockUser(self::USER_ID_LIMIT_NOT_REACHED), $constraint);
        $this->assertNoViolation();
    }

    private function mockTranslator(): Translator
    {
        $translator = $this->createMock(Translator::class);
        $translator
            ->method('trans')
            ->willReturnCallback(function ($key): string {
                return $key;
            });

        return $translator;
    }

    private function mockTFACodesManager(): TFACodesManagerInterface
    {
        $tfaCodesManager = $this->createMock(TFACodesManagerInterface::class);
        $tfaCodesManager
            ->method('isDownloadCodesLimitReached')
            ->willReturnCallback(function (User $user) {
                return self::USER_ID_LIMIT_REACHED === $user->getId();
            });

        return $tfaCodesManager;
    }

    private function mockUserLimitsConfig(): UserLimitsConfig
    {
        $config = $this->createMock(UserLimitsConfig::class);
        $config
            ->method('getMonthlyBackupCodesLimit')
            ->willReturn(self::MONTH_LIMIT);

        return $config;
    }

    private function mockUser(int $id): User
    {
        $user = $this->createMock(User::class);
        $user
            ->method('getId')
            ->willReturn($id);

        return $user;
    }

    private function getConstraint(): BackupCodesDownloadLimits
    {
        return new BackupCodesDownloadLimits();
    }
}
