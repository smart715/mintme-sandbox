<?php declare(strict_types = 1);

namespace App\Serializer;

use App\Exchange\Order;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class OrderNormalizer implements NormalizerInterface
{
    /** @var ObjectNormalizer */
    private ObjectNormalizer $normalizer;

    public function __construct(ObjectNormalizer $normalizer)
    {
        $this->normalizer = $normalizer;
    }

    /**
     * {@inheritDoc}
     *
     * @param Order $order
     */
    public function normalize($order, $format = null, array $context = []): array
    {
        return $this->normalizer->normalize($order, $format, $context);
    }

    /** {@inheritDoc} */
    public function supportsNormalization($data, $format = null): bool
    {
        return $data instanceof Order;
    }
}
