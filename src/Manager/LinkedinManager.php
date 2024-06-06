<?php declare(strict_types = 1);

namespace App\Manager;

use League\OAuth2\Client\Provider\LinkedIn;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use League\OAuth2\Client\Token\LinkedInAccessToken;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class LinkedinManager
{
    public const SHARE_URL = 'https://api.linkedin.com/v2/shares';
    private LinkedIn $provider;
    private SessionInterface $session;
    
    public function __construct(
        RouterInterface $router,
        SessionInterface $session,
        string $linkedinClientId,
        string $linkedinClientSecret
    ) {
        $this->provider = new LinkedIn([
            'clientId' => $linkedinClientId,
            'clientSecret' => $linkedinClientSecret,
            'redirectUri' => $router->generate('linkedin_callback', [], UrlGeneratorInterface::ABSOLUTE_URL),
        ]);
        $this->session = $session;
    }

    public function redirectToLinkedin(): Response
    {
        $url = $this->provider->getAuthorizationUrl();
        $this->session->set('oauth2state', $this->provider->getState());

        return new RedirectResponse($url);
    }
    
    public function getUser(LinkedInAccessToken $token): ResourceOwnerInterface
    {
        return $this->provider->getResourceOwner($token);
    }

    public function setAccessToken(string $code): void
    {
        $token = $this->provider->getAccessToken('authorization_code', [
            'code' => $code,
        ]);

        $this->session->set('linkedin_access_token', $token);
    }

    public function getAccessToken(): LinkedInAccessToken
    {
        return $this->session->get('linkedin_access_token');
    }
    
    public function shareMessage(string $message, string $url): void
    {
        $token = $this->getAccessToken();
        $userLinkedIn = $this->getUser($token);

        $body = new \stdClass();
        $body->content = new \stdClass();
        $body->content->contentEntities[0] = new \stdClass();
        $body->text = new \stdClass();
        $body->content->contentEntities[0]->entityLocation = $url;
        $body->owner = 'urn:li:person:'.$userLinkedIn->getId();
        $body->text->text = $message;

        $body_json = json_encode($body, 128);

        $request = $this->provider->getRequest(
            'POST',
            self::SHARE_URL,
            [
                'headers' => [
                    "Authorization" => "Bearer " . $token,
                    "Content-Type"  => "application/json",
                    "X-Restli-Protocol-Version"=>"2.0.0",
                ],
                'body' => $body_json,
            ]
        );

        $this->provider->getParsedResponse($request);
    }
}
