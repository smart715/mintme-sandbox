<?php declare(strict_types = 1);

namespace App\Tests\Validator\Constraints;

use App\Admin\MainDocumentsAdmin;
use App\Entity\Media\Media;
use App\Validator\Constraints\MainDocumentValidator;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class MainDocumentValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): MainDocumentValidator
    {
        return new MainDocumentValidator();
    }

    public function testValid(): void
    {
        $this->validator->validate(
            $this->mockMedia(MainDocumentsAdmin::PROVIDER_NAME),
            $this->constraint
        );
        $this->assertNoViolation();
    }

    public function testInvalid(): void
    {
        $this->validator->validate($this->mockMedia('foo bar'), $this->constraint);
        $this->buildViolation($this->constraint->message)
            ->assertRaised();
    }

    private function mockMedia(string $value): Media
    {
        $media = $this->createMock(Media::class);
        $media->expects($this->any())
            ->method('getProviderName')
            ->willReturn($value);

        return $media;
    }
}
