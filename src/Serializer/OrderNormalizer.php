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
            $normalized["maker"]["profile"]["image"] = [];
            $normalized["maker"]["profile"]["image"]["url"] = Image::DEFAULT_PROFILE_IMAGE_URL;
            $normalized["maker"]["profile"]["image"]["avatar_small"] = Image::DEFAULT_PROFILE_IMAGE_URL;
            $normalized["maker"]["profile"]["image"]["avatar_medium"] = Image::DEFAULT_PROFILE_IMAGE_URL;
            $normalized["maker"]["profile"]["image"]["avatar_large"] = Image::DEFAULT_PROFILE_IMAGE_URL;
        }

        if (!is_null($order->getTaker()) && $order->getTaker()->getProfile()->isAnonymous()) {
            $normalized["taker"]["profile"] = null;
            $normalized["taker"]["profile"]["nickname"] = "Anonymous";
            $normalized["taker"]["profile"]["image"] = [];
            $normalized["taker"]["profile"]["image"]["url"] = Image::DEFAULT_PROFILE_IMAGE_URL;
            $normalized["taker"]["profile"]["image"]["avatar_small"] = Image::DEFAULT_PROFILE_IMAGE_URL;
            $normalized["taker"]["profile"]["image"]["avatar_medium"] = Image::DEFAULT_PROFILE_IMAGE_URL;
            $normalized["taker"]["profile"]["image"]["avatar_large"] = Image::DEFAULT_PROFILE_IMAGE_URL;
        }

        return $normalized;
    }

    /** {@inheritDoc} */
    public function supportsNormalization($data, $format = null)
    {
        return $data instanceof Order;
    }
}
