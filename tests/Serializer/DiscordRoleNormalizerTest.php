<?php declare(strict_types = 1);

namespace App\Tests\Serializer;

use App\Entity\DiscordRole;
use App\Form\DataTransformer\ColorTransformer;
use App\Serializer\DiscordRoleNormalizer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class DiscordRoleNormalizerTest extends TestCase
{
    public function testNormalizeWithAUserThatLikedATheComment(): void
    {
        $normalizer = new DiscordRoleNormalizer(
            $this->mockObjectNormalizer(),
            $this->mockColorTransformer(),
        );

        $discordRole = $this->mockDiscordRole();

        $this->assertEquals(
            [
                'discordId' => 123,
                'color' => '#fff',
            ],
            $normalizer->normalize($discordRole)
        );
    }

    public function testSupportsNormalizationSuccess(): void
    {
        $normalizer = new DiscordRoleNormalizer(
            $this->mockObjectNormalizer(),
            $this->mockColorTransformer(),
        );

        $discordRole = $this->mockDiscordRole();

        $this->assertTrue($normalizer->supportsNormalization($discordRole));
    }

    public function testSupportsNormalizationFailure(): void
    {
        $normalizer = new DiscordRoleNormalizer(
            $this->mockObjectNormalizer(),
            $this->mockColorTransformer(),
        );


        $this->assertFalse($normalizer->supportsNormalization(new \stdClass()));
    }

    private function mockObjectNormalizer(): ObjectNormalizer
    {
        $objectNormalizer = $this->createMock(ObjectNormalizer::class);
        $objectNormalizer->method('normalize')->willReturn([
            'discordId' => 123,
            'color' => '#fff',
        ]);

        return $objectNormalizer;
    }

    private function mockColorTransformer(string $color = '#fff'): ColorTransformer
    {
        $colorTransformer = $this->createMock(ColorTransformer::class);
        $colorTransformer->method('transform')->willReturn($color);

        return $colorTransformer;
    }

    private function mockDiscordRole(): DiscordRole
    {
        return $this->createMock(DiscordRole::class);
    }
}
