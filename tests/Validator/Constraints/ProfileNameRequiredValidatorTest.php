<?php declare(strict_types = 1);

namespace App\Tests\Validator\Constraints;

use App\Entity\Profile;
use App\Validator\Constraints\ProfileNameRequired;
use App\Validator\Constraints\ProfileNameRequiredValidator;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class ProfileNameRequiredValidatorTest extends ConstraintValidatorTestCase
{
    public function createValidator(): ProfileNameRequiredValidator
    {
        return new ProfileNameRequiredValidator();
    }

    /** @dataProvider getTestCases */
    public function testValidate(
        string $property,
        string $value,
        string $otherFieldValue,
        bool $isValid
    ): void {
        $constraint = new ProfileNameRequired();

        $this->setObject($profile = $this->mockProfile($property, $otherFieldValue));
        $this->setProperty($profile, $property);
        $this->validator->validate($value, $constraint);

        if ($isValid) {
            $this->assertNoViolation();
        } else {
            $this->buildViolation(
                "firstName" === $property ?
                $constraint->firstNameMessage :
                $constraint->lastNameMessage
            )->assertRaised();
        }
    }

    public function getTestCases(): array
    {
        return [
            "Empty first name fails if last name isn't empty" => [
                "property" => "firstName",
                "value" => "",
                "otherFieldValue" => "Doe",
                "isValid" => false,
            ],
            "Empty last name fails if first name isn't empty" => [
                "property" => "lastName",
                "value" => "",
                "otherFieldValue" => "John",
                "isValid" => false,
            ],
            "Empty first name passes if last name is empty" => [
                "property" => "firstName",
                "value" => "",
                "otherFieldValue" => "",
                "isValid" => true,
            ],
            "Empty last name passes if first name is empty" => [
                "property" => "lastName",
                "value" => "",
                "otherFieldValue" => "",
                "isValid" => true,
            ],
            "Non-empty first name passes regardless last name value" => [
                "property" => "firstName",
                "value" => "John",
                "otherFieldValue" => "",
                "isValid" => true,
            ],
            "Non-empty last name passes regardless first name value" => [
                "property" => "lastName",
                "value" => "Doe",
                "otherFieldValue" => "",
                "isValid" => true,
            ],

        ];
    }

    private function mockProfile(string $property, string $otherFieldValue): Profile
    {
        $profile = $this->createMock(Profile::class);

        $profile->method('firstName' === $property ?  'getLastName' : 'getFirstName')
                ->willReturn($otherFieldValue);

        return $profile;
    }
}
