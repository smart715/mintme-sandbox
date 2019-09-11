<?php declare(strict_types = 1);

namespace App\Communications;

use Symfony\Component\HttpFoundation\Request;

class DisposableEmailCommunicator implements DisposableEmailCommunicatorInterface
{
    /** @var RestRpcInterface */
    private $rpc;

    public function __construct(RestRpcInterface $rpc)
    {
        $this->rpc = $rpc;
    }

    public function checkDisposable(?string $email): bool
    {
        if (!is_null($email)) {
            return false;
        }

        $domain = substr($email, strrpos($email, '@') + 1);
        $response = $this->rpc->send(
            $domain,
            Request::METHOD_GET
        );
        $response = json_decode($response, true);
        $response = $response['disposable'];

        return $response;
    }
}
