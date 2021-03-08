<?php declare(strict_types = 1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

abstract class Controller extends AbstractController
{
    /** @var NormalizerInterface */
    private $normalizer;

    public function __construct(NormalizerInterface $normalizer)
    {
        $this->normalizer = $normalizer;
    }

    /**
     * @param mixed $object
     * @param array $groups
     * @return array|string|int|float|bool|\ArrayObject|null
     */
    protected function normalize($object, array $groups = ['Default'])
    {
        return $this->normalizer->normalize($object, null, [
            'groups' => $groups,
        ]);
    }
}
