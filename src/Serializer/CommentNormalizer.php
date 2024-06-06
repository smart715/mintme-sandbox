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

    /**
     * {@inheritdoc}
     *
     * @param Comment $object
     */
    public function normalize($object, $format = null, array $context = array())
    {
        /** @var array $comment */
        $comment = $this->normalizer->normalize($object, $format, $context);

        $comment['content'] = $this->authorizationChecker->isGranted('view', $object->getPost())
            ? $comment['content']
            : null;

        $comment['editable'] = $this->authorizationChecker->isGranted('edit', $object);
        $comment['deletable'] = $this->authorizationChecker->isGranted('delete', $object);

        $token = $this->tokenStorage->getToken();
        $user = $token
            ? $token->getUser()
            : null;

        $comment['liked'] = $user instanceof User && $object->getLikedBy($user);

        $tips = [];
        $isTipped = false;

        if (count($object->getTips())) {
            foreach ($object->getTips() as $key => $tip) {
                $tips[$key] = $this->normalizer->normalize($tip, $format, ['groups' => ['API_BASIC']]);

                if ($user && $tip->getUser() == $user) {
                    $isTipped = true;
                }
            }
        }

        $comment['tips'] = $tips;
        $comment['tipped'] = $isTipped;

        return $comment;
    }

    /** {@inheritdoc} */
    public function supportsNormalization($data, $format = null)
    {
        return $data instanceof Comment;
    }
}
