<?php declare(strict_types = 1);

namespace App\Tests\Validator\Constraints;

use App\Entity\Profile;
use App\Entity\User;
use App\Manager\ProfileManagerInterface;
use App\Validator\Constraints\UniqueNickname;
use App\Validator\Constraints\UniqueNicknameValidator;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class UniqueNicknameValidatorTest extends ConstraintValidatorTestCase
{
    public function createValidator(): UniqueNicknameValidator
    {
        return new UniqueNicknameValidator(
            $this->mockProfileManager(),
            $this->mockTokenStorage()
        );
    }

    /** @dataProvider getNicknames  */
    public function testNicknames(?User $user, ?User $userWithTheNickname, bool $isValid): void
    {
        $validator = new UniqueNicknameValidator(
            $this->mockProfileManager($userWithTheNickname),
            $this->mockTokenStorage($user)
        );
        $constraint = new UniqueNickname();

        $validator->initialize($this->context);
        $validator->validate("John doe", $constraint);

        if ($isValid) {
            $this->assertNoViolation();
        } else {
            $this->buildViolation($constraint->message)
                ->assertRaised();
        }
    }

    public function getNicknames(): array
    {
        return [
            "Valid if no user with the nickname" =>[
                "user" => null,
                "userWithTheNickname" => null,
                "isValid" => true,
            ],
            "Invalid if guest user, and there is a user with the nickname" =>[
                "user" => null,
                "userWithTheNickname" => $this->mockUser(),
                "isValid" => false,
            ],
            "Invalid if user with the nickname is different user" =>[
                "user" => $this->mockUser(10),
                "userWithTheNickname" => $this->mockUser(11),
                "isValid" => false,
            ],
            "Valid if user with the nickname is the same user" =>[
                "user" => $user = $this->mockUser(10),
                "userWithTheNickname" => $user,
                "isValid" => true,
            ],
        ];
    }

    private function mockProfileManager(?User $user = null): ProfileManagerInterface
    {
        $profileManager = $this->createMock(ProfileManagerInterface::class);
        $profileManager->method('findByNickname')
            ->willReturn($user ? $this->mockProfile($user) : null);

        return $profileManager;
    }

    private function mockTokenStorage(?User $user = null): TokenStorageInterface
    {
        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage->method('getToken')->willReturn($this->mockToken($user));

        return $tokenStorage;
    }

    private function mockProfile(User $user): Profile
    {
        $profile =  $this->createMock(Profile::class);
        $profile->method('getUser')->willReturn($user);

        return $profile;
    }

    private function mockToken(?User $user): TokenInterface
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        return $token;
    }

    private function mockUser(int $id = 1): User
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn($id);

        return $user;
    }
}
