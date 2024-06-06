<?php declare(strict_types = 1);

namespace App\Serializer;

use App\Entity\Crypto;
use App\Entity\Token\Token;
use App\Entity\TradableInterface;
use App\Utils\Converter\RebrandingConverterInterface;
use App\Utils\Converter\TokenNameConverterInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class TradableNormalizer implements NormalizerInterface
{
    private ObjectNormalizer $normalizer;
    private TokenNameConverterInterface $tokenNameConverter;
    private RebrandingConverterInterface $rebrandingConverter;
    private int $tokenSubunit;

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

    /**
     * {@inheritdoc}
     *
     * @param TradableInterface|Mixed $object
     */
    public function normalize($object, $format = null, array $context = array())
    {
        $isMarketStatus = in_array(MarketStatusNormalizer::GROUP_KEY, $context['groups'] ?? []);

        if ($isMarketStatus && $object instanceof Token) {
            $this->ignoreHolders($context);
        }

        /** @var array $tradable */
        $tradable = $this->normalizer->normalize($object, $format, $context);

        if (array_key_exists('groups', $context) && $context['groups']) {
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

        if (!$isMarketStatus && $object instanceof Token && $object->isDeployed()) {
            $tradable['networks'] = array_map(function ($deploy) use ($context) {
                $symbol = $deploy->getCrypto()->getSymbol();

                return in_array('dev', $context['groups'] ?? [])
                    ? $this->rebrandingConverter->convert($symbol)
                    : $symbol;
            }, $object->getDeploys());
        }

        return $tradable;
    }

    /** {@inheritdoc} */
    public function supportsNormalization($data, $format = null)
    {
        return $data instanceof TradableInterface;
    }

    private function ignoreHolders(array &$context): void
    {
        if (isset($context[ObjectNormalizer::IGNORED_ATTRIBUTES])) {
            $context[ObjectNormalizer::IGNORED_ATTRIBUTES][] = ['holdersCount'];
        } else {
            $context[ObjectNormalizer::IGNORED_ATTRIBUTES] = ['holdersCount'];
        }
    }
}
