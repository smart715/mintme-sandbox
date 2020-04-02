<?php declare(strict_types = 1);

namespace App\Tests\Validator\Constraints;

use App\Entity\User;
use App\Manager\TwoFactorManager;
use App\Validator\Constraints\TwoFactorAuth;
use App\Validator\Constraints\TwoFactorAuthValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class TwoFactorAuthValidatorTest extends TestCase
{
    public function testValidate(): void
    {
        $builder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $builder->method('setParameter')->willReturnSelf();

        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects($this->once())->method('buildViolation')->willReturn(
            $builder
        );

        $token = $this->createMock(TokenInterface::class);

        $storage = $this->createMock(TokenStorageInterface::class);
        $storage->method('getToken')->willReturn($token);

        $tm = $this->createMock(TwoFactorManager::class);
        $tm->method('checkCode')->willReturn(false);

        $validator = new TwoFactorAuthValidator($storage, $tm);
        $validator->user = $this->createMock(User::class);
        $validator->initialize($context);
        $validator->validate('123', $this->createMock(TwoFactorAuth::class));
        $validator->validate('', $this->createMock(TwoFactorAuth::class));
    }
}
