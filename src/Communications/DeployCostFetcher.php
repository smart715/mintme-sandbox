<?php declare(strict_types = 1);

namespace App\Communications;

use App\Communications\Exception\FetchException;
use App\Entity\Token\Token;
use App\Wallet\Money\MoneyWrapper;
use App\Wallet\Money\MoneyWrapperInterface;
use Money\Currency;
use Money\Exchange\FixedExchange;
use Money\Money;
use Symfony\Component\HttpFoundation\Request;

class DeployCostFetcher implements DeployCostFetcherInterface
{
    /** @var RestRpcInterface */
    private $rpc;

    /** @var int */
    private $usdCost;

    /** @var MoneyWrapperInterface */
    private $moneyWrapper;

    public function __construct(RestRpcInterface $rpc, int $usdCost, MoneyWrapperInterface $moneyWrapper)
    {
        $this->rpc = $rpc;
        $this->usdCost = $usdCost;
        $this->moneyWrapper = $moneyWrapper;
    }

    public function getDeployWebCost(): Money
    {
        $response = $this->rpc->send(
            'simple/price?ids=webchain&vs_currencies=usd',
            Request::METHOD_GET
        );

        $response = json_decode($response, true);

        if (!isset($response['webchain']['usd'])) {
            throw new FetchException();
        }

        return $this->moneyWrapper->convert(
            Money::USD($this->usdCost),
            new Currency(Token::WEB_SYMBOL),
            new FixedExchange([
                MoneyWrapper::USD_SYMBOL => [ Token::WEB_SYMBOL => 1 / $response['webchain']['usd'] ],
            ])
        );
    }
}
