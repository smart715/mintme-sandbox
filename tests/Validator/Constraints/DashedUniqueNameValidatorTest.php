<?php declare(strict_types = 1);

namespace App\Tests\Validator\Constraints;

use App\Entity\Token\Token;
use App\Entity\User;
use App\Manager\TokenManagerInterface;
use App\Validator\Constraints\DashedUniqueName;
use App\Validator\Constraints\DashedUniqueNameValidator;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class DashedUniqueNameValidatorTest extends ConstraintValidatorTestCase
{
    /** @var mixed */
    private $tokenStorageInterface;

    /** @var mixed */
    private $tokenManagerInterface;

    protected function createValidator(): DashedUniqueNameValidator
    {
        $this->tokenManagerInterface = $this->createMock(TokenManagerInterface::class);
        $this->tokenStorageInterface = $this->mockTokenStorageInterface();

        return new DashedUniqueNameValidator($this->tokenManagerInterface, $this->tokenStorageInterface);
    }

    public function testGoodValidate(): void
    {
        $constraint = new DashedUniqueName();
        $constraint->message = 'Token name is already exists.';
        $this->tokenManagerInterface->method('isExisted')->willReturn(true);
        $this->validator->validate('baz', $constraint);

        $this->buildViolation('Token name is already exists.')->assertRaised();
    }

    public function testBadValidate(): void
    {
        $this->tokenManagerInterface->method('isExisted')->willReturn(false);
        $this->validator->validate('foo', new DashedUniqueName());

        $this->assertNoViolation();
    }

    private function mockTokenStorageInterface(): TokenStorageInterface
    {
        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage->method('getToken')->willReturn($this->mockTokenInterface());

        return $tokenStorage;
    }

    private function mockTokenInterface(): TokenInterface
    {
        $tokenStorage = $this->createMock(TokenInterface::class);
        $tokenStorage->method('getUser')->willReturn($this->mockUser());

        return $tokenStorage;
    }

    private function mockUser(): User
    {
        $user = $this->createMock(User::class);
        $user->method('getTokens')->willReturn([
            $this->mockToken('foo'),
            $this->mockToken('bar'),
        ]);

        return $user;
    }

    private function mockToken(string $name): Token
    {
        $token = $this->createMock(Token::class);
        $token->method('getName')->willReturn($name);

        return $token;
    }
}
