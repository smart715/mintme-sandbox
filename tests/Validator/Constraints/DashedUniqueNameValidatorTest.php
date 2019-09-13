<?php declare(strict_types = 1);

namespace App\Tests\Validator\Constraints;

use App\Manager\TokenManagerInterface;
use App\Validator\Constraints\DashedUniqueName;
use App\Validator\Constraints\DashedUniqueNameValidator;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class DashedUniqueNameValidatorTest extends ConstraintValidatorTestCase
{

    /** @var mixed */
    private $tokenManagerInterface;

    protected function createValidator(): DashedUniqueNameValidator
    {
        $this->tokenManagerInterface = $this->createMock(TokenManagerInterface::class);

        return new DashedUniqueNameValidator($this->tokenManagerInterface);
    }

    public function testGoodValidate(): void
    {
        $constraint = new DashedUniqueName();
        $constraint->message = 'Token name is already exists.';
        $this->tokenManagerInterface->method('isExisted')->willReturn(true);
        $this->validator->validate('NinjoToken', $constraint);

        $this->buildViolation('Token name is already exists.')->assertRaised();
    }

    public function testBadValidate(): void
    {
        $this->tokenManagerInterface->method('isExisted')->willReturn(false);
        $this->validator->validate('NinjoToken', new DashedUniqueName());

        $this->assertNoViolation();
    }
}
