<?php declare(strict_types = 1);

namespace App\Controller\API;

use Abraham\TwitterOAuth\TwitterOAuth;
use App\Entity\User;
use App\Exception\ApiBadRequestException;
use App\Exception\ApiUnauthorizedException;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Rest\Route("/api/twitter")
 */
class TwitterController extends AbstractFOSRestController
{
    private TwitterOAuth $twitter;
    private SessionInterface $session;
    private EntityManagerInterface $entityManager;
    private LoggerInterface $logger;
    private TranslatorInterface $translator;

    public function __construct(
        TwitterOAuth $twitter,
        SessionInterface $session,
        EntityManagerInterface $entityManager,
        LoggerInterface $logger,
        TranslatorInterface $translator
    ) {
        $this->twitter = $twitter;
        $this->session = $session;
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->translator = $translator;
    }

    /**
     * @Rest\View()
     * @Rest\Post("/request_token", name="twitter_request_token", options={"expose"=true})
     */
    public function requestToken(): View
    {
        if (!$this->getUser()) {
            throw new ApiUnauthorizedException('Unauthorized');
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
        /** @var User|null $user */
        $user = $this->getUser();

        if (!$user) {
            throw new ApiUnauthorizedException($this->translator->trans('api.tokens.unathorized'));
        }

        if ($request->get('denied', false)) {
            return $this->render('pages/twitter_callback.html.twig');
        }

        $oauth_token = $this->session->get('twitter_oauth_token');
        $oauth_token_secret = $this->session->get('twitter_oauth_token_secret');

        if (!$oauth_token || $oauth_token !== $request->get('oauth_token')) {
            throw new ApiBadRequestException();
        }

        $this->twitter->setOauthToken($oauth_token, $oauth_token_secret);

        $response = $this->twitter->oauth("oauth/access_token", ["oauth_verifier" => $request->get('oauth_verifier')]);

        $user->setTwitterAccessToken($response['oauth_token'])
            ->setTwitterAccessTokenSecret($response['oauth_token_secret']);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $this->render('pages/twitter_callback.html.twig');
    }

    /**
     * @Rest\Get("/check", name="check_twitter", options={"expose"=true})
     */
    public function check(): View
    {
        /** @var User|null $user */
        $user = $this->getUser();

        if (!$user) {
            throw new ApiUnauthorizedException($this->translator->trans('api.tokens.unathorized'));
        }

        return $this->view(['isSignedInWithTwitter' => $user->isSignedInWithTwitter()]);
    }
}
