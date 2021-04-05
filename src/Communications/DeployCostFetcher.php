<?php declare(strict_types = 1);

namespace App\Communications;

use App\Communications\Exception\FetchException;
use App\Exchange\Config\DeployCostConfig;
use App\Utils\Symbols;
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

    /** @var DeployCostConfig */
    private $deployCostConfig;

    /** @var MoneyWrapperInterface */
    private $moneyWrapper;

    public function __construct(
        RestRpcInterface $rpc,
        DeployCostConfig $deployCostConfig,
        MoneyWrapperInterface $moneyWrapper
    ) {
        $this->rpc = $rpc;
        $this->deployCostConfig = $deployCostConfig;
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
            $this->moneyWrapper->parse((string)$this->deployCostConfig->getDeployCost(), Symbols::USD),
            new Currency(Token::WEB_SYMBOL),
            new FixedExchange([
                Symbols::USD => [ Symbols::WEB => 1 / $response['webchain']['usd'] ],
            ])
        );
    }

    public function getDeployCostReferralReward(): Money
    {
        $deployCostReward = $this->deployCostConfig->getDeployCostReward();
        $deployWebCost = $this->getDeployWebCost();

        if ($deployCostReward > 0 && $deployWebCost->isPositive()) {
            return $deployWebCost->multiply($deployCostReward);
        }

        return new Money(0, new Currency(Symbols::WEB));
    }
}
