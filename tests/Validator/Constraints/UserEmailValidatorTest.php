<?php declare(strict_types = 1);

namespace App\Tests\Validator\Constraints;

use App\Communications\DisposableEmailCommunicatorInterface;
use App\Entity\User;
use App\Manager\UserManagerInterface;
use App\Validator\Constraints\UserEmail;
use App\Validator\Constraints\UserEmailValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class UserEmailValidatorTest extends TestCase
{
    public function testValidateDisposableFalse(): void
    {
        $email = 'foo@bar.baz';
        $user = $this->createMock(User::class);
        $user->method('getEmail')->willReturn($email);

        $um = $this->createMock(UserManagerInterface::class);
        $um->method('findUserByEmail')->willReturn($user);

        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects($this->once())->method('buildViolation')->willReturn(
            $this->createMock(ConstraintViolationBuilderInterface::class)
        );

        $constraint = $this->createMock(UserEmail::class);
        $constraint->message = 'test';

        $token = $this->createMock(TokenInterface::class);

        $storage = $this->createMock(TokenStorageInterface::class);
        $storage->method('getToken')->willReturn($token);

        $disposableEmail = $this->createMock(DisposableEmailCommunicatorInterface::class);
        $disposableEmail->method('checkDisposable')->willReturn(false);

        $validator = new UserEmailValidator($um, $storage, $disposableEmail);
        $validator->user = $user;
        $validator->initialize($context);

        $validator->validate($email, $constraint);
        $validator->validate('uniqueemail', $constraint);
        $validator->validate(null, $constraint);
    }

    public function testValidateDisposableTrue(): void
    {
        $email = 'foo@invalid.domain';
        $user = $this->createMock(User::class);
        $user->method('getEmail')->willReturn('foo@bar.baz');

        $um = $this->createMock(UserManagerInterface::class);
        $um->method('findUserByEmail')->willReturn(null);

        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects($this->exactly(2))->method('buildViolation')->willReturn(
            $this->createMock(ConstraintViolationBuilderInterface::class)
        );

        $constraint = $this->createMock(UserEmail::class);
        $constraint->domainMessage = 'test';

        $token = $this->createMock(TokenInterface::class);

        $storage = $this->createMock(TokenStorageInterface::class);
        $storage->method('getToken')->willReturn($token);

        $disposableEmail = $this->createMock(DisposableEmailCommunicatorInterface::class);
        $disposableEmail->method('checkDisposable')->willReturn(true);

        $validator = new UserEmailValidator($um, $storage, $disposableEmail);
        $validator->user = $user;
        $validator->initialize($context);

        $validator->validate($email, $constraint);
        $validator->validate('uniqueemail', $constraint);
        $validator->validate(null, $constraint);
    }
}
