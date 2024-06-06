<?php declare(strict_types = 1);

namespace App\Tests\Validator\Constraints;

use App\Entity\GoogleAuthenticatorEntry;
use App\Entity\User;
use App\Entity\ValidationCode\BackupValidationCode;
use App\Manager\TwoFactorManager;
use App\Validator\Constraints\TFASmsCodeValidator;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class TFASmsCodeValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): TFASmsCodeValidator
    {
        return new TFASmsCodeValidator(
            $this->mockTokenStorageInterface(),
            $this->mockTwoFactorManager(),
            $this->mockTranslator()
        );
    }

    public function testValidate(): void
    {
        $this->validator->validate('123456', $this->constraint);

        $this->assertNoViolation();
    }

    public function testValidateWithInvalidCode(): void
    {
        $this->validator->validate('12345', $this->constraint);

        $this->buildViolation($this->constraint->message)
            ->setParameter('{{message}}', 'test')
            ->assertRaised();
    }

    private function mockTranslator(): TranslatorInterface
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturn('test');

        return $translator;
    }

    private function mockTokenStorageInterface(): TokenStorageInterface
    {
        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage->method('getToken')->willReturn($this->mockToken());

        return $tokenStorage;
    }

    private function mockToken(): TokenInterface
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($this->mockUser());

        return $token;
    }

    private function mockUser(): User
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(1);

        return $user;
    }
    
    private function mockGoogleAuthEntry(): GoogleAuthenticatorEntry
    {
        $googleAuthEntry = $this->createMock(GoogleAuthenticatorEntry::class);
        $googleAuthEntry->method('getSMSCode')->willReturn($this->mockSMSCode());

        return $googleAuthEntry;
    }

    private function mockTwoFactorManager(): TwoFactorManager
    {
        $twoFactorManager = $this->createMock(TwoFactorManager::class);
        $twoFactorManager
            ->method('getGoogleAuthEntry')
            ->willReturn($this->mockGoogleAuthEntry());

        return $twoFactorManager;
    }

    private function mockSMSCode(): BackupValidationCode
    {
        $SMSCode = $this->createMock(BackupValidationCode::class);
        $SMSCode->method('getCode')->willReturn('123456');

        return $SMSCode;
    }
}
