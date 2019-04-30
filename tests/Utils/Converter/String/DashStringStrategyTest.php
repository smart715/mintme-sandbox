<?php declare(strict_types = 1);

namespace App\Tests\Utils\Converter\String;

use App\Utils\Converter\String\DashStringStrategy;
use App\Utils\Converter\String\StringConverter;
use PHPUnit\Framework\TestCase;

class DashStringStrategyTest extends TestCase
{
    /** @var StringConverter */
    private $dasher;

    public function setUp(): void
    {
        $this->dasher = new StringConverter(new DashStringStrategy());
        parent::setUp();
    }

    /**
     * @dataProvider dashedProvider
     */
    public function testDashed(string $name, string $dashedName): void
    {
        $this->assertEquals($dashedName, $this->dasher->convert($name));
    }

    public function dashedProvider(): array
    {
        return [
            ['test', 'test'],
            ['test 123', 'test-123'],
            ['test--123', 'test-123'],
            [' test', 'test'],
            [' test ', 'test'],
            [' test--', 'test'],
            [' test-1--  1', 'test-1-1'],
            ['- tes t-1--  1', 'tes-t-1-1'],
        ];
    }
}
