<?php

namespace App\Tests\Validator\Constraints;

use App\Validator\Constraints\IsUrlFromDomain;
use App\Validator\Constraints\IsUrlFromDomainValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @group dns-sensitive
 */
class IsUrlFromDomainValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): IsUrlFromDomainValidator
    {
        return new IsUrlFromDomainValidator();
    }

    public function testNullIsValid(): void
    {
        $this->validator->validate(null, new IsUrlFromDomain());

        $this->assertNoViolation();
    }

    public function testEmptyStringIsValid(): void
    {
        $this->validator->validate('', new IsUrlFromDomain());

        $this->assertNoViolation();
    }

    public function testExpectsStringCompatibleType(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->validator->validate(new \stdClass(), new IsUrlFromDomain());
    }

    /**
     * @dataProvider getValidDomainUrls
     */
    public function testValidDomainUrls(string $url): void
    {
        $constraint = new IsUrlFromDomain();
        $constraint->domain = 'www.facebook.com';
        $this->validator->validate($url, $constraint);

        $this->assertNoViolation();
    }

    public function getValidDomainUrls(): array
    {
        return [
            ['www.facebook.com/something'],
            ['https://www.facebook.com/something'],
            ['https://www.facebook.com/username'],
            ['http://www.facebook.com/something/'],
            ['http://www.facebook.com/username?q=something&u=another'],
        ];
    }

    /**
     * @dataProvider getInvalidDomainUrls
     */
    public function testInvalidDomainUrls(string $url): void
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
