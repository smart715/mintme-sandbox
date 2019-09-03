<?php declare(strict_types = 1);

namespace App\Communications;

use Symfony\Component\HttpFoundation\Request;

class DisposableEmailCommunicator implements DisposableEmailCommunicatorInterface
{
    /** @var RestRpcInterface */
    private $rpc;

    /** @var string */
    protected $disposableApiLink;

    public function __construct(string $disposableApiLink, RestRpcInterface $rpc)
    {
        $this->rpc = $rpc;
        $this->disposableApiLink = $disposableApiLink;
    }

    /**
     *@param string $email
     *@return bool
     */
    public function checkDisposable(string $email): bool
    {
        $domain = substr($email, strrpos($email, '@') + 1);
        $response = $this->rpc->send(
            $this->disposableApiLink.$domain,
            Request::METHOD_GET
        );
        $response = json_decode($response, true);

        return $response['disposable'];
    }
}
