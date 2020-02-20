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
     * @return App\Controller\ArrayObject|array|bool|float|int|string|null
     */
    protected function normalize($object)
    {
        return $this->normalizer->normalize($object, null, [
            'groups' => ['Default'],
        ]);
    }
}
