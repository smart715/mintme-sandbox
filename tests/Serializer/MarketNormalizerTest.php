<?php declare(strict_types = 1);

namespace App\Tests\Serializer;

use App\Exchange\Market;
use App\Serializer\MarketNormalizer;
use App\Utils\Converter\MarketNameConverterInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class MarketNormalizerTest extends TestCase
{
    public function testNormalizeWithExpectedContextArray(): void
    {
        $normalizer = new MarketNormalizer(
            $this->mockObjectNormalizer(),
            $this->mockMarketNameConverter(),
        );

        $market = $this->mockMarket();

        $this->assertEquals(
            [
                'identifier' => 'test',
            ],
            $normalizer->normalize($market, null, ['groups' => [
                'Default',
                'API',
            ]])
        );
    }

    public function testNormalizeWithUnExpectedContextArray(): void
    {
        $normalizer = new MarketNormalizer(
            $this->mockObjectNormalizer(),
            $this->mockMarketNameConverter(),
        );

        $market = $this->mockMarket();

        $this->assertEquals(
            [],
            $normalizer->normalize($market, null, ['groups' => []])
        );
    }

    public function testSupportsNormalizationSuccess(): void
    {
        $normalizer = new MarketNormalizer(
            $this->mockObjectNormalizer(),
            $this->mockMarketNameConverter(),
        );

        $market = $this->mockMarket();


        $this->assertTrue($normalizer->supportsNormalization($market));
    }

    public function testSupportsNormalizationFailure(): void
    {
        $normalizer = new MarketNormalizer(
            $this->mockObjectNormalizer(),
            $this->mockMarketNameConverter(),
        );

        $this->assertFalse($normalizer->supportsNormalization(new \stdClass()));
    }

    private function mockObjectNormalizer(): ObjectNormalizer
    {
        $objectNormalizer = $this->createMock(ObjectNormalizer::class);
        $objectNormalizer->method('normalize')->willReturn([]);

        return $objectNormalizer;
    }

    private function mockMarketNameConverter(): MarketNameConverterInterface
    {
        $marketNameConverter = $this->createMock(MarketNameConverterInterface::class);
        $marketNameConverter->method('convert')->willReturn('test');

        return $marketNameConverter;
    }

    private function mockMarket(): Market
    {
        return $this->createMock(Market::class);
    }
}
