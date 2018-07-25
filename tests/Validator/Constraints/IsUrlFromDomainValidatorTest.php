<?php

namespace App\Tests\Validator\Constraints;

use App\Validator\Constraints\IsUrlFromDomain;
use App\Validator\Constraints\IsUrlFromDomainValidator;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * @group dns-sensitive
 */
class IsUrlFromDomainValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator()
    {
        return new IsUrlFromDomainValidator();
    }

    public function testNullIsValid()
    {
        $this->validator->validate(null, new IsUrlFromDomain());

        $this->assertNoViolation();
    }

    public function testEmptyStringIsValid()
    {
        $this->validator->validate('', new IsUrlFromDomain());

        $this->assertNoViolation();
    }

    public function testExpectsStringCompatibleType()
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->validator->validate(new \stdClass(), new IsUrlFromDomain());
    }

    /**
     * @dataProvider getValidDomainUrls
     */
    public function testValidDomainUrls($url)
    {
        $constraint = new IsUrlFromDomain();
        $constraint->domain = 'facebook.com';
        $this->validator->validate($url, $constraint);

        $this->assertNoViolation();
    }

    public function getValidDomainUrls(): array
    {
        return [
            ['facebook.com/something'],
            ['https://facebook.com/something'],
            ['https://facebook.com/username'],
            ['http://facebook.com/something'],
            ['http://facebook.com/username?q=something&u=another'],
        ];
    }

    /**
     * @dataProvider getInvalidDomainUrls
     */
    public function testInvalidDomainUrls($url)
    {
        $constraint = new IsUrlFromDomain();
        $constraint->message = 'error message';
        $constraint->domain = 'facebook.com';

        $this->validator->validate($url, $constraint);

        $this->buildViolation('error message')
            ->setParameter('{{ string }}', $url)
            ->assertRaised();
    }

    public function getInvalidDomainUrls(): array
    {
        return [
            ['fb.com'],
            ['https://youtube.com/'],
            ['https://facebook.es/username'],
            ['http://facebook.com/'],
            ['facebook'],
        ];
    }
}