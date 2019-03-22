<?php

namespace App\Serializer;

use App\Entity\Token\Token;
use App\Exchange\Balance\Model\BalanceResult;
use App\Exchange\Balance\Model\BalanceResultContainer;
use App\Manager\TokenManagerInterface;
use App\Repository\TokenRepository;
use App\Utils\Converter\TokenNameConverterInterface;
use App\Wallet\Money\MoneyWrapperInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class BalanceResultContainerNormalizer implements NormalizerInterface
{

    /** @var ObjectNormalizer */
    private $normalizer;

    /** @var TokenManagerInterface */
    private $tokenManager;

    /** @var MoneyWrapperInterface */
    private $moneyWrapper;

    /** @var TokenNameConverterInterface */
    private $tokenNameConverter;

    public function __construct(
        ObjectNormalizer $normalizer,
        TokenManagerInterface $tokenManager,
        MoneyWrapperInterface $moneyWrapper,
        TokenNameConverterInterface $tokenNameConverter
    ) {
        $this->normalizer = $normalizer;
        $this->tokenManager = $tokenManager;
        $this->moneyWrapper = $moneyWrapper;
        $this->tokenNameConverter = $tokenNameConverter;
    }

    /**
     * {@inheritdoc}
     *
     * @param BalanceResultContainer $object
     */
    public function normalize($object, $format = null, array $context = array()): array
    {
        $result = [];
        $data = $object->getAll();

        array_walk($data, function (BalanceResult $balanceResult, string $key) use (&$result, $format, $context): void {
            $token = $this->tokenManager->findByName($key) ?? $this->tokenManager->findByHiddenName($key);

            if (!$token) {
                return;
            }

            $result[$token->getName()] = $this->tokenManager->getRealBalance(
                $token,
                $balanceResult
            );

            $result[$token->getName()] = $this->normalizer->normalize($result[$token->getName()], $format, $context);
            $result[$token->getName()]['identifier'] = $this->tokenNameConverter->convert($token);
            $result[$token->getName()]['frozen'] = $token->getLockIn() ? $this->moneyWrapper->format(
                $token->getLockIn()->getFrozenAmount()
            ) : 0;

            if ($token->getCrypto()) {
                $result[$token->getName()]['fullname'] = $token->getCrypto()->getName();
                $result[$token->getName()]['precision'] = $token->getCrypto()->getSubunit();
                $result[$token->getName()]['fee'] = $this->moneyWrapper->format(
                    $token->getCrypto()->getFee()
                );
            }
        });

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null): bool
    {
        return $data instanceof BalanceResultContainer;
    }
}
