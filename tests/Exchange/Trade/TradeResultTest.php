<?php declare(strict_types = 1);

namespace App\Tests\Exchange\Trade;

use App\Exchange\Trade\TradeResult;
use PHPUnit\Framework\TestCase;

class TradeResultTest extends TestCase
{
    public function testCreate(): void
    {
        $tradeResult = new TradeResult(1);
        $this->assertEquals(1, $tradeResult->getResult());
        $this->assertEquals('Order Created', $tradeResult->getMessage());
    }

    public function testException(): void
    {
        $this->expectException(\Throwable::class);
        new TradeResult(-999);
    }
}
