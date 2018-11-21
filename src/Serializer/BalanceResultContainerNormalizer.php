<?php

namespace App\Serializer;

use App\Entity\Token\Token;
use App\Exchange\Balance\Model\BalanceResult;
use App\Exchange\Balance\Model\BalanceResultContainer;
use App\Manager\TokenManagerInterface;
use App\Repository\TokenRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class BalanceResultContainerNormalizer implements NormalizerInterface
{
    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var ObjectNormalizer */
    private $normalizer;

    /** @var TokenManagerInterface */
    private $tokenManager;

    public function __construct(
        EntityManagerInterface $entityManager,
        ObjectNormalizer $normalizer,
        TokenManagerInterface $tokenManager
    ) {
        $this->entityManager = $entityManager;
        $this->normalizer = $normalizer;
        $this->tokenManager = $tokenManager;
    }

    /**
     * {@inheritdoc}
     *
     * @param BalanceResultContainer $object
     */
    public function normalize($object, $format = null, array $context = array())
    {
        $result = [];
        $data = $object->getAll();

        array_walk($data, function (BalanceResult $balanceResult, string $key) use (&$result, $format, $context): void {
            $token = $this->getTokenFromHiddenName($key);

            $result[$token->getName()] = $this->tokenManager->getRealBalance(
                $token,
                $balanceResult
            );

            $result[$token->getName()] = $this->normalizer->normalize($result[$token->getName()], $format, $context);
            $result[$token->getName()]['fullname'] = $token->getFullname();
        });

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
        return $this->tokenManager->findByName($name) ??
            $this->getTokenRepository()->find($this->getIdFromName($name));
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
