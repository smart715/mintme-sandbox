<?php declare(strict_types = 1);

namespace App\Communications;

use App\Communications\Exception\FetchException;
use App\Entity\Crypto;
use App\Manager\CryptoManagerInterface;
use App\Utils\Symbols;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

class CryptoRatesFetcher implements CryptoRatesFetcherInterface
{
    private CryptoManagerInterface $cryptoManager;
    private RestRpcInterface $rpc;
    private ExternalServiceIdsMapperInterface $cryptoIdsMapper;
    private LoggerInterface $logger;

    public function __construct(
        CryptoManagerInterface $cryptoManager,
        RestRpcInterface $rpc,
        LoggerInterface $logger,
        ExternalServiceIdsMapperInterface $cryptoIdsMapper
    ) {
        $this->cryptoManager = $cryptoManager;
        $this->rpc = $rpc;
        $this->cryptoIdsMapper = $cryptoIdsMapper;
        $this->logger = $logger;
    }

    /** @inheritDoc */
    public function fetch(): array
    {
        $cryptos = $this->cryptoManager->findAllIndexed('name');

        $names = implode(',', array_map(function (Crypto $crypto) {
            return $this->getCryptoId($crypto);
        }, $cryptos));

        $symbols = implode(',', array_map(function ($crypto) {
            return str_replace(' ', '-', $crypto->getSymbol());
        }, $cryptos));

        $symbols .= ','.Symbols::USD;

        try {
            $response = $this->rpc->send("simple/price?ids={$names}&vs_currencies={$symbols}", Request::METHOD_GET);
            $response = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            /** @var mixed $response */
            $this->logger->error('Invalid response from API', ['response' => $response ?? null, 'exception' => $e]);

            throw new FetchException('Invalid response from API');
        }

        if (array_key_exists('error', $response)) {
            throw new FetchException($response['error']);
        }

        $keys = array_map(function ($key) use ($cryptos) {
            return $this->getCryptoKeyFromId(strval($key), $cryptos);
        }, array_keys($response));

        $values = array_map(function ($value) {
            return array_combine(
                array_map(fn($a) => strtoupper((string)$a), array_keys($value)),
                array_values($value)
            );
        }, array_values($response));

        return array_combine($keys, $values) ?: [];
    }

    private function getCryptoId(Crypto $crypto): string
    {
        return $this->cryptoIdsMapper->getCryptoId($crypto->getSymbol()) ?? str_replace(' ', '-', $crypto->getName());
    }

    private function getCryptoKeyFromId(string $cryptoId, array $cryptos): string
    {
        return $this->cryptoIdsMapper->getSymbolFromId($cryptoId) ?? $cryptos[ucfirst((string)$cryptoId)]->getSymbol();
    }
}
