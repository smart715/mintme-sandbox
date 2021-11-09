<?php declare(strict_types = 1);

namespace App\Serializer;

use App\Entity\Crypto;
use App\Entity\Token\Token;
use App\Entity\TradebleInterface;
use App\Utils\Converter\RebrandingConverterInterface;
use App\Utils\Converter\TokenNameConverterInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class TradableNormalizer implements NormalizerInterface
{
    /** @var ObjectNormalizer */
    private $normalizer;

    /** @var TokenNameConverterInterface */
    private $tokenNameConverter;

    /** @var RebrandingConverterInterface */
    private $rebrandingConverter;

    /** @var int */
    private $tokenSubunit;

    public function __construct(
        ObjectNormalizer $objectNormalizer,
        TokenNameConverterInterface $tokenNameConverter,
        RebrandingConverterInterface $rebrandingConverter,
        int $tokenSubunit
    ) {
        $this->normalizer = $objectNormalizer;
        $this->tokenNameConverter = $tokenNameConverter;
        $this->rebrandingConverter = $rebrandingConverter;
        $this->tokenSubunit = $tokenSubunit;
    }

    /** {@inheritdoc} */
    public function normalize($object, $format = null, array $context = array())
    {
        /** @var array $tradable */
        $tradable = $this->normalizer->normalize($object, $format, $context);

        if ($context['groups']) {
            if (in_array('Default', $context['groups']) || in_array('API', $context['groups'])) {
                $tradable['identifier'] = $object instanceof Token ?
                    $this->tokenNameConverter->convert($object) :
                    $object->getSymbol();

                $tradable['subunit'] = $object instanceof Crypto ?
                    $object->getShowSubunit() :
                    $this->tokenSubunit;
            }

            if (in_array('dev', $context['groups'])) {
                $tradable['name'] = $this->rebrandingConverter->convert($object->getName());
                $tradable['symbol'] = $this->rebrandingConverter->convert($object->getSymbol());
            }
        }

        return $tradable;
    }

    /** {@inheritdoc} */
    public function supportsNormalization($data, $format = null)
    {
        return $data instanceof TradebleInterface;
    }
}
