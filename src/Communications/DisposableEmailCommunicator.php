<?php declare(strict_types = 1);

namespace App\Communications;

use Symfony\Component\HttpFoundation\Request;

class DisposableEmailCommunicator implements DisposableEmailCommunicatorInterface
{
    /** @var RestRpcInterface */
    private $rpc;

    /** @var string */
    private $fileNameIndex;

    /** @var string */
    private $fileNameWildcard;

    public function __construct(RestRpcInterface $rpc, string $fileNameIndex, string $fileNameWildcard)
    {
        $this->rpc = $rpc;
        $this->fileNameIndex = $fileNameIndex;
        $this->fileNameWildcard = $fileNameWildcard;
    }

    public function fetchDomainsIndex(): array
    {
        $response = $this->rpc->send(
            $this->fileNameIndex,
            Request::METHOD_GET
        );
        $response = json_decode($response, true);

        return $response;
    }

    public function fetchDomainsWildcard(): array
    {
        $response = $this->rpc->send(
            $this->fileNameWildcard,
            Request::METHOD_GET
        );
        $response = json_decode($response, true);

        return $response;
    }
}
