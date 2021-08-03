<?php declare(strict_types = 1);

namespace App\Serializer;

use App\Entity\DiscordRole;
use App\Form\DataTransformer\ColorTransformer;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class DiscordRoleNormalizer implements NormalizerInterface
{
    private ObjectNormalizer $normalizer;
    private ColorTransformer $colorTransformer;

    public function __construct(
        ObjectNormalizer $objectNormalizer,
        ColorTransformer $colorTransformer
    ) {
        $this->normalizer = $objectNormalizer;
        $this->colorTransformer = $colorTransformer;
    }

    /** {@inheritdoc} */
    public function normalize($object, $format = null, array $context = array())
    {
        /** @var array $role */
        $role = $this->normalizer->normalize($object, $format, $context);

        $role['color'] = $this->colorTransformer->transform($role['color']);
        $role['discordId'] = (string)$role['discordId'];

        return $role;
    }

    /** {@inheritdoc} */
    public function supportsNormalization($data, $format = null)
    {
        return $data instanceof DiscordRole;
    }
}
