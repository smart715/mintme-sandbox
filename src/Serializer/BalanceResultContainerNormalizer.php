<?php

namespace App\Serializer;

use App\Entity\Token\Token;
use App\Entity\User;
use App\Exchange\Balance\Model\BalanceResultContainer;
use App\Repository\TokenRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class BalanceResultContainerNormalizer implements NormalizerInterface
{
    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var ObjectNormalizer */
    private $normalizer;

    /** @var mixed */
    private $user;

    public function __construct(
        EntityManagerInterface $entityManager,
        ObjectNormalizer $normalizer,
        TokenStorageInterface $storage
    ) {
        $this->entityManager = $entityManager;
        $this->normalizer = $normalizer;
        $this->user = $storage->getToken()->getUser();
    }

    /**
     * {@inheritdoc}
     *
     * @param BalanceResultContainer $object
     */
    public function normalize($object, $format = null, array $context = array())
    {
        $data = $this->normalizer->normalize($object, $format, $context);

        $result = [];

        foreach ($data['all'] as $name => $props) {
            $token = $this->getTokenFromHiddenName($name);

            if ($token->getProfile()->getUser() === $this->user) {
                $props['available'] -= $token->getLockIn()
                    ? $token->getLockIn()->getFrozenAmount()
                    : 0;
            }
            $result[$token->getName()] = $props;
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null)
    {
        return $data instanceof BalanceResultContainer;
    }

    private function getTokenFromHiddenName(string $name): Token
    {
        return $this->getTokenRepository()->find($this->getIdFromName($name));
    }

    private function getIdFromName(string $name): int
    {
        return (int) filter_var($name, FILTER_SANITIZE_NUMBER_INT);
    }

    private function getTokenRepository(): TokenRepository
    {
        return $this->entityManager->getRepository(Token::class);
    }
}
