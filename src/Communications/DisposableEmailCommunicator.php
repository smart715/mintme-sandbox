<?php declare(strict_types = 1);

namespace App\Communications;

use Symfony\Component\HttpFoundation\Request;

class DisposableEmailCommunicator implements DisposableEmailCommunicatorInterface
{
    /** @var RestRpcInterface */
    private $rpc;

    /** @var string */
    private $fileName;

    public function __construct(RestRpcInterface $rpc, string $fileName)
    {
        $this->rpc = $rpc;
        $this->fileName = $fileName;
    }

    public function fetchDomains(): array
    {
        $response = $this->rpc->send(
            $this->fileName,
            Request::METHOD_GET
        );
        $response = json_decode($response, true);

        return $response;
    }
}
