<?php declare(strict_types = 1);

namespace App\Controller\API;

use Abraham\TwitterOAuth\TwitterOAuth;
use App\Controller\Traits\ViewOnlyTrait;
use App\Entity\User;
use App\Exception\ApiBadRequestException;
use App\Exception\ApiForbiddenException;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @Rest\Route("/api/twitter")
 */
class TwitterController extends AbstractFOSRestController
{
    private TwitterOAuth $twitter;
    protected SessionInterface $session;
    private EntityManagerInterface $entityManager;
    private LoggerInterface $logger;

    use ViewOnlyTrait;

    public function __construct(
        TwitterOAuth $twitter,
        SessionInterface $session,
        EntityManagerInterface $entityManager,
        LoggerInterface $logger
    ) {
        $this->twitter = $twitter;
        $this->session = $session;
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }

    /**
     * @Rest\View()
     * @Rest\Post("/request_token", name="twitter_request_token", options={"expose"=true})
     */
    public function requestToken(): View
    {
        if ($this->isViewOnly()) {
            throw new ApiForbiddenException('View only');
        }

        try {
            $response = $this->twitter->oauth(
                'oauth/request_token',
                ['oauth_callback' => $this->generateUrl('twitter_callback', ['_locale' => 'en'], UrlGeneratorInterface::ABSOLUTE_URL)]
            );
        } catch (\Throwable $e) {
            $this->logger->error("Failed to get request token for twitter: {$e->getMessage()}");

            throw new \Exception('Something went wrong');
        }

        $this->session->set('twitter_oauth_token', $response['oauth_token']);
        $this->session->set('twitter_oauth_token_secret', $response['oauth_token_secret']);

        return $this->view(
            ['url' => $this->twitter->url('oauth/authorize', ['oauth_token' => $response['oauth_token']])],
            Response::HTTP_ACCEPTED
        );
    }

    /**
     * @Rest\Get("/callback", name="twitter_callback")
     */
    public function callback(Request $request): Response
    {
        $twitterCallbackView = new Response(
            $this->render('pages/twitter_callback.html.twig'),
            Response::HTTP_OK,
            ['Content-Type' => 'text/html']
        );

        if ($request->get('denied', false)) {
            return $twitterCallbackView;
        }

        $oauth_token = $this->session->get('twitter_oauth_token');
        $oauth_token_secret = $this->session->get('twitter_oauth_token_secret');

        if (!$oauth_token || $oauth_token !== $request->get('oauth_token')) {
            throw new ApiBadRequestException();
        }

        $this->twitter->setOauthToken($oauth_token, $oauth_token_secret);

        $response = $this->twitter->oauth("oauth/access_token", ["oauth_verifier" => $request->get('oauth_verifier')]);

        /** @var User|null $user */
        $user = $this->getUser();

        if ($user) {
            $user
                ->setTwitterAccessToken($response['oauth_token'])
                ->setTwitterAccessTokenSecret($response['oauth_token_secret']);

            $this->entityManager->persist($user);
            $this->entityManager->flush();
        } else {
            $this->session->remove('twitter_oauth_token');
            $this->session->remove('twitter_oauth_token_secret');

            $this->session->set('twitter_oauth_token', $response['oauth_token']);
            $this->session->set('twitter_oauth_token_secret', $response['oauth_token_secret']);
        }

        return $twitterCallbackView;
    }

    /**
     * @Rest\Get("/check", name="check_twitter", options={"expose"=true})
     */
    public function check(): View
    {
        if ($this->isViewOnly()) {
            throw new ApiForbiddenException('View only');
        }

        /** @var User|null $user */
        $user = $this->getUser();

        $isSignedIn = $user
            ? $user->isSignedInWithTwitter()
            : $this->session->has('twitter_oauth_token') && $this->session->has('twitter_oauth_token_secret');

        return $this->view(['isSignedInWithTwitter' => $isSignedIn]);
    }
}
