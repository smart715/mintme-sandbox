<?php declare(strict_types = 1);

namespace App\Tests\Request\ParamConverter;

use App\Entity\TradebleInterface;
use App\Exchange\Market;
use App\Exchange\Market\MarketFinderInterface;
use App\Request\ParamConverter\MarketConverter;
use PHPUnit\Framework\TestCase;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class MarketConverterTest extends TestCase
{
    public function testApply(): void
    {
        $base = $this->createMock(TradebleInterface::class);
        $quote = $this->createMock(TradebleInterface::class);

        $market = $this->createMock(Market::class);
        $market->method('getBase')->willReturn($base);
        $market->method('getQuote')->willReturn($quote);

        $finder = $this->createMock(MarketFinderInterface::class);
        $finder->method('find')->willReturn($market);

        $req = $this->createMock(Request::class);
        $req->attributes = $this->createMock(ParameterBag::class);

        $converter = new MarketConverter($finder);
        $res = $converter->apply(
            $req,
            $this->createMock(ParamConverter::class)
        );
        $this->assertTrue($res);
    }

    public function testApplyFalse(): void
    {
        $finder = $this->createMock(MarketFinderInterface::class);
        $finder->method('find')->willReturn(null);

        $converter = new MarketConverter($finder);

        $this->expectException(NotFoundHttpException::class);

        $converter->apply(
            $this->createMock(Request::class),
            $this->createMock(ParamConverter::class)
        );
    }
}
