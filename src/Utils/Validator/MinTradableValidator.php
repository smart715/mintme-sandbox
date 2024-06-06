<?php declare(strict_types = 1);

namespace App\Utils\Validator;

use App\Entity\Crypto;
use App\Entity\Token\Token;
use App\Entity\TradableInterface;
use App\Exchange\Market;
use App\Services\TranslatorService\TranslatorInterface;
use App\Utils\Converter\RebrandingConverterInterface;
use App\Wallet\Money\MoneyWrapperInterface;
use Money\Money;

class MinTradableValidator implements ValidatorInterface
{
    private TradableInterface $tradable;

    private string $amount;

    private string $message = ''; // phpcs:ignore

    private MoneyWrapperInterface $moneyWrapper;

    private TranslatorInterface $translator;

    private RebrandingConverterInterface $rebranding;

    private ?string $minimum;
    private Market $market;

    public function __construct(
        TradableInterface $tradable,
        Market $market,
        string $amount,
        ?string $minimum,
        MoneyWrapperInterface $moneyWrapper,
        TranslatorInterface $translator,
        RebrandingConverterInterface $rebranding
    ) {
        $this->tradable = $tradable;
        $this->market = $market;
        $this->amount = $amount;
        $this->minimum = $minimum;
        $this->moneyWrapper = $moneyWrapper;
        $this->translator = $translator;
        $this->rebranding = $rebranding;
    }

    public function validate(): bool
    {
        $minimum = $this->getMinimum();

        $amount = $this->moneyWrapper->parse($this->amount, $this->tradable->getMoneySymbol());

        if ($amount->lessThan($minimum)) {
            $this->message = $this->translator->trans(
                'trade.buy_order.amount_has_to_be',
                [
                    '%minTotalPrice%' => $this->moneyWrapper->format($minimum, false),
                    '%baseSymbol%' => $this->rebranding->convert($this->tradable->getSymbol()),
                ]
            );

            return false;
        }

        return true;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    private function getMinimum(): Money
    {
        if (null !== $this->minimum) {
            return $this->moneyWrapper->parse($this->minimum, $this->tradable->getMoneySymbol());
        }

        $quote = $this->market->getQuote();
        $decimals = $this->tradable instanceof Crypto && $quote instanceof Token && $quote->getPriceDecimals()
            ? $quote->getPriceDecimals()
            : $this->tradable->getShowSubunit();

        return $this->moneyWrapper->parse('1', $this->tradable->getMoneySymbol())
            ->divide(str_pad('1', $decimals + 1, '0'));
    }
}
