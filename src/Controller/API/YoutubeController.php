<?php declare(strict_types = 1);

namespace App\Controller\API;

use App\Manager\YoutubeManager;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @Rest\Route("/api/youtube")
 */
class YoutubeController extends AbstractFOSRestController
{
    private YoutubeManager $youtubeManager;

    private SessionInterface $session;

    public function __construct(YoutubeManager $youtubeManager, SessionInterface $session)
    {
        $this->youtubeManager = $youtubeManager;
        $this->session = $session;
    }

    /**
     * @Rest\Get("/request-token", name="youtube_request_token", options={"expose"=true})
     */
    public function requestToken(): View
    {
        $callback = $this->createCallbackUrl();
        $url = $this->youtubeManager->getAuthUrl($callback);

        return $this->view(['url' => $url]);
    }

    /**
     * @Rest\Get("/callback", name="youtube_callback", options={"expose"=true})
     */
    public function callback(Request $request): Response
    {
        $code = $request->get('code');

        $youtubeCallbackView = new Response(
            $this->renderView('pages/youtube_callback.html.twig'),
            Response::HTTP_OK,
            ['Content-Type' => 'text/html']
        );

        if ($code) {
            $this->youtubeManager->client->setRedirectUri($this->createCallbackUrl());
            $token = $this->youtubeManager->client->fetchAccessTokenWithAuthCode($code);
            $this->session->remove('youtube_access_token');
            $this->session->set('youtube_access_token', $token);
        }

        return $youtubeCallbackView;
    }

    /**
     * @Rest\Get("/token-expired", name="youtube_token_expired", options={"expose"=true})
     */
    public function isTokenExpired(): View
    {
        $this->youtubeManager->client->setAccessToken($this->session->get('youtube_access_token'));

        return $this->view(
            ['isExpired'=> $this->youtubeManager->client->isAccessTokenExpired()]
        );
    }

    /**
     * @Rest\Get("/channels/info/{channelsIdsJson}", name="youtube_channels_info", options={"expose"=true})
     */
    public function getChannelsInfo(string $channelsIdsJson): View
    {
        $channelsIds = json_decode($channelsIdsJson);

        return $this->view($this->youtubeManager->getChannelsInfo($channelsIds));
    }

    private function createCallbackUrl(): string
    {
        return $this->generateUrl(
            'youtube_callback',
            ['_locale' => 'en'],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
    }
}
