<?php declare(strict_types = 1);

namespace App\Serializer;

use App\Utils\Symbols;
use App\Wallet\Money\MoneyWrapperInterface;
use Money\Currency;
use Money\Money;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class MoneyNormalizer implements NormalizerInterface
{
    /** @var MoneyWrapperInterface */
    private $moneyWrapper;

    public function __construct(MoneyWrapperInterface $moneyWrapper)
    {
        $this->moneyWrapper = $moneyWrapper;
    }

    /**
     * {@inheritdoc}
     *
     * @param Money $object
     */
    public function normalize($object, $format = null, array $context = array()): string
    {
        if (!$this->moneyWrapper->getRepository()->contains($object->getCurrency())) {
            $object = new Money($object->getAmount(), new Currency(Symbols::TOK));
        }

        return $this->moneyWrapper->format($object);
    }

    /** {@inheritdoc} */
    public function supportsNormalization($data, $format = null): bool
    {
        return $data instanceof Money;
    }
}
