<?php declare(strict_types = 1);

namespace App\Tests\Serializer;

use App\Serializer\AddressNormalizer;
use App\Wallet\Model\Address;
use PHPUnit\Framework\TestCase;

class AddressNormalizerTest extends TestCase
{
    public function testNormalize(): void
    {
        $normalizer = new AddressNormalizer();
        $address = $this->createMock(Address::class);
        $address->expects($this->once())->method('getAddress')->willReturn('TEST');

        $this->assertEquals(
            'TEST',
            $normalizer->normalize($address)
        );
    }

    public function testSupportsNormalizationSuccess(): void
    {
        $normalizer = new AddressNormalizer();
        $address = $this->createMock(Address::class);

        $this->assertTrue($normalizer->supportsNormalization($address));
    }

    public function testSupportsNormalizationFailure(): void
    {
        $normalizer = new AddressNormalizer();

        $this->assertFalse($normalizer->supportsNormalization(new \stdClass()));
    }
}
