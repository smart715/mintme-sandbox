<?php declare(strict_types = 1);

namespace App\Serializer;

use App\Entity\MarketStatus;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class MarketStatusNormalizer implements NormalizerInterface
{

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
        /** @var array $normalized */
        $normalized = $this->normalizer->normalize($marketStatus, $format, $context);

        if ($context['groups'] && (in_array('APIv2', $context['groups']))) {
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
