<?php declare(strict_types = 1);

namespace App\Tests\Form\DataTransformer;

use App\Form\DataTransformer\ColorTransformer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Exception\TransformationFailedException;

class ColorTransformerTest extends TestCase
{
    /** @dataProvider provideTransform */
    public function testColorTransform(int $value, string $expected): void
    {
        $colorTransform = new ColorTransformer();

        $this->assertEquals($expected, $colorTransform->transform($value));
    }

    public function provideTransform(): array
    {
        return [
            'Decimal to hex' => [999, '#0003e7'],
            'A non 6-digit-hex will get padded left with 0 to 6 chars' => [999, '#0003e7'],
        ];
    }

    /** @dataProvider provideReverseTransform */
    public function testColorReverseTransform(string $value, string $expected): void
    {
        $colorTransform = new ColorTransformer();

        $this->assertEquals($expected, $colorTransform->reverseTransform($value));
    }

    public function provideReverseTransform(): array
    {
        return [
            'Hex to decimal' => ['#0003e7', '999'],
            'A padded 6-digit-hex will get un-padded' => ['#0003e7', '999'],
        ];
    }

    public function testColorReverseTransformWithInvalidData(): void
    {
        $colorTransform = new ColorTransformer();

        $this->expectException(TransformationFailedException::class);

        $colorTransform->reverseTransform([]); /** @phpstan-ignore-line */
    }
}
