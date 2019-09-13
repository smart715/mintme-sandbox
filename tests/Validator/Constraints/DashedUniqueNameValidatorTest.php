<?php declare(strict_types = 1);

namespace App\Tests\Validator\Constraints;

use App\Manager\TokenManagerInterface;
use App\Validator\Constraints\DashedUniqueName;
use App\Validator\Constraints\DashedUniqueNameValidator;
use App\Validator\Constraints\IsUrlFromDomainValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class DashedUniqueNameValidatorTest extends ConstraintValidatorTestCase
{

    private $tokenManagerInterface;
    private $tokenName;

    protected function createValidator(): DashedUniqueNameValidator
    {
        $this->tokenName = 'NinjoToken';
        $this->tokenManagerInterface = $this->createMock(TokenManagerInterface::class);
        return new DashedUniqueNameValidator($this->tokenManagerInterface);
    }

    public function testGoodValidate(): void
    {
        $constraint = new DashedUniqueName();
        $constraint->message = 'Token name is already exists.';

        $this->tokenManagerInterface->method('isExisted')->willReturn(true);
        $this->validator->validate($this->tokenName, $constraint);

        $this->buildViolation('Token name is already exists.')->assertRaised();
    }

    public function testBadValidate(): void
    {
        $this->tokenManagerInterface->method('isExisted')->willReturn(false);
        $this->validator->validate($this->tokenName, new DashedUniqueName());

        $this->assertNoViolation();
    }
}
