<?php declare(strict_types = 1);

namespace App\Security\Model;

/** @codeCoverageIgnore */
class OAuthCredentials implements ApiAuthCredentialsInterface
{
    /** @var string */
    private $token;

    public function __construct(string $token)
    {
        $this->token = $token;
    }

    public function getToken(): string
    {
        return $this->token;
    }
}
