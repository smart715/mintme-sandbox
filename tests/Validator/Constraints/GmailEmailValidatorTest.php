<?php declare(strict_types = 1);

namespace App\Tests\Validator\Constraints;

use App\Entity\User;
use App\Manager\UserManager;
use App\Validator\Constraints\GmailEmail;
use App\Validator\Constraints\GmailEmailValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class GmailEmailValidatorTest extends TestCase
{
    public function testValidate(): void
    {
        $userEmail = 'test@gmail.com';
        $email = 't.e.s.t@gmail.com';

        $user = $this->createMock(User::class);
        $user->method('getEmail')->willReturn($userEmail);

        $um = $this->createMock(UserManager::class);
        $um->method('getGmailUsers')->willReturn([$user]);

        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturn(null);

        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects($this->once())->method('buildViolation')->willReturn(
            $this->createMock(ConstraintViolationBuilderInterface::class)
        );

        $constraint = $this->createMock(GmailEmail::class);
        $constraint->message = 'test';

        $validator = new GmailEmailValidator($security, $um);
        $validator->initialize($context);

        $validator->validate($email, $constraint);
        $validator->validate('uniqueemail', $constraint);
        $validator->validate(null, $constraint);
    }

    public function testValidateAuthUser(): void
    {
        $email = 'test@gmail.com';

        $user = $this->createMock(User::class);

        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturn([$user]);

        $um = $this->createMock(UserManager::class);

        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects($this->exactly(0))->method('buildViolation')->willReturn(
            $this->createMock(ConstraintViolationBuilderInterface::class)
        );

        $constraint = $this->createMock(GmailEmail::class);
        $constraint->message = 'test';

        $validator = new GmailEmailValidator($security, $um);
        $validator->initialize($context);

        $validator->validate($email, $constraint);
        $validator->validate('uniqueemail', $constraint);
        $validator->validate(null, $constraint);
    }

    public function testValidateNotExistGmailEmail(): void
    {
        $email = 'test@gmail.com';
        $userEmail = 't.e.s.t@notGoogle.com';

        $user = $this->createMock(User::class);
        $user->method('getEmail')->willReturn($userEmail);

        $um = $this->createMock(UserManager::class);
        $um->method('getGmailUsers')->willReturn(null);

        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturn(null);

        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects($this->exactly(0))->method('buildViolation')->willReturn(
            $this->createMock(ConstraintViolationBuilderInterface::class)
        );

        $constraint = $this->createMock(GmailEmail::class);
        $constraint->message = 'test';

        $validator = new GmailEmailValidator($security, $um);
        $validator->initialize($context);

        $validator->validate($email, $constraint);
        $validator->validate('uniqueemail', $constraint);
        $validator->validate(null, $constraint);
    }
}
