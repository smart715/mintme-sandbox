<?php declare(strict_types = 1);

namespace App\Manager;

use Abraham\TwitterOAuth\TwitterOAuth;
use App\Entity\User;
use App\Exception\InvalidTwitterTokenException;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class TwitterManager implements TwitterManagerInterface
{
    public const TWITTER_INVALID_TOKEN_ERROR = 89;
    public const TWEET_ALREADY_RETWEETED = 327;

    private SessionInterface $session;
    private TwitterOAuth $twitter;
    private LoggerInterface $logger;
    private EntityManagerInterface $entityManager;

    public function __construct(
        SessionInterface $session,
        TwitterOAuth $twitter,
        LoggerInterface $logger,
        EntityManagerInterface $entityManager
    ) {
        $this->session = $session;
        $this->twitter = $twitter;
        $this->logger = $logger;
        $this->entityManager = $entityManager;
    }

    /**
     * @throws InvalidTwitterTokenException
     * @throws \Throwable
     */
    public function sendTweet(?User $user, string $message): self
    {
        if ($user) {
            $this->checkSignedInWithTwitter($user)->authorizeUser($user);
        } else {
            $this->authorizeGuest();
        }

        try {
            $this->twitter->setApiVersion('2');

            /** @var object $response */
            $response = $this->twitter->post('tweets', ['text' => $message], true);
        } catch (\Throwable $e) {
            $this->logger->error("Failed to post message on twitter: {$e->getMessage()}");

            throw $e;
        }

        $this->errorHandler($user, $response);

        return $this;
    }

    /**
     * @throws InvalidTwitterTokenException
     * @throws \Throwable
     */
    public function retweet(?User $user, string $tweetId): self
    {
        if ($user) {
            $this->checkSignedInWithTwitter($user)->authorizeUser($user);
        } else {
            $this->authorizeGuest();
        }

        try {
            /** @var object $response */
            $response = $this->twitter->post("statuses/retweet/{$tweetId}");
        } catch (\Throwable $e) {
            $this->logger->error("Failed to post message on twitter: {$e->getMessage()}");

            throw $e;
        }

        $this->errorHandler($user, $response);

        return $this;
    }

    /**
     * @throws InvalidTwitterTokenException
     */
    private function checkSignedInWithTwitter(User $user): self
    {
        if (!$user->isSignedInWithTwitter()) {
            throw new InvalidTwitterTokenException();
        }

        return $this;
    }

    private function authorizeUser(User $user): self
    {
        $this->twitter->setOauthToken(
            $user->getTwitterAccessToken(),
            $user->getTwitterAccessTokenSecret(),
        );

        return $this;
    }
    
    /**
     * @throws InvalidTwitterTokenException
     */
    private function authorizeGuest(): void
    {
        $oauthToken = $this->session->get('twitter_oauth_token');
        $oauthTokenSecret = $this->session->get('twitter_oauth_token_secret');

        if (!$oauthToken || !$oauthTokenSecret) {
            throw new InvalidTwitterTokenException();
        }

        $this->twitter->setOauthToken(
            $oauthToken,
            $oauthTokenSecret,
        );
    }

    /**
     * @throws InvalidTwitterTokenException
     * @throws \Exception
     */
    private function errorHandler(?User $user, object $response): void
    {
        /** @var array $errors */
        $errors = $response->errors ?? [];

        if (count($errors) > 0) {
            switch ($errors[0]->code) {
                case self::TWITTER_INVALID_TOKEN_ERROR: // expired or invalid access token
                    $this->checkUser($user);

                    throw new InvalidTwitterTokenException();
                case self::TWEET_ALREADY_RETWEETED:
                    return;
            }

            $this->logger->error("Failed to post message on twitter: {$errors[0]->message}");

            throw new \Exception($errors[0]->message);
        }
    }

    private function checkUser(?User $user): void
    {
        if ($user) {
            $user->setTwitterAccessToken(null)->setTwitterAccessTokenSecret(null);
            $this->entityManager->persist($user);
            $this->entityManager->flush();
        } else {
            $this->session->remove('twitter_oauth_token');
            $this->session->remove('twitter_oauth_token_secret');
        }
    }
}
