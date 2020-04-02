<?php declare(strict_types = 1);

namespace App\Tests\Validator\Constraints;

use App\Validator\Constraints\GreaterThanPrevious;
use App\Validator\Constraints\GreaterThanPreviousValidator;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\UnitOfWork;
use PHPUnit\Framework\TestCase;
use stdClass;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class GreaterThanPreviousValidatorTest extends TestCase
{
    /** @dataProvider validateProvider */
    public function testValidate(int $original, int $new, bool $validate): void
    {
        $uof = $this->createMock(UnitOfWork::class);
        $uof->method('getOriginalEntityData')->willReturn([
            'val' => $original,
        ]);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getUnitOfWork')->willReturn($uof);

        $context = $this->createMock(ExecutionContextInterface::class);
        $context->method('getObject')->willReturn(new stdClass());
        $context->method('getPropertyName')->willReturn('val');
        $context->expects($validate ? $this->never() : $this->once())->method('buildViolation')->willReturn(
            $this->createMock(ConstraintViolationBuilderInterface::class)
        );

        $constraint = $this->createMock(GreaterThanPrevious::class);
        $constraint->message = 'test';

        $validator = new GreaterThanPreviousValidator($em);
        $validator->initialize($context);
        $validator->validate($new, $constraint);
    }

    public function validateProvider(): array
    {
        return [
            [1, 3, true],
            [3, 3, true],
            [4, 3, false],
            [5, 3, false],
        ];
    }

    public function testValidateEmptyOrigin(): void
    {
        $uof = $this->createMock(UnitOfWork::class);
        $uof->method('getOriginalEntityData')->willReturn(null);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getUnitOfWork')->willReturn($uof);

        $context = $this->createMock(ExecutionContextInterface::class);
        $context->method('getObject')->willReturn(new stdClass());
        $context->method('getPropertyName')->willReturn('val');
        $context->expects($this->never())->method('buildViolation')->willReturn(
            $this->createMock(ConstraintViolationBuilderInterface::class)
        );

        $constraint = $this->createMock(Constraint::class);

        $validator = new GreaterThanPreviousValidator($em);
        $validator->initialize($context);
        $validator->validate(123, $constraint);
    }
}
