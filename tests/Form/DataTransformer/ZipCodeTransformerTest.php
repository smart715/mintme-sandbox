<?php declare(strict_types = 1);

namespace App\Tests\Form\DataTransformer;

use App\Form\DataTransformer\ZipCodeTransformer;
use PHPUnit\Framework\TestCase;

class ZipCodeTransformerTest extends TestCase
{
    /** @dataProvider  zipCodeProvider */
    public function testZipCodeTransformer(string $value, string $expected): void
    {
        $xssProtectionTransformer = new ZipCodeTransformer();

        # Both acts the same way
        $this->assertEquals($expected, $xssProtectionTransformer->transform($value));
        $this->assertEquals($expected, $xssProtectionTransformer->reverseTransform($value));
    }

    public function zipCodeProvider(): array
    {
        return [
            "Lowercase turn to uppercase" => ['lower', 'LOWER'],
            "Uppercase stays uppercase" => ['UPPER', 'UPPER'],
            'Spaces are removed' => ['  12345  ', '12345'],
        ];
    }
}
