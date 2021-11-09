<?php declare(strict_types = 1);

namespace App\Tests\Exchange\Balance\Factory;

use App\Exchange\Balance\Factory\BalancesArrayFactory;
use App\Exchange\Config\Config;
use PHPUnit\Framework\TestCase;

class BalanceArrayFactoryTest extends TestCase
{
    public function testCreate(): void
    {
        $factory = new BalancesArrayFactory(
            $this->mockConfig()
        );

        $this->assertEquals(
            $factory->create([
                [4, '44'],
                [5, '55'],
                [6, '66'],
                [9, '99'],
            ]),
            [
                1 => '66',
                4 => '99',
            ]
        );
    }

    private function mockConfig(): Config
    {
        $config = $this->createMock(Config::class);
        $config->method('getOffset')->willReturn(5);

        return $config;
    }
}
