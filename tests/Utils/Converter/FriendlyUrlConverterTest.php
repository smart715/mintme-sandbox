<?php declare(strict_types = 1);

namespace App\Tests\Utils\Converter;

use App\Utils\Converter\FriendlyUrlConverter;
use PHPUnit\Framework\TestCase;

class FriendlyUrlConverterTest extends TestCase
{
    /**
     * @var string $fileName
     * @var string $friendlyUrl
     * @dataProvider convertProvider
     */
    public function testConvert(string $fileName, string $friendlyUrl): void
    {
        $urlConverter = new FriendlyUrlConverter();

        $this->assertEquals($friendlyUrl, $urlConverter->convert($fileName));
    }

    public function convertProvider(): array
    {
        return [
            ['mintMe Press Kit.pdf', 'mintme-press-kit.pdf'],
            ['!@#$%mintme.pdf', 'mintme.pdf'],
            ['a    lot of spaces', 'a-lot-of-spaces'],
            ['FiLeNaMe test', 'filename-test'],
            ['file------mintme.doc', 'file-mintme.doc'],
        ];
    }
}
