<?php declare(strict_types = 1);

namespace App\Tests\Validator\Constraints;

use App\Validator\Constraints\ZipCode;
use App\Validator\Constraints\ZipCodeValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class ZipCodeValidatorTest extends TestCase
{
    /** @var ZipCodeValidator */
    protected $validator;
    /** @var ExecutionContextInterface */
    protected $contextMock;

    public function setUp(): void
    {
        $this->contextMock = $this->getMockBuilder(ExecutionContextInterface::class)
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
    public function testValidate(string $isoCode, string $value): void
    {
        $this->validator->validate(
            $value,
            new ZipCode(['iso' => $isoCode])
        );
    }

    /**
     * @dataProvider
     */
    public function validZipCodes(): array
    {
        return [
            ['CH', '3007'],
            ['HK', '999077'],
            ['KE', '12345'],
            ['RU', '153251'],
            ['NL', '1234AB'],
            ['NL', '1234 AB'],
            ['PN', 'PCRN 1ZZ'],
        ];
    }
}
