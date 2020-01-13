<?php

namespace App\Security;

use App\Entity\Api\AccessToken;
use OAuth2\OAuth2 as OAuth;


class OAuth2 extends OAuth
{
    /**
     * Return User by access token.
     *
     * @param string $token    access token.
     *
     * @return User   The value of the variable.
     */
    public function getUsernameForToken($token)
    {
        /** @var AccessToken $accessToken */
        $accessToken = $this->storage->getAccessToken($token);
        $client = $this->storage->getClient($accessToken->getClientId());

        return $client->getUser()->getUsername();
    }
}