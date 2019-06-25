<?php declare(strict_types = 1);

namespace App\Tests\Exchange\Balance\Model;

use App\Exchange\Balance\Model\BalanceResult;
use PHPUnit\Framework\TestCase;

class BalanceResultTest extends TestCase
{
    public function testFail(): void
    {
        $res = BalanceResult::fail('FOO');

        $this->assertTrue($res->isFailed());
        $this->assertEquals(0, $res->getAvailable()->getAmount());
        $this->assertEquals(0, $res->getFreeze()->getAmount());
        $this->assertEquals(0, $res->getReferral()->getAmount());
    }
}
