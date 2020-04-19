<?php declare(strict_types = 1);

namespace App\Serializer;

use App\Entity\Post;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Manager\TokenManagerInterface;
use Money\Money;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class PostNormalizer implements NormalizerInterface
{
    /** @var ObjectNormalizer */
    private $normalizer;

    /** @var TokenStorageInterface */
    private $tokenStorage;

    /** @var TokenManagerInterface */
    private $tokenManager;

    /** @var BalanceHandlerInterface */
    private $balanceHandler;

    public function __construct(
        ObjectNormalizer $objectNormalizer,
        TokenStorageInterface $tokenStorage,
        TokenManagerInterface $tokenManager,
        BalanceHandlerInterface $balanceHandler
    ) {
        $this->normalizer = $objectNormalizer;
        $this->tokenStorage = $tokenStorage;
        $this->tokenManager = $tokenManager;
        $this->balanceHandler = $balanceHandler;
    }

    /** {@inheritdoc} */
    public function normalize($object, $format = null, array $context = array())
    {
        $post = $this->normalizer->normalize($object, $format, $context);

        $showContent = false;
        $user = $this->getUser();

        if ($user) {
            $showContent = $this->checkBalance($user, $object->getToken(), $object->getAmount());
        }

        if (!$showContent) {
            $post['content'] = null;
        }

        return $post;
    }

    /** {@inheritdoc} */
    public function supportsNormalization($data, $format = null)
    {
        return $data instanceof Post;
    }

    private function getUser(): ?User
    {
        $user = $this->tokenStorage->getToken()->getUser();

        return $user instanceof User
            ? $user
            : null;
    }

    private function checkBalance(User $user, Token $token, Money $amount): bool
    {
        $available = $this->tokenManager->getRealBalance(
            $token,
            $this->balanceHandler->balance($user, $token)
        )->getAvailable();

        return $available->greaterThanOrEqual($amount);
    }
}
