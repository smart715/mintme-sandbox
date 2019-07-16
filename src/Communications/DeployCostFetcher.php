<?php declare(strict_types = 1);

namespace App\Communications;

use App\Communications\Exception\FetchException;
use Symfony\Component\HttpFoundation\Request;

class DeployCostFetcher implements DeployCostFetcherInterface
{
    /** @var RestRpcInterface */
    private $rpc;

    /** @var int */
    private $usdCost;

    public function __construct(RestRpcInterface $rpc, int $usdCost)
    {
        $this->rpc = $rpc;
        $this->usdCost = $usdCost;
    }

    public function getDeployWebCost(): string
    {
        $response = $this->rpc->send(
            'simple/price?ids=webchain&vs_currencies=usd',
            Request::METHOD_GET
        );

        $response = json_decode($response, true);

        if (!isset($response['webchain']['usd'])) {
            throw new FetchException();
        }

        $usdPerWeb = $response['webchain']['usd'];

        bcscale(8);

        return bcdiv((string)$this->usdCost, (string)$usdPerWeb);
    }
}
