<?php declare(strict_types = 1);

namespace App\Tests\Validator\Constraints;

use App\Validator\Constraints\DateTimeMin;
use App\Validator\Constraints\DateTimeMinValidator;
use DateTimeInterface;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class DateTimeMinValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): DateTimeMinValidator
    {
        return new DateTimeMinValidator();
    }

    /** @dataProvider provider */
    public function testValidate(DateTimeInterface $value, bool $violation = false): void
    {
        $constraint = new DateTimeMin([
            'modify' => '+1 day',
        ]);

        $this->validator->validate($value, $constraint);

        if ($violation) {
            $this->buildViolation($constraint->message)
                ->setParameter('{{modify}}', '+1 day')
                ->assertRaised();
        } else {
            $this->assertNoViolation();
        }
    }

    public function provider(): array
    {
        return [
            'valid' => [
                (new \DateTimeImmutable())->modify('+1 day'),
                true,
            ],
            'invalid' => [
                (new \DateTimeImmutable())->modify('+1000 day'),
                false,
            ],
        ];
    }
}
