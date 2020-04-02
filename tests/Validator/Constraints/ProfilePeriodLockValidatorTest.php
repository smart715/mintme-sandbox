<?php declare(strict_types = 1);

namespace App\Tests\Validator\Constraints;

use App\Entity\Profile;
use App\Utils\DateTime;
use App\Validator\Constraints\ProfilePeriodLock;
use App\Validator\Constraints\ProfilePeriodLockValidator;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\UnitOfWork;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class ProfilePeriodLockValidatorTest extends TestCase
{
    /** @dataProvider validateProvider */
    public function testValidate(
        string $original,
        string $new,
        ?DateTimeImmutable $changedDate,
        DateTimeImmutable $now,
        bool $validate
    ): void {
        $uof = $this->createMock(UnitOfWork::class);
        $uof->method('getOriginalEntityData')->willReturn([
            'val' => $original,
        ]);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getUnitOfWork')->willReturn($uof);

        $profile = $this->createMock(Profile::class);
        $profile->method('getNameChangedDate')->willReturn($changedDate);

        $context = $this->createMock(ExecutionContextInterface::class);
        $context->method('getObject')->willReturn($profile);
        $context->method('getPropertyName')->willReturn('val');

        $builder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $builder->method('setParameter')->willReturnSelf();

        $context->expects($validate ? $this->never() : $this->once())->method('buildViolation')->willReturn(
            $builder
        );

        $constraint = $this->createMock(ProfilePeriodLock::class);
        $constraint->message = 'test';

        $dateTime = $this->createMock(DateTime::class);
        $dateTime->method('now')->willReturn($now);

        $validator = new ProfilePeriodLockValidator($em, $dateTime);
        $validator->initialize($context);
        $validator->validate($new, $constraint);
    }

    public function validateProvider(): array
    {
        return [
            [1, 3, null, new DateTimeImmutable('now - 1 day'), true],
            [3, 3, new DateTimeImmutable(), new DateTimeImmutable('now - 1 day'), true],
            [3, 4, new DateTimeImmutable(), new DateTimeImmutable('now + 1 day'), true],
            [4, 3, new DateTimeImmutable(), new DateTimeImmutable('now - 1 day'), false],
            [5, 3, new DateTimeImmutable(), new DateTimeImmutable('now - 1 day'), false],
        ];
    }

    public function testValidateWithoutOrigin(): void
    {
        $uof = $this->createMock(UnitOfWork::class);
        $uof->method('getOriginalEntityData')->willReturn([]);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getUnitOfWork')->willReturn($uof);

        $profile = $this->createMock(Profile::class);
        $profile->method('getNameChangedDate')->willReturn(new DateTimeImmutable());

        $context = $this->createMock(ExecutionContextInterface::class);
        $context->method('getObject')->willReturn($profile);
        $context->method('getPropertyName')->willReturn('val');

        $builder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $builder->method('setParameter')->willReturnSelf();

        $context->expects($this->once())->method('buildViolation')->willReturn(
            $builder
        );

        $constraint = $this->createMock(ProfilePeriodLock::class);
        $constraint->message = 'test';

        $dateTime = $this->createMock(DateTime::class);
        $dateTime->method('now')->willReturn(
            new DateTimeImmutable('now - 1 day')
        );

        $validator = new ProfilePeriodLockValidator($em, $dateTime);
        $validator->initialize($context);
        $validator->validate('12', $constraint);
    }
}
