<?php

namespace App\Validator\Handler;

use Symfony\Contracts\HttpClient\HttpClient;

class DisposableEmailApiHandler
{
    /** @var HttpClient */
    protected $client;

    /** @var string */
    protected $disposableApiLink;

    public function __construct(string $disposableApiLink)
    {
        $this->client = HttpClient::create();
        $this->disposableApiLink = $disposableApiLink;
    }

    public function checkDisposable($email): bool
    {
        if (!is_string($email)) return true;

        $domain = substr($email, strrpos($email, '@')+1);
        $response = $this->client->request('GET', $this->disposableApiLink.$domain);
        $response = json_decode($response->getContent(), true);

        return $response['disposable'];
    }
}
