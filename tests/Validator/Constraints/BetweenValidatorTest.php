<?php declare(strict_types = 1);

namespace App\Tests\Validator\Constraints;

use App\Tests\Mocks\MockMoneyWrapper;
use App\Validator\Constraints\Between;
use App\Validator\Constraints\BetweenValidator;
use Money\Currency;
use Money\Money;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class BetweenValidatorTest extends ConstraintValidatorTestCase
{

    use MockMoneyWrapper;

    protected function createValidator(): BetweenValidator
    {
        return new BetweenValidator($this->mockMoneyWrapper());
    }

    /** @dataProvider provider */
    public function testValidate(string $min, string $max, string $value, bool $violation = false): void
    {
        $constraint = new Between(['min' => $min, 'max' => $max]);

        $this->validator->validate(new Money($value, new Currency('FOO')), $constraint);

        if ($violation) {
            $this->buildViolation($constraint->message)
                ->setParameter('{{min}}', $min)
                ->setParameter('{{max}}', $max)
                ->assertRaised();
        } else {
            $this->assertNoViolation();
        }
    }

    public function provider(): array
    {
        return [
            ['1', '3', '2'],
            ['1', '3', '1'],
            ['1', '3', '3'],
            ['1', '3', '0', true],
            ['1', '3', '4', true],
        ];
    }
}
