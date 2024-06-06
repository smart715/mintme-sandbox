<?php declare(strict_types = 1);

namespace App\Communications\GeckoCoin;

use App\Communications\Exception\FetchException;
use App\Communications\GeckoCoin\Config\GeckoCoinConfig;
use App\Communications\GeckoCoin\Model\SimplePrice;
use App\Communications\GuzzleRestWrapper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class GeckoCoinCommunicator implements GeckoCoinCommunicatorInterface
{
    private GeckoCoinConfig $geckoCoinConfig;
    private GuzzleRestWrapper $guzzleRestWrapper;

    public function __construct(
        GeckoCoinConfig $geckoCoinConfig,
        GuzzleRestWrapper $guzzleRestWrapper
    ) {
        $this->geckoCoinConfig = $geckoCoinConfig;
        $this->guzzleRestWrapper = $guzzleRestWrapper;
    }

    public function getSimplePrice(SimplePrice $simplePriceData): array
    {
        $path = $this->geckoCoinConfig->getSimplePriceMethod()
            . '?'
            . $simplePriceData->getQueriesString();

        $response = $this->guzzleRestWrapper->send($path, Request::METHOD_GET);

        return json_decode($response, true);
    }

    public function getCoinList(): array
    {
        $response = $this->guzzleRestWrapper->send(
            $this->geckoCoinConfig->getCoinListMethod(),
            Request::METHOD_GET,
            ['include_platform' => false]
        );

        return json_decode($response, true);
    }

    public function fetchCryptoCirculatingSupply(string $symbol, CacheInterface $cache): string
    {
        try {
            $id = $cache->get("gecko_coin_id_{$symbol}", function (ItemInterface $item) use ($symbol) {
                $item->expiresAfter(60 * 60 * 24 * 365);

                return $this->searchCoin($symbol)['id'];
            });

            $response = $this->guzzleRestWrapper->send(
                "coins/{$id}",
                Request::METHOD_GET
            );
            $crypto = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable $e) {
            throw new FetchException('Invalid response from API: '. $e->getMessage());
        }

        return (string)$crypto['market_data']['circulating_supply'];
    }

    private function searchCoin(string $symbol): array
    {
        $response = $this->guzzleRestWrapper->send(
            "search?query={$symbol}",
            Request::METHOD_GET,
        );

        $response = json_decode($response, true, 512, JSON_THROW_ON_ERROR);

        $coins = $response['coins'];
        $splicedCoins = array_splice($coins, 0, 5); // API returns way too many results

        $symbol = strtolower($symbol);

        foreach ($splicedCoins as $coin) {
            if (strtolower($coin['symbol']) === $symbol ||
                strtolower($coin['name']) === $symbol ||
                strtolower($coin['id']) === $symbol
            ) {
                return $coin;
            }
        }

        throw new FetchException('Coin symbol not found');
    }
}
