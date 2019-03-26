<?php declare(strict_types = 1);

namespace App\Serializer;

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

    public function __construct(ObjectNormalizer $objectNormalizer, TokenNameConverterInterface $tokenNameConverter)
    {
        $this->normalizer = $objectNormalizer;
        $this->tokenNameConverter = $tokenNameConverter;
    }

    /** {@inheritdoc} */
    public function normalize($object, $format = null, array $context = array())
    {
        $tradable = $this->normalizer->normalize($object, $format, $context);

        $tradable['identifier'] = $object instanceof Token ?
            $this->tokenNameConverter->convert($object) :
            $object->getSymbol();
;
        $tradable['name'] = $this->tokenNameParser($object->getName());

        return $tradable;
    }

    public static function tokenNameParser(string $name): string
    {
        $name = trim($name, " -");
        $name = (string)preg_replace('/-+/', '-', $name);
        $name = (string)preg_replace('/\s+/', ' ', $name);
        return $name;
    }

    /** {@inheritdoc} */
    public function supportsNormalization($data, $format = null)
    {
        return $data instanceof TradebleInterface;
    }
}
