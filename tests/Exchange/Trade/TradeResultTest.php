<?php declare(strict_types = 1);

namespace App\Tests\Exchange\Trade;

use App\Exchange\Trade\TradeResult;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class TradeResultTest extends TestCase
{
    public function testCreate(): void
    {
        $tradeResult = new TradeResult(1, $this->mockTranslator());
        $this->assertEquals(1, $tradeResult->getResult());
        $this->assertEquals('Order Created', $tradeResult->getMessage());
    }

    public function testException(): void
    {
        $this->expectException(\Throwable::class);
        new TradeResult(-999, $this->mockTranslator());
    }

    private function mockTranslator(): TranslatorInterface
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->with('place_order.created')->willReturn('Order Created');

        return $translator;
    }
}
