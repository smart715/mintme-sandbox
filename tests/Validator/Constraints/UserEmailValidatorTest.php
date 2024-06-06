<?php declare(strict_types = 1);

namespace App\Tests\Validator\Constraints;

use App\Entity\User;
use App\Manager\UserManagerInterface;
use App\Validator\Constraints\UserEmail;
use App\Validator\Constraints\UserEmailValidator;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class UserEmailValidatorTest extends ConstraintValidatorTestCase
{
    public function createValidator(): UserEmailValidator
    {
        return new UserEmailValidator(
            $this->mockUserManager(),
            $this->mockTokenStorage()
        );
    }

    /** @dataProvider getTestCases  */
    public function testUserEmails(string $email, ?User $user, ?User $userWithTheEmail, bool $isValid): void
    {
        $validator = new UserEmailValidator(
            $this->mockUserManager($userWithTheEmail),
            $this->mockTokenStorage($user)
        );
        $constraint = new UserEmail();

        $validator->initialize($this->context);
        $validator->validate($email, $constraint);

        if ($isValid) {
            $this->assertNoViolation();
        } else {
            $this->buildViolation($constraint->message)
                ->assertRaised();
        }
    }

    public function getTestCases(): array
    {
        return [
            "Invalid if user email exists with a different user" => [
                $email = "foo@bar.com",
                $this->mockUser(),
                $this->mockUser($email),
                false,
            ],
            "Invalid if user email exists with the same user" => [
                $email = "foo@bar.com",
                $user = $this->mockUser($email),
                $user,
                false,
            ],
            "Valid if email doesn't exists" => [
                "foo@bar.com",
                $this->mockUser(),
                null,
                true,
            ],
        ];
    }

    private function mockTokenStorage(?User $user = null): TokenStorageInterface
    {
        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage->method('getToken')->willReturn($this->mockToken($user));

        return $tokenStorage;
    }

    private function mockToken(?User $user): TokenInterface
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        return $token;
    }

    private function mockUser(?string $email = null): User
    {
        $user = $this->createMock(User::class);
        $user->method('getEmail')->willReturn($email);

        return $user;
    }

    private function mockUserManager(?User $user = null): UserManagerInterface
    {
        $userManager = $this->createMock(UserManagerInterface::class);
        $userManager->method('findUserByEmail')
            ->willReturn($user);

        return $userManager;
    }
}
