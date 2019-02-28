<?php

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
        $name = $this->marketNameConverter->convert($market);

        $normalized['hiddenName'] = $name;

        return $normalized;
    }

    /** {@inheritdoc} */
    public function supportsNormalization($data, $format = null)
    {
        return $data instanceof Market;
    }
}
