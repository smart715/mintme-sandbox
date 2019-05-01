<?php declare(strict_types = 1);

namespace App\Serializer;

use App\Entity\Crypto;
use App\Entity\Token\Token;
use App\Entity\TradebleInterface;
use App\Utils\Converter\TokenNameConverterInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class TradableNormalizer implements NormalizerInterface
{
    /** @var ObjectNormalizer */
    private $normalizer;

    /** @var TokenNameConverterInterface */
    private $tokenNameConverter;

    /** @var int */
    private $tokenSubunit;

    public function __construct(
        ObjectNormalizer $objectNormalizer,
        TokenNameConverterInterface $tokenNameConverter,
        int $tokenSubunit
    ) {
        $this->normalizer = $objectNormalizer;
        $this->tokenNameConverter = $tokenNameConverter;
        $this->tokenSubunit = $tokenSubunit;
    }

    /** {@inheritdoc} */
    public function normalize($object, $format = null, array $context = array())
    {
        $tradable = $this->normalizer->normalize($object, $format, $context);

        $tradable['identifier'] = $object instanceof Token ?
            $this->tokenNameConverter->convert($object) :
            $object->getSymbol();

        $tradable['subunit'] = $object instanceof Crypto ?
            $object->getShowSubunit() :
            $this->tokenSubunit;

        return $tradable;
    }

    /** {@inheritdoc} */
    public function supportsNormalization($data, $format = null)
    {
        return $data instanceof TradebleInterface;
    }
}
