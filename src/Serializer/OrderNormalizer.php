<?php declare(strict_types = 1);

namespace App\Serializer;

use App\Entity\Image;
use App\Exchange\Order;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class OrderNormalizer implements NormalizerInterface
{
    /** @var ObjectNormalizer */
    private $normalizer;

    public function __construct(ObjectNormalizer $normalizer)
    {
        $this->normalizer = $normalizer;
    }
    /**
     * {@inheritDoc}
     *
     * @param Order $order
     */
    public function normalize($order, $format = null, array $context = [])
    {
        $normalized = $this->normalizer->normalize($order, $format, $context);

        if ($order->getMaker()->getProfile()->isAnonymous()) {
            $normalized["maker"]["profile"] = null;
            $normalized["maker"]["profile"]["nickname"] = "Anonymous";
        }

        if (!is_null($order->getTaker()) && $order->getTaker()->getProfile()->isAnonymous()) {
            $normalized["taker"]["profile"] = null;
            $normalized["taker"]["profile"]["nickname"] = "Anonymous";
        }

        return $normalized;
    }

    /** {@inheritDoc} */
    public function supportsNormalization($data, $format = null)
    {
        return $data instanceof Order;
    }
}
