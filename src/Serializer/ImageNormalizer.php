<?php declare(strict_types = 1);

namespace App\Serializer;

use App\Entity\Image;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class ImageNormalizer implements NormalizerInterface
{
    private ObjectNormalizer $normalizer;
    private CacheManager $cacheManager;

    public function __construct(ObjectNormalizer $normalizer, CacheManager $cacheManager)
    {
        $this->normalizer = $normalizer;
        $this->cacheManager = $cacheManager;
    }

    /**
     * {@inheritdoc}
     *
     * @param Image $image
     */
    public function normalize($image, $format = null, array $context = array())
    {
        /** @var array $normalized */
        $normalized = $this->normalizer->normalize($image, $format, $context);

        if ((is_string($context['groups']) && 'API_BASIC' === $context['groups'])
            || (is_array($context['groups']) && in_array('API_BASIC', $context['groups']))
        ) {
            $normalized['avatar_small'] = $this->cacheManager->generateUrl($image->getUrl(), 'avatar_small');
            $normalized['avatar_large'] = $this->cacheManager->generateUrl($image->getUrl(), 'avatar_large');
        } elseif (is_array($context['groups']) &&
            (in_array('Default', $context['groups']) || in_array('API', $context['groups']))) {
            $normalized['avatar_small'] = $this->cacheManager->generateUrl($image->getUrl(), 'avatar_small');
            $normalized['avatar_middle'] = $this->cacheManager->generateUrl($image->getUrl(), 'avatar_middle');
            $normalized['avatar_large'] = $this->cacheManager->generateUrl($image->getUrl(), 'avatar_large');
        }

        return $normalized;
    }

    /** {@inheritdoc} */
    public function supportsNormalization($data, $format = null)
    {
        return $data instanceof Image;
    }
}
