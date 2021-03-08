<?php declare(strict_types = 1);

namespace App\Communications;

use App\Manager\CryptoManagerInterface;
use App\Utils\Symbols;
use App\Wallet\Money\MoneyWrapper;
use Symfony\Component\HttpFoundation\Request;

class CryptoRatesFetcher implements CryptoRatesFetcherInterface
{
    /** @var CryptoManagerInterface */
    private $cryptoManager;

    /** @var RestRpcInterface */
    private $rpc;

    public function __construct(
        CryptoManagerInterface $cryptoManager,
        RestRpcInterface $rpc
    ) {
        $this->cryptoManager = $cryptoManager;
        $this->rpc = $rpc;
    }

    public function fetch(): array
    {
        $cryptos = $this->cryptoManager->findAllIndexed('name');

        $names = implode(',', array_map(function ($crypto) {
            return str_replace(' ', '-', $crypto->getName());
        }, $cryptos));

        $symbols = implode(',', array_map(function ($crypto) {
            return str_replace(' ', '-', $crypto->getSymbol());
        }, $cryptos));

        $symbols .= ','.Symbols::USD;

        $response = $this->rpc->send("simple/price?ids={$names}&vs_currencies={$symbols}", Request::METHOD_GET);

        $response = json_decode($response, true);

        $keys = array_map(function ($key) use ($cryptos) {
            return 'usd-coin' === $key
                ? $cryptos['USD Coin']->getSymbol()
                : $cryptos[ucfirst((string)$key)]->getSymbol();
        }, array_keys($response));

        $values = array_map(function ($value) {
            return array_combine(
                array_map('strtoupper', array_keys($value)),
                array_values($value)
            );
        }, array_values($response));

        return array_combine($keys, $values) ?: [];
    }
}
