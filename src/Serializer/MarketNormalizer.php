<?php declare(strict_types = 1);

namespace App\Serializer;

use App\Exchange\Market;
use App\Utils\Converter\MarketNameConverterInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class MarketNormalizer implements NormalizerInterface
{
    /** @var ObjectNormalizer */
    private $normalizer;

    /** @var MarketNameConverterInterface */
    private $marketNameConverter;

    public function __construct(ObjectNormalizer $normalizer, MarketNameConverterInterface $marketNameConverter)
    {
        $this->normalizer = $normalizer;
        $this->marketNameConverter = $marketNameConverter;
    }

    /**
     * {@inheritdoc}
     *
     * @param Market $market
     */
    public function normalize($market, $format = null, array $context = array())
    {
        $normalized = $this->normalizer->normalize($market, $format, $context);

        if ($context['groups'] &&
            (in_array('Default', $context['groups']) || in_array('API', $context['groups']))) {
            $normalized['identifier'] = $this->marketNameConverter->convert($market);
        }

        return $normalized;
    }

    /** {@inheritdoc} */
    public function supportsNormalization($data, $format = null)
    {
        return $data instanceof Market;
    }
}
