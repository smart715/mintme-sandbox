<?php declare(strict_types = 1);

namespace App\Manager;

use Abraham\TwitterOAuth\TwitterOAuth;
use App\Entity\User;
use App\Exception\InvalidTwitterTokenException;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class TwitterManager implements TwitterManagerInterface
{
    public const TWITTER_INVALID_TOKEN_ERROR = 89;
    public const TWEET_ALREADY_RETWEETED = 327;

    private TwitterOAuth $twitter;
    private LoggerInterface $logger;
    private EntityManagerInterface $entityManager;

    public function __construct(
        TwitterOAuth $twitter,
        LoggerInterface $logger,
        EntityManagerInterface $entityManager
    ) {
        $this->twitter = $twitter;
        $this->logger = $logger;
        $this->entityManager = $entityManager;
    }

    /**
     * @throws InvalidTwitterTokenException
     * @throws \Throwable
     */
    public function sendTweet(User $user, string $message): self
    {
        $this->checkSignedInWithTwitter($user)->authorizeUser($user);

        try {
            /** @var object $response */
            $response = $this->twitter->post('statuses/update', ['status' => $message]);
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
    public function retweet(User $user, string $tweetId): self
    {
        $this->checkSignedInWithTwitter($user)->authorizeUser($user);

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
     * @throws \Exception
     */
    private function errorHandler(User $user, object $response): void
    {
        /** @var array $errors */
        $errors = $response->errors ?? []; // @phpstan-ignore-line

        if (count($errors) > 0) {
            switch ($errors[0]->code) {
                case self::TWITTER_INVALID_TOKEN_ERROR: // expired or invalid access token
                    $user->setTwitterAccessToken(null)->setTwitterAccessTokenSecret(null);
                    $this->entityManager->persist($user);
                    $this->entityManager->flush();

                    throw new InvalidTwitterTokenException();
                case self::TWEET_ALREADY_RETWEETED:
                    return;
            }

            $this->logger->error("Failed to post message on twitter: {$errors[0]->message}");

            throw new \Exception($errors[0]->message);
        }
    }
}
