<?php declare(strict_types = 1);

namespace App\Validator\Handler;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class DisposableEmailApiHandler
{
    /** @var HttpClientInterface */
    protected $client;

    /** @var string */
    protected $disposableApiLink;

    public function __construct(string $disposableApiLink)
    {
        $this->client = HttpClient::create();
        $this->disposableApiLink = $disposableApiLink;
    }

    /**
     * @param mixed $email
     *@return bool
     */
    public function checkDisposable($email): bool
    {
        if (!is_string($email)) {
            return false;
        }

        $domain = substr($email, strrpos($email, '@')+1);
        $response = $this->client->request('GET', $this->disposableApiLink.$domain);
        $response = json_decode($response->getContent(), true);

        return $response['disposable'];
    }
}
