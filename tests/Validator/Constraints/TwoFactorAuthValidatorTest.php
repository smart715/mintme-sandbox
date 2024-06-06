<?php declare(strict_types = 1);

namespace App\Tests\Validator\Constraints;

use App\Entity\User;
use App\Manager\TwoFactorManager;
use App\Services\TranslatorService\TranslatorInterface;
use App\Validator\Constraints\TwoFactorAuth;
use App\Validator\Constraints\TwoFactorAuthValidator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class TwoFactorAuthValidatorTest extends TestCase
{
    public function testValidateWithValue(): void
    {
        $twoFactorAuthValidator = new TwoFactorAuthValidator(
            $this->mockTokenStorageInterface(),
            $this->mockTwoFactorManager(),
            $this->createMock(TranslatorInterface::class)
        );

        $twoFactorAuthValidator->user = $this->createMock(User::class);
        $twoFactorAuthValidator->initialize($this->mockContextInterface());
        $twoFactorAuthValidator->validate('123', $this->createMock(TwoFactorAuth::class));
    }

    public function testValidateWithValueEmpty(): void
    {
        $twoFactorAuthValidator = new TwoFactorAuthValidator(
            $this->mockTokenStorageInterface(),
            $this->mockTwoFactorManager(),
            $this->createMock(TranslatorInterface::class)
        );

        $twoFactorAuthValidator->user = $this->createMock(User::class);
        $twoFactorAuthValidator->initialize($this->mockContextInterface());
        $twoFactorAuthValidator->validate('', $this->createMock(TwoFactorAuth::class));
    }

    private function mockTwoFactorManager(): TwoFactorManager
    {
        /** @var TwoFactorManager|MockObject $twoFactorManager */
        $twoFactorManager = $this->createMock(TwoFactorManager::class);
        $twoFactorManager->method('checkCode')->willReturn(false);

        return $twoFactorManager;
    }

    private function mockTokenStorageInterface(): TokenStorageInterface
    {
        /** @var TokenInterface|MockObject $tokenInterface */
        $tokenInterface = $this->createMock(TokenInterface::class);

        /** @var TokenStorageInterface|MockObject $tokenStorageInterface */
        $tokenStorageInterface = $this->createMock(TokenStorageInterface::class);
        $tokenStorageInterface->method('getToken')->willReturn($tokenInterface);

        return $tokenStorageInterface;
    }

    private function mockContextInterface(): ExecutionContextInterface
    {
        /** @var ExecutionContextInterface|MockObject $contextInterface */
        $contextInterface = $this->createMock(ExecutionContextInterface::class);
        $contextInterface->expects($this->once())->method('buildViolation')->willReturn(
            $this->mockConstraintViolationBuilderInterface()
        );

        return $contextInterface;
    }

    private function mockConstraintViolationBuilderInterface(): ConstraintViolationBuilderInterface
    {
        /** @var ConstraintViolationBuilderInterface|MockObject $builderInterface */
        $builderInterface = $this->createMock(ConstraintViolationBuilderInterface::class);
        $builderInterface->method('setParameter')->willReturnSelf();

        return $builderInterface;
    }
}
