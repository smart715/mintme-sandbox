<?php declare(strict_types = 1);

namespace App\Serializer;

use App\Entity\Comment;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class CommentNormalizer implements NormalizerInterface
{
    /** @var ObjectNormalizer */
    private $normalizer;

    /** @var AuthorizationCheckerInterface */
    private $authorizationChecker;

    /** @var TokenStorageInterface */
    private $tokenStorage;

    public function __construct(
        ObjectNormalizer $objectNormalizer,
        AuthorizationCheckerInterface $authorizationChecker,
        TokenStorageInterface $tokenStorage
    ) {
        $this->normalizer = $objectNormalizer;
        $this->authorizationChecker = $authorizationChecker;
        $this->tokenStorage = $tokenStorage;
    }

    /** {@inheritdoc} */
    public function normalize($object, $format = null, array $context = array())
    {
        /** @var array $comment */
        $comment = $this->normalizer->normalize($object, $format, $context);

        $comment['editable'] = $this->authorizationChecker->isGranted('edit', $object);
        $comment['deletable'] = $this->authorizationChecker->isGranted('delete', $object);

        $token = $this->tokenStorage->getToken();
        $user = $token
            ? $token->getUser()
            : null;

        $comment['liked'] = $user instanceof User
            ? $object->getLikedBy($user)
            : false;

        return $comment;
    }

    /** {@inheritdoc} */
    public function supportsNormalization($data, $format = null)
    {
        return $data instanceof Comment;
    }
}
