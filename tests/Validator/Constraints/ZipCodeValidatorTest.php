<?php declare(strict_types = 1);

namespace App\Tests\Validator\Constraints;

use App\Validator\Constraints\ZipCode;
use App\Validator\Constraints\ZipCodeValidator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Context\ExecutionContext;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;
use Symfony\Component\Validator\Exception\MissingOptionsException;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class ZipCodeValidatorTest extends TestCase
{
    /** @var ZipCodeValidator */
    protected $validator;

    /** @var ExecutionContextInterface|MockObject */
    protected $contextMock;

    public function setUp(): void
    {
        $this->contextMock = $this->getMockBuilder(ExecutionContext::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->validator = new ZipCodeValidator();
    }

    /**
     * @param string $isoCode
     * @param string $value
     * @dataProvider validZipCodes
     * @doesNotPerformAssertions
     */
    public function testZipCodeValidationWithIso(string $isoCode, string $value): void
    {
        $constraint = new ZipCode(["iso" => $isoCode]);
        $this->validator->validate($value, $constraint);
    }

    /**
     * @dataProvider
     * @return array
     */
    public function validZipCodes(): array
    {
        return [
            ['RU', '347430'],
            ['KE', '12345'],
            ['MU', '15325'],
            ['NL', '1234AB'],
            ['NL', '1234 AB'],
            ['PN', 'PCRN 1ZZ'],
            ['FOO', 'BAR BAZ'],
        ];
    }

    public function testUnexpectedTypeException(): void
    {
        $constraint = $this->getMockBuilder(Constraint::class)->disableOriginalConstructor()->getMock();
        $this->expectException(UnexpectedTypeException::class);
        $this->validator = new ZipCodeValidator();
        $this->validator->validate('FOO', $constraint);
    }
}
