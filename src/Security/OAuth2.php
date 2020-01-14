<?php declare(strict_types = 1);

namespace App\Security;

use App\Entity\Api\AccessToken;
use App\Entity\Api\Client;
use OAuth2\OAuth2 as OAuth;

class OAuth2 extends OAuth
{
    /**
     * Return User by access token.
     *
     * @param string $token    access token.
     * @return string   User name
     */
    public function getUsernameForToken(string $token): string
    {
        /** @var AccessToken $accessToken */
        $accessToken = $this->storage->getAccessToken($token);
        /** @var Client $client */
        $client = $this->storage->getClient($accessToken->getClientId());

        return $client->getUser()->getUsername();
    }
}
