<?php declare(strict_types = 1);

namespace App\Tests\Validator\Constraints;

use App\Entity\Token\Token;
use App\Manager\BlacklistManagerInterface;
use App\Manager\TokenManagerInterface;
use App\Validator\Constraints\IsNotBlacklisted;
use App\Validator\Constraints\IsNotBlacklistedValidator;
use libphonenumber\PhoneNumber;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class IsNotBlacklistedValidatorTest extends ConstraintValidatorTestCase
{
    public function createValidator(): IsNotBlacklistedValidator
    {
        return new IsNotBlacklistedValidator(
            $this->mockBlacklistManager(),
            $this->mockTokenManager()
        );
    }

    /** @dataProvider validateTestCases */
    public function testValidate(
        string $type,
        bool $isBlackListed,
        bool $isValid,
        ?bool $tokenExist = null,
        ?bool $isPhoneNumber = null
    ): void {
        $validator = new IsNotBlacklistedValidator(
            $this->mockBlacklistManager($type, $isBlackListed),
            $this->mockTokenManager($type, $isBlackListed, $tokenExist)
        );

        $constraint = new IsNotBlacklisted(["type" => $type]);

        $validator->initialize($this->context);
        $validator->validate($isPhoneNumber ? new PhoneNumber() : "test", $constraint);

        if ($isValid) {
            $this->assertNoViolation();
        } else {
            $this->buildViolation($constraint->message)
                ->assertRaised();
        }
    }


    public function validateTestCases(): array
    {
        return [
            "Valid if Email is not blacklisted" => [
                "type" => "email",
                "isBlackListed" => false,
                "isValid" => true,
            ],
            "Invalid if Email is blacklisted" => [
                "type" => "email",
                "isBlackListed" => true,
                "isValid" => false,
            ],
            "Invalid if type is one of token types and token is blacklisted" => [
                "type" => "token",
                "isBlackListed" => true,
                "isValid" => false,
            ],
            "Valid if type is one of token types and token is not blacklisted" => [
                "type" => "token",
                "isBlackListed" => false,
                "isValid" => true,
            ],
            "Valid if type is one of token types and token is not blacklisted and token exist" => [
                "type" => "token",
                "isBlackListed" => false,
                "isValid" => true,
                "tokenExist" => true,
            ],
            "Valid if type is one of token types and token is not blacklisted and token not exist" => [
                "type" => "token",
                "isBlackListed" => false,
                "isValid" => true,
                "tokenExist" => false,
            ],
            "Invalid if type is one of token types and token is blacklisted and token exist" => [
                "type" => "token",
                "isBlackListed" => true,
                "isValid" => true,
                "tokenExist" => true,
            ],
            "Valid if Phone number is not blacklisted" => [
                "type" => "phone",
                "isBlackListed" => false,
                "isValid" => true,
                "tokenExist" => false,
                "isPhoneNumber" => true,
            ],
            "Invalid if Phone number is blacklisted" => [
                "type" => "phone",
                "isBlackListed" => true,
                "isValid" => false,
                "tokenExist" => false,
                "isPhoneNumber" => true,
            ],
        ];
    }

    public function testDoesntValidateIfValueIsNull(): void
    {
        $validator = new IsNotBlacklistedValidator(
            $this->mockBlacklistManager(),
            $this->mockTokenManager()
        );

        $constraint = new IsNotBlacklisted();

        try {
            $validator->initialize($this->context);
            $validator->validate(null, $constraint);
        } catch (UnexpectedTypeException $e) {
            $this->fail();
        }

        $this->assertNoViolation();
    }


    public function testInvalidType(): void
    {
        $validator = new IsNotBlacklistedValidator(
            $this->mockBlacklistManager(),
            $this->mockTokenManager()
        );

        $constraint = new IsNotBlacklisted();

        $validator->initialize($this->context);

        $this->expectException(UnexpectedTypeException::class);

        $validator->validate([], $constraint);
    }

    private function mockTokenManager(
        ?string $type = null,
        bool $isBlackListed = false,
        ?bool $tokenExist = null
    ): TokenManagerInterface {
        $manager = $this->createMock(TokenManagerInterface::class);
        $manager->expects("token" === $type && $isBlackListed ? $this->once() : $this->never())
            ->method('findByName')
            ->willReturn($tokenExist ? $this->mockToken() : null);

        return $manager;
    }

    private function mockBlacklistManager(?string $type = null, ?bool $isBlackListed = false): BlacklistManagerInterface
    {
        $manager = $this->createMock(BlacklistManagerInterface::class);
        $manager->expects("email" === $type ? $this->once() : $this->never())
            ->method('isBlacklistedEmail')
            ->willReturn($isBlackListed);

        $manager->expects("token" === $type ? $this->once() : $this->never())
            ->method('isBlacklistedToken')
            ->willReturn($isBlackListed);

        $manager->expects("phone" === $type ? $this->once() : $this->never())
            ->method('isBlackListedNumber')
            ->willReturn($isBlackListed);

        return $manager;
    }

    private function mockToken(): Token
    {
        return $this->createMock(Token::class);
    }
}
