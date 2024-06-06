<?php declare(strict_types = 1);

namespace App\Tests\Validator\Constraints;

use App\Entity\PhoneNumber;
use App\Entity\Profile;
use App\Entity\User;
use App\Manager\PhoneNumberManagerInterface;
use App\Services\TranslatorService\TranslatorInterface;
use App\Validator\Constraints\EditPhoneNumberValidator;
use libphonenumber\PhoneNumberUtil;
use Safe\DateTimeImmutable;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class EditPhoneNumberValidatorTest extends ConstraintValidatorTestCase
{
    private const INTERVAL = '1D';
    private const ATTEMPTS = 1;

    protected function createValidator(): EditPhoneNumberValidator
    {
        return new EditPhoneNumberValidator(
            $this->createMock(ParameterBagInterface::class),
            $this->createMock(TranslatorInterface::class),
            $this->mockTokenStorage(),
            $this->createMock(PhoneNumberUtil::class),
            $this->createMock(PhoneNumberManagerInterface::class),
        );
    }

    /** @dataProvider provider */
    public function testValidate(
        ?\libphonenumber\PhoneNumber $value,
        ?PhoneNumber $oldPhoneNumber,
        ?PhoneNumber $newPhoneEntity,
        bool $violation,
        ?string $oldPhoneFormat = null,
        ?string $newPhoneFormat = null
    ): void {
        $this->validator = new EditPhoneNumberValidator(
            $this->mockParameterBag(),
            $this->mockTranslator(),
            $this->mockTokenStorage($oldPhoneNumber),
            $this->mockPhoneNumberUtil($oldPhoneFormat, $newPhoneFormat),
            $this->mockPhoneNumberManager($newPhoneEntity)
        );

        $this->validator->initialize($this->context);

        $this->validator->validate($value, $this->constraint);

        if ($violation) {
            $this->buildViolation($this->constraint->message)
                ->setParameter('{{message}}', 'test')
                ->assertRaised();
        } else {
            $this->assertNoViolation();
        }
    }

    public function provider(): array
    {
        return [
            "Valid" => [
                "value" => $this->mockLibphonenumber(),
                "oldPhoneNumber" => $this->mockPhoneNumber((new DateTimeImmutable('-10 day')), self::ATTEMPTS - 2),
                "newPhoneNumber" => null,
                "violation" => false,
                "oldPhoneFormat" => '+33123456789',
                "newPhoneFormat" => '+33123456782',
            ],
            "Invalid if edit with no value and old number exist" => [
                "value" => null,
                "oldPhoneNumber" => $this->mockPhoneNumber(),
                "newPhoneNumber" => $this->mockPhoneNumber(),
                "violation" => true,
            ],
            "Invalid if used a in use Phone number and it wasn't used by the same user" => [
                "value" => $this->mockLibphonenumber(),
                "oldPhoneNumber" => $this->mockPhoneNumber(),
                "newPhoneNumber" => $this->mockPhoneNumber(),
                "violation" => true,
            ],
            "Valid if no old phone number" => [
                "value" => $this->mockLibphonenumber(),
                "oldPhoneNumber" => null,
                "newPhoneNumber" => null,
                "violation" => false,
            ],
            "Valid if no edit date set on old phone number" => [
                "value" => $this->mockLibphonenumber(),
                "oldPhoneNumber" => $this->mockPhoneNumber(null),
                "newPhoneNumber" => null,
                "violation" => false,
            ],
            "Valid if same phone number" => [
                "value" => $this->mockLibphonenumber(),
                "oldPhoneNumber" => $this->mockPhoneNumber(new DateTimeImmutable()),
                "newPhoneNumber" => null,
                "violation" => false,
                "oldPhoneFormat" => '+33123456789',
                "newPhoneFormat" => '+33123456789',
            ],
            "Invalid if possible edit date is bigger than now" => [
                "value" => $this->mockLibphonenumber(),
                "oldPhoneNumber" => $this->mockPhoneNumber(new DateTimeImmutable()),
                "newPhoneNumber" => null,
                "violation" => true,
                "oldPhoneFormat" => '+33123456789',
                "newPhoneFormat" => '+33123456782',
            ],
            "Invalid if edit attempts is more than maximum" => [
                "value" => $this->mockLibphonenumber(),
                "oldPhoneNumber" => $this->mockPhoneNumber(new DateTimeImmutable(), self::ATTEMPTS + 1),
                "newPhoneNumber" => null,
                "violation" => true,
                "oldPhoneFormat" => '+33123456789',
                "newPhoneFormat" => '+33123456782',
            ],

        ];
    }

    private function mockParameterBag(): ParameterBagInterface
    {
        $bag = $this->createMock(ParameterBagInterface::class);
        $bag->method('get')->willReturn(
            ['interval' => self::INTERVAL, 'attempts' => self::ATTEMPTS]
        );

        return $bag;
    }

    private function mockTranslator(): TranslatorInterface
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturn('test');

        return $translator;
    }

    private function mockTokenStorage(?PhoneNumber $oldPhoneNumber = null): TokenStorageInterface
    {
        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage->method('getToken')->willReturn($this->mockToken($oldPhoneNumber));

        return $tokenStorage;
    }

    private function mockPhoneNumberUtil(?string $oldPhoneFormat, ?string $newPhoneFormat): PhoneNumberUtil
    {
        $numberUtil = $this->createMock(PhoneNumberUtil::class);
        $numberUtil->expects($oldPhoneFormat ? $this->at(0) : $this->never())
            ->method('format')
            ->willReturn($oldPhoneFormat);

        $numberUtil->expects($newPhoneFormat ? $this->at(1) : $this->never())
            ->method('format')
            ->willReturn($newPhoneFormat);

        return $numberUtil;
    }

    private function mockPhoneNumberManager(?PhoneNumber $newPhoneEntity): PhoneNumberManagerInterface
    {
        $phoneNumberManager = $this->createMock(PhoneNumberManagerInterface::class);
        $phoneNumberManager->method('findVerifiedPhoneNumber')
            ->willReturn($newPhoneEntity);

        return $phoneNumberManager;
    }

    private function mockToken(?PhoneNumber $oldPhoneNumber): TokenInterface
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($this->mockUser($oldPhoneNumber));

        return $token;
    }

    private function mockUser(?PhoneNumber $oldPhoneNumber): User
    {
        $user = $this->createMock(User::class);
        $user->method('getProfile')
            ->willReturn($this->mockProfile($oldPhoneNumber));

        return $user;
    }


    private function mockProfile(?PhoneNumber $oldPhoneNumber): Profile
    {
        $profile = $this->createMock(Profile::class);
        $profile->method('getPhoneNumber')
            ->willReturn($oldPhoneNumber);

        return $profile;
    }

    private function mockPhoneNumber(?DateTimeImmutable $oldPhoneEditDate = null, ?int $editAttempts = 0): PhoneNumber
    {
        $phoneNumber = $this->createMock(PhoneNumber::class);
        $phoneNumber->method('getEditDate')->willReturn($oldPhoneEditDate);
        $phoneNumber->method('getEditAttempts')->willReturn($editAttempts);

        return $phoneNumber;
    }

    private function mockLibphonenumber(): \libphonenumber\PhoneNumber
    {
        return $this->createMock(\libphonenumber\PhoneNumber::class);
    }
}
