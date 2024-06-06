<?php declare(strict_types = 1);

namespace App\Tests\Serializer;

use App\Entity\Image;
use App\Serializer\ImageNormalizer;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class ImageNormalizerTest extends TestCase
{
    public function testNormalizeWithNoGroups(): void
    {
        $image = $this->mockImage();
        $imageNormalizer = new ImageNormalizer(
            $this->mockObjectNormalizer(),
            $this->mockCacheManager(),
        );

        $normalizedImage = $imageNormalizer->normalize(
            $image,
            null,
            ['groups' => []]
        );

        $this->assertEquals(
            [],
            $normalizedImage
        );
    }

    public function testNormalizeWithDefaultGroup(): void
    {
        $image = $this->mockImage();
        $imageNormalizer = new ImageNormalizer(
            $this->mockObjectNormalizer(),
            $this->mockCacheManager(),
        );

        $normalizedImage = $imageNormalizer->normalize(
            $image,
            null,
            ['groups' => ['Default']]
        );

        $this->assertEquals(
            [
                'avatar_small' => 'https://www.test.test/test/',
                'avatar_middle' => 'https://www.test.test/test/',
                'avatar_large' => 'https://www.test.test/test/',
            ],
            $normalizedImage
        );
    }

    public function testNormalizeWithApiContextGroup(): void
    {
        $image = $this->mockImage();
        $imageNormalizer = new ImageNormalizer(
            $this->mockObjectNormalizer(),
            $this->mockCacheManager(),
        );

        $normalizedImage = $imageNormalizer->normalize(
            $image,
            null,
            ['groups' => ['API']]
        );

        $this->assertEquals(
            [
                'avatar_small' => 'https://www.test.test/test/',
                'avatar_middle' => 'https://www.test.test/test/',
                'avatar_large' => 'https://www.test.test/test/',
            ],
            $normalizedImage
        );
    }

    public function testNormalizeWithApiBasicGroup(): void
    {
        $image = $this->mockImage();
        $imageNormalizer = new ImageNormalizer(
            $this->mockObjectNormalizer(),
            $this->mockCacheManager(),
        );

        $normalizedImage = $imageNormalizer->normalize(
            $image,
            null,
            ['groups' => ['API_BASIC']]
        );


        $this->assertEquals(
            [
                'avatar_small' => 'https://www.test.test/test/',
                'avatar_large' => 'https://www.test.test/test/',
            ],
            $normalizedImage
        );
    }

    public function testNormalizeWithApiBasicContext(): void
    {
        $image = $this->mockImage();
        $imageNormalizer = new ImageNormalizer(
            $this->mockObjectNormalizer(),
            $this->mockCacheManager(),
        );

        $normalizedImage = $imageNormalizer->normalize(
            $image,
            null,
            ['groups' => 'API_BASIC']
        );

        $this->assertEquals(
            [
                'avatar_small' => 'https://www.test.test/test/',
                'avatar_large' => 'https://www.test.test/test/',
            ],
            $normalizedImage,
        );
    }

    public function testSupportsNormalizationSuccess(): void
    {
        $image = $this->mockImage();
        $imageNormalizer = new ImageNormalizer(
            $this->mockObjectNormalizer(),
            $this->mockCacheManager(),
        );

        $this->assertTrue($imageNormalizer->supportsNormalization($image));
    }

    public function testSupportsNormalizationFailure(): void
    {
        $notImage = new \stdClass();
        $imageNormalizer = new ImageNormalizer(
            $this->mockObjectNormalizer(),
            $this->mockCacheManager(),
        );

        $this->assertFalse($imageNormalizer->supportsNormalization($notImage));
    }

    private function mockObjectNormalizer(): ObjectNormalizer
    {
        $objectNormalizer = $this->createMock(ObjectNormalizer::class);
        $objectNormalizer->method('normalize')->willReturn([]);

        return $objectNormalizer;
    }

    private function mockCacheManager(): CacheManager
    {
        $cacheManager = $this->createMock(CacheManager::class);
        $cacheManager->method('generateUrl')->willReturn('https://www.test.test/test/');

        return $cacheManager;
    }

    private function mockImage(): Image
    {
        return $this->createMock(Image::class);
    }
}
