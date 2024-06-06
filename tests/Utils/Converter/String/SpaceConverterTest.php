<?php declare(strict_types = 1);

namespace App\Tests\Utils\Converter\String;

use App\Utils\Converter\String\SpaceConverter;
use PHPUnit\Framework\TestCase;

class SpaceConverterTest extends TestCase
{
    /** @var SpaceConverter */
    private SpaceConverter $converter;

    public function setUp(): void
    {
        $this->converter = new SpaceConverter();
    }

    /**
     * @dataProvider toDashProvider
     */
    public function testToDash(string $value, string $expected): void
    {
        $this->assertEquals($expected, $this->converter->toDash($value));
    }

    public function toDashProvider(): array
    {
        return [
            ['', ''],
            [' ', ''],
            ['john doe', 'john-doe'],
            ['john  doe', 'john-doe'],
        ];
    }
}
