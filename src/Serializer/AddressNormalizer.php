<?php declare(strict_types = 1);

namespace App\Serializer;

use App\Wallet\Model\Address;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class AddressNormalizer implements NormalizerInterface
{
    /**
     * {@inheritDoc}
     *
     * @param Address $address
     */
    public function normalize($address, $format = null, array $context = [])
    {
        return $address->getAddress();
    }

    /** {@inheritDoc} */
    public function supportsNormalization($data, $format = null)
    {
        return $data instanceof Address;
    }
}
