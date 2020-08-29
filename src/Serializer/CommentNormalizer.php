<?php declare(strict_types = 1);

namespace App\Serializer;

use App\Entity\Comment;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class CommentNormalizer implements NormalizerInterface
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
        $comment = $this->normalizer->normalize($object, $format, $context);

        $comment['editable'] = $this->authorizationChecker->isGranted('edit', $object);

        return $comment;
    }

    /** {@inheritdoc} */
    public function supportsNormalization($data, $format = null)
    {
        return $data instanceof Comment;
    }
}
