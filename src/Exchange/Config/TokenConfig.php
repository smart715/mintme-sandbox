<?php declare(strict_types = 1);

namespace App\Exchange\Config;

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

    public function getWithdrawFeeByCryptoSymbol(string $symbol): Money
    {
        return $this->moneyWrapper->parse(
            (string)$this->parameterBag->get(strtolower($symbol) . '_token_withdraw_fee'),
            $symbol
        );
    }

    public function getBnbWithdrawFee(): Money
    {
        return $this->parameterBag->get('bnb_token_withdraw_fee');
    }

    public function getEthWithdrawFee(): Money
    {
        return $this->parameterBag->get('eth_token_withdraw_fee');
    }
}
