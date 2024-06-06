<?php declare(strict_types = 1);

namespace App\Tests\Serializer;

use App\Entity\MarketStatus;
use App\Serializer\MarketStatusNormalizer;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class MarketStatusNormalizerTest extends TestCase
{
    public function testNormalizeWithApiV2Group(): void
    {
        $marketStatusNormalizer = $this->getMarketStatusNormalizer();
        $marketStatus = $this->mockMarketStatus();

        $normalizedMarketStatus = $marketStatusNormalizer->normalize(
            $marketStatus,
            null,
            ['groups' => ['APIv2']]
        );

        $this->assertEquals(
            [
                'base' => 'TEST_QUOTE',
                'quote' => 'TEST_BASE',
            ],
            $normalizedMarketStatus
        );
    }

    public function testNormalizeWithEmptyContext(): void
    {
        $marketStatusNormalizer = $this->getMarketStatusNormalizer();
        $marketStatus = $this->mockMarketStatus();

        $normalizedMarketStatus = $marketStatusNormalizer->normalize($marketStatus);

        $this->assertEquals(
            [
                'base' => 'TEST_BASE',
                'quote' => 'TEST_QUOTE',
            ],
            $normalizedMarketStatus
        );
    }

    public function testSupportsNormalizationSuccess(): void
    {
        $marketStatusNormalizer = $this->getMarketStatusNormalizer();
        $marketStatus = $this->mockMarketStatus();

        $this->assertTrue($marketStatusNormalizer->supportsNormalization($marketStatus));
    }

    public function testSupportsNormalizationFailure(): void
    {
        $marketStatusNormalizer = $this->getMarketStatusNormalizer();
        $notMarketStatus = new \stdClass();

        $this->assertFalse($marketStatusNormalizer->supportsNormalization($notMarketStatus));
    }

    private function getMarketStatusNormalizer(): MarketStatusNormalizer
    {
        return new MarketStatusNormalizer(
            $this->mockObjectNormalizer()
        );
    }

    private function mockObjectNormalizer(): ObjectNormalizer
    {
        $objectNormalizer = $this->createMock(ObjectNormalizer::class);
        $objectNormalizer
            ->method('normalize')
            ->willReturnCallback(function ($object, $format, $context): array {
                $this->assertContains('MARKET_STATUS', $context['groups'] ?? $context);

                return ['base' => 'TEST_BASE', 'quote' => 'TEST_QUOTE'];
            });

        return $objectNormalizer;
    }

    private function mockMarketStatus(): MarketStatus
    {
        return $this->createMock(MarketStatus::class);
    }
}
