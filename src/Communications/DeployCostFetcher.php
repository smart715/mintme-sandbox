<?php declare(strict_types = 1);

namespace App\Communications;

use App\Communications\Exception\FetchException;
use App\Exchange\Config\DeployCostConfig;
use App\Utils\Symbols;
use App\Wallet\Money\MoneyWrapperInterface;
use Money\Currency;
use Money\Exchange\FixedExchange;
use Money\Money;
use Symfony\Component\HttpFoundation\Request;

class DeployCostFetcher implements DeployCostFetcherInterface
{
    private const CRYPTO_IDS = [
        Symbols::WEB => 'webchain',
        Symbols::ETH => 'ethereum',
        Symbols::BNB => 'binancecoin',
    ];

    private RestRpcInterface $rpc;
    private DeployCostConfig $deployCostConfig;
    private MoneyWrapperInterface $moneyWrapper;

    public function __construct(
        RestRpcInterface $rpc,
        DeployCostConfig $deployCostConfig,
        MoneyWrapperInterface $moneyWrapper
    ) {
        $this->rpc = $rpc;
        $this->deployCostConfig = $deployCostConfig;
        $this->moneyWrapper = $moneyWrapper;
    }

    public function getDeployCost(string $symbol): Money
    {
        $ids = implode(',', self::CRYPTO_IDS);
        $response = $this->rpc->send(
            "simple/price?ids=${ids}&vs_currencies=usd",
            Request::METHOD_GET
        );

        $response = json_decode($response, true);

        foreach (self::CRYPTO_IDS as $cryptoId) {
            if (!isset($response[$cryptoId]['usd'])) {
                throw new FetchException();
            }
        }

        return $this->rate($symbol, $response);
    }

    public function getDeployCosts(): array
    {
        $ids = implode(',', self::CRYPTO_IDS);
        $response = $this->rpc->send(
            "simple/price?ids=${ids}&vs_currencies=usd",
            Request::METHOD_GET
        );

        $response = json_decode($response, true);

        foreach (self::CRYPTO_IDS as $cryptoId) {
            if (!isset($response[$cryptoId]['usd'])) {
                throw new FetchException();
            }
        }

        $costs = [];

        foreach (array_keys(self::CRYPTO_IDS) as $symbol) {
            $costs[$symbol] = $this->rate($symbol, $response);
        }

        return $costs;
    }

    /**
     * @return Money
     * @throws FetchException
     */
    public function getDeployCostReferralReward(string $symbol): Money
    {
        $deployCostReward = $this->deployCostConfig->getDeployCostReward($symbol);
        $deployWebCost = $this->getDeployCost($symbol);

        if ($deployCostReward > 0 && $deployWebCost->isPositive()) {
            return $deployWebCost->multiply($deployCostReward);
        }

        return new Money(0, new Currency($symbol));
    }

    private function rate(string $symbol, array $response): Money
    {
        return $this->moneyWrapper->convert(
            $this->moneyWrapper->parse((string)$this->deployCostConfig->getDeployCost($symbol), Symbols::USD),
            new Currency($symbol),
            new FixedExchange([
                Symbols::USD => [ $symbol => 1 / $response[self::CRYPTO_IDS[$symbol]]['usd'] ],
            ])
        );
    }
}
