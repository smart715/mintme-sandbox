<?php declare(strict_types = 1);

namespace App\Serializer;

use App\Entity\MarketStatus;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class MarketStatusNormalizer implements NormalizerInterface
{
    public const GROUP_KEY = "MARKET_STATUS";

    private ObjectNormalizer $normalizer;

    public function __construct(ObjectNormalizer $normalizer)
    {
        $this->normalizer = $normalizer;
    }

    /**
     * {@inheritdoc}
     *
     * @param MarketStatus $marketStatus
     */
    public function normalize($marketStatus, $format = null, array $context = [])
    {
        if (isset($context["groups"])) {
            $context["groups"][] = self::GROUP_KEY;
        } else {
            $context[] = self::GROUP_KEY;
        }

        /** @var array $normalized */
        $normalized = $this->normalizer->normalize($marketStatus, $format, $context);

        if (array_key_exists('groups', $context) && (in_array('APIv2', $context['groups']))) {
            $temp = $normalized['base'];
            $normalized['base'] = $normalized['quote'];
            $normalized['quote'] = $temp;
        }

        return $normalized;
    }

    /** {@inheritdoc} */
    public function supportsNormalization($data, $format = null, array $context = [])
    {
        return $data instanceof MarketStatus;
    }
}
