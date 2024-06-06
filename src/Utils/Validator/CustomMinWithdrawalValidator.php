<?php declare(strict_types = 1);

namespace App\Utils\Validator;

use App\Config\MinWithdrawalConfig;
use App\Entity\Crypto;
use App\Entity\TradableInterface;
use App\Services\TranslatorService\TranslatorInterface;
use App\Utils\Converter\RebrandingConverterInterface;
use App\Wallet\Money\MoneyWrapperInterface;
use Money\Money;

class CustomMinWithdrawalValidator implements ValidatorInterface
{
    private MoneyWrapperInterface $moneyWrapper;
    private TranslatorInterface $translator;
    private RebrandingConverterInterface $rebrandingConverter;
    private MinWithdrawalConfig $minWithdrawalConfig;
    private Money $amount;
    private TradableInterface $tradable;

    public function __construct(
        MoneyWrapperInterface $moneyWrapper,
        TranslatorInterface $translator,
        RebrandingConverterInterface $rebrandingConverter,
        MinWithdrawalConfig $minWithdrawalConfig,
        Money $amount,
        TradableInterface $tradable
    ) {
        $this->moneyWrapper = $moneyWrapper;
        $this->translator = $translator;
        $this->rebrandingConverter = $rebrandingConverter;
        $this->minWithdrawalConfig = $minWithdrawalConfig;
        $this->amount = $amount;
        $this->tradable = $tradable;
    }

    public function validate(): bool
    {
        $minValue = $this->minWithdrawalConfig->getMinWithdrawalByCryptoSymbol($this->tradable->getSymbol());

        if (is_null($minValue) || !$this->tradable instanceof Crypto) {
            return true;
        }

        $minValueMoney = $this->moneyWrapper->parse((string)$minValue, $this->tradable->getSymbol());

        return $this->amount->greaterThanOrEqual($minValueMoney);
    }

    public function getMessage(): string
    {
        $symbol = $this->tradable->getSymbol();

        return $this->translator->trans('withdraw_modal.min_withdraw', [
            '%minAmount%' => $this->minWithdrawalConfig->getMinWithdrawalByCryptoSymbol($symbol),
            '%currency%' => $this->rebrandingConverter->convert($symbol),
        ]);
    }
}
