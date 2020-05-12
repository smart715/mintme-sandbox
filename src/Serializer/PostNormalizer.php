<?php declare(strict_types = 1);

namespace App\Serializer;

use App\Entity\Post;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class PostNormalizer implements NormalizerInterface
{
    /** @var ObjectNormalizer */
    private $normalizer;

    /** @var AuthorizationCheckerInterface */
    private $authorizationChecker;

    public function __construct(
        ObjectNormalizer $objectNormalizer,
        AuthorizationCheckerInterface $authorizationChecker
    ) {
        $this->normalizer = $objectNormalizer;
        $this->authorizationChecker = $authorizationChecker;
    }

    /** {@inheritdoc} */
    public function normalize($object, $format = null, array $context = array())
    {
        /** @var array $post */
        $post = $this->normalizer->normalize($object, $format, $context);

        $post['content'] = $this->authorizationChecker->isGranted('view', $object)
            ? $post['content']
            : null;

        return $post;
    }

    /** {@inheritdoc} */
    public function supportsNormalization($data, $format = null)
    {
        return $data instanceof Post;
    }
}
