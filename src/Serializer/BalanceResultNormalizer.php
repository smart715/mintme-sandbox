<?php

namespace App\Serializer;

use App\Entity\Token\Token;
use App\Exchange\Balance\Model\BalanceResultContainer;
use App\Repository\TokenRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class BalanceResultNormalizer implements NormalizerInterface
{
    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var ObjectNormalizer */
    private $normalizer;

    public function __construct(EntityManagerInterface $entityManager, ObjectNormalizer $normalizer)
    {
        $this->entityManager = $entityManager;
        $this->normalizer = $normalizer;
    }

    /**
     * {@inheritdoc}
     * @param BalanceResultContainer $object
     */
    public function normalize($object, $format = null, array $context = array())
    {
        $data = $this->normalizer->normalize($object, $format, $context);

        $result = [];

        foreach ($data['all'] as $name => $props) {
            $result[$this->getTokenFromHiddenName($name)->getName()] = $props;
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