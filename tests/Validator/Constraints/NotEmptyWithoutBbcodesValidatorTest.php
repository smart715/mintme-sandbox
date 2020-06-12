<?php declare(strict_types = 1);

namespace App\Tests\Validator\Constraints;

use App\Validator\Constraints\NotEmptyWithoutBbcodes;
use App\Validator\Constraints\NotEmptyWithoutBbcodesValidator;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class NotEmptyWithoutBbcodesValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): NotEmptyWithoutBbcodesValidator
    {
        return new NotEmptyWithoutBbcodesValidator();
    }

    /** @dataProvider provider */
    public function testValidate(string $value, bool $violation = false): void
    {
        $constraint = new NotEmptyWithoutBbcodes();

        $this->validator->validate($value, $constraint);

        if ($violation) {
            $this->buildViolation($constraint->message)->assertRaised();
        } else {
            $this->assertNoViolation();
        }
    }

    public function provider(): array
    {
        return [
            ["[b][/b]", true],
            [" \t\n", true],
            ["[b] [/b]\n  ", true],
            ["foo"],
            ["[b]foo[/b]"],
            ["[ b ] \t\n[/ b ]", true],
            ["[hola]"],
            ["[url=http://example.com][/url]"],
            ["[img=http://foo.com/bar.jpg][/img]"],
            ["[i][/i][u][/u][s][/s][ul][/ul][ol][li][/li][/ol][p][/p][s][/s][url][/url][img][/img][h1][/h1][h2][/h2][h3][/h3][h4][/h4][h5][/h5][h6][/h6]", true],
        ];
    }
}
