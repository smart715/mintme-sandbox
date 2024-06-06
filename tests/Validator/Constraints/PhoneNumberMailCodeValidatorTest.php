<?php declare(strict_types = 1);

namespace App\Tests\Validator\Constraints;

use App\Entity\PhoneNumber;
use App\Entity\Profile;
use App\Entity\User;
use App\Entity\ValidationCode\ValidationCodeInterface;
use App\Services\TranslatorService\TranslatorInterface;
use App\Validator\Constraints\PhoneNumberMailCodeValidator;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class PhoneNumberMailCodeValidatorTest extends ConstraintValidatorTestCase
{
    private const VALID_CODE = '123456';
    private const VIOLATION_MESSAGE = 'violation message';
    protected function createValidator(): PhoneNumberMailCodeValidator
    {
        return new PhoneNumberMailCodeValidator($this->mockTranslator(), $this->mockTokenStorageInterface());
    }

    public function testValidate(): void
    {
        $this->validator->validate(self::VALID_CODE, $this->constraint);

        $this->assertNoViolation();
    }

    public function testValidateWithInvalidCode(): void
    {
        $this->validator->validate('invalid code', $this->constraint);

        $this->buildViolation($this->constraint->message)
            ->setParameter('{{message}}', self::VIOLATION_MESSAGE)
            ->assertRaised();
    }

    private function mockTranslator(): TranslatorInterface
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturn(self::VIOLATION_MESSAGE);

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
        $user->method('getProfile')->willReturn($this->mockProfile());

        return $user;
    }

    private function mockProfile(): Profile
    {
        $profile = $this->createMock(Profile::class);
        $profile->method('getPhoneNumber')->willReturn($this->mockPhoneNumber());

        return $profile;
    }

    private function mockPhoneNumber(): PhoneNumber
    {
        $phoneNumber = $this->createMock(PhoneNumber::class);
        $phoneNumber->method('getMailCode')->willReturn($this->mockMailCode());

        return $phoneNumber;
    }

    private function mockMailCode(): ValidationCodeInterface
    {
        $smsCode = $this->createMock(ValidationCodeInterface::class);
        $smsCode->method('getCode')->willReturn(self::VALID_CODE);

        return $smsCode;
    }
}
