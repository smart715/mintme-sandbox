<?php

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
     * @return array|bool|float|int|string
     */
    protected function normalize($object)
    {
        return $this->normalizer->normalize($object, null, [
            'groups' => ['Default'],
        ]);
    }
}
