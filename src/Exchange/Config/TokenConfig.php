<?php declare(strict_types = 1);

namespace App\Exchange\Config;

use App\Utils\Symbols;
use App\Wallet\Money\MoneyWrapperInterface;
use Money\Money;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class TokenConfig
{
    private ParameterBagInterface $parameterBag;
    private MoneyWrapperInterface $moneyWrapper;

    public function __construct(ParameterBagInterface $parameterBag, MoneyWrapperInterface $moneyWrapper)
    {
        $this->parameterBag = $parameterBag;
        $this->moneyWrapper = $moneyWrapper;
    }

    public function getTokenQuantity(): Money
    {
        return $this->moneyWrapper->parse(
            (string)$this->parameterBag->get('token_quantity'),
            Symbols::TOK
        );
    }

    /**
     * @deprecated This method should not be here in TokenConfig
     */
    public function getCryptoInternalWithdrawFeeBySymbol(string $symbol, ?string $moneySymbol = null): ?Money
    {
        $fees = $this->parameterBag->get('crypto_internal_withdrawal_fees');

        if (!isset($fees[$symbol])) {
            return null;
        }

        return $this->moneyWrapper->parse(
            (string)$fees[$symbol],
            $moneySymbol ?? $symbol
        );
    }

    public function getInternalWithdrawFeeByCryptoSymbol(string $symbol, ?string $moneySymbol = null): ?Money
    {
        $fees = $this->parameterBag->get('token_internal_withdrawal_fees');

        if (!isset($fees[$symbol])) {
            return null;
        }

        return $this->moneyWrapper->parse(
            (string)$fees[$symbol],
            $moneySymbol ?? $symbol
        );
    }

    public function getExternalWithdrawalFeeByCryptoSymbol(string $symbol, ?string $moneySymbol = null): ?Money
    {
        $fees = $this->parameterBag->get('token_withdrawal_fees');

        if (!isset($fees[$symbol])) {
            return null;
        }

        return $this->moneyWrapper->parse(
            (string)$fees[$symbol],
            $moneySymbol ?? $symbol
        );
    }

    public function getWithdrawFeeByCryptoSymbol(
        string $symbol,
        bool $isInternalWithdraw = false,
        ?string $moneySymbol = null
    ): ?Money {
        $internalTokenFee = $this->getInternalWithdrawFeeByCryptoSymbol($symbol, $moneySymbol);

        return $isInternalWithdraw && null !== $internalTokenFee
            ? $internalTokenFee
            : $this->getExternalWithdrawalFeeByCryptoSymbol($symbol, $moneySymbol);
    }
}
