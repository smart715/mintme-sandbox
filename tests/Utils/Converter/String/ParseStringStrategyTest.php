<?php declare(strict_types = 1);

namespace App\Tests\Utils\Converter\String;

use App\Utils\Converter\String\ParseStringStrategy;
use App\Utils\Converter\String\StringConverter;
use PHPUnit\Framework\TestCase;

class ParseStringStrategyTest extends TestCase
{
    /** @var StringConverter */
    private $parser;

    public function setUp(): void
    {
        $this->parser = new StringConverter(new ParseStringStrategy());
        parent::setUp();
    }

    /**
     * @dataProvider parserProvider
     */
    public function testParser(string $name, string $parsedName): void
    {
        $this->assertEquals($parsedName, $this->parser->convert($name));
    }

    public function parserProvider(): array
    {
        return [
            ['test', 'test'],
            [' test', 'test'],
            ['test  123', 'test 123'],
            ['test--123', 'test-123'],
            [' - - - t--e st', 't-e st'],
            [' te s  t ', 'te s t'],
            [' test--', 'test'],
            [' test-1--  1', 'test-1-1'],
            ['- tes t-1--  1', 'tes t-1-1'],
        ];
    }
}
