<?php declare(strict_types = 1);

namespace App\Communications;

use Symfony\Component\HttpFoundation\Request;

class DiscordOAuthClient implements DiscordOAuthClientInterface
{
    private const ACCESS_TOKEN = 'token';
    private const AUTHORIZE = 'authorize';
    private string $clientId;
    private string $clientSecret;
    private string $baseAuthUrl;
    private RestRpcInterface $guzzleRestWrapper;

    public function __construct(
        string $clientId,
        string $clientSecret,
        string $baseAuthUrl,
        RestRpcInterface $guzzleRestWrapper
    ) {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->baseAuthUrl = $baseAuthUrl;
        $this->guzzleRestWrapper = $guzzleRestWrapper;
    }

    public function getAccessToken(string $code, string $redirectUrl): string
    {
        $response = $this->guzzleRestWrapper->send(self::ACCESS_TOKEN, Request::METHOD_POST, [
            'form_params' => [
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'code' => $code,
                'grant_type' => 'authorization_code',
                'redirect_uri' => $redirectUrl,
            ],
        ]);

        $response = \json_decode($response, true, 512, JSON_THROW_ON_ERROR);

        return $response['access_token'];
    }

    public function generateAuthUrl(
        string $scope,
        string $redirectUrl,
        ?int $permissions = null,
        ?string $state = null
    ): string {
        return $this->baseAuthUrl.self::AUTHORIZE.'?'.http_build_query([
                'client_id' => $this->clientId,
                'redirect_uri' => $redirectUrl,
                'scope' => $scope,
                'response_type' => 'code',
                'permissions' => $permissions,
                'state' => $state,
            ], '', '&', PHP_QUERY_RFC3986);
    }
}
