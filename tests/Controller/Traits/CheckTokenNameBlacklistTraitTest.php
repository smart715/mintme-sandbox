<?php declare(strict_types = 1);

namespace App\Tests\Controller\Traits;

use App\Entity\Blacklist;
use App\Manager\BlacklistManagerInterface;
use PHPUnit\Framework\TestCase;

class CheckTokenNameBlacklistTraitTest extends TestCase
{
    /** @dataProvider checkProvider */
    public function testCheckTokenNameBlacklist(string $name, bool $result): void
    {
        $blm = $this->createMock(BlacklistManagerInterface::class);
        $blm->method('getList')->with('token')->willReturn([
            new Blacklist('ethereum', 'token'),
            new Blacklist('bitcoin', 'token'),
        ]);

        $ctnbt = new CheckTokenNameBlacklist($blm);

        $this->assertEquals($result, $ctnbt->test($name));
    }

    public function checkProvider(): array
    {
        return [
            ['ethereum', true],
            ['ethereum-bitcoin', true],
            ['ethereum-ethereum', true],
            ['ethereum-foo', false],
            ['foo-bar', false],
            ['foobar', false],
        ];
    }
}
