<?php declare(strict_types = 1);

namespace App\Serializer;

use App\Entity\Post;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class PostNormalizer implements NormalizerInterface
{
    private ObjectNormalizer $normalizer;
    private AuthorizationCheckerInterface $authorizationChecker;
    private TokenStorageInterface $tokenStorage;

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
        /** @var array $post */
        $post = $this->normalizer->normalize($object, $format, $context);

        $post['content'] = $this->authorizationChecker->isGranted('view', $object)
            ? $post['content']
            : null;

        $token = $this->tokenStorage->getToken();
        $user = $token
            ? $token->getUser()
            : null;

        $post['isUserAlreadyRewarded'] = $user instanceof User
            ? $object->isUserAlreadyRewarded($user)
            : false;

        return $post;
    }

    /** {@inheritdoc} */
    public function supportsNormalization($data, $format = null)
    {
        return $data instanceof Post;
    }
}
