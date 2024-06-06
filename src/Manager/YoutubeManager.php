<?php declare(strict_types = 1);

namespace App\Manager;

use App\Utils\Youtube\Model\ChannelInfo;
use Google\Client;
use Google\Service\YouTube as Provider;
use Google\Service\YouTube\ResourceId as Resource;
use Google\Service\YouTube\Subscription;
use Google\Service\YouTube\SubscriptionSnippet as Snippet;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class YoutubeManager
{
    public const YOUTUBE_URL = [
        'https://www.googleapis.com/auth/youtube.readonly',
        'https://www.googleapis.com/auth/youtube',
    ];
    public const APPLICATION_NAME = 'Mintme';

    public const CHANNEL_KIND = 'youtube#channel';

    public Provider $provider;
    public Client $client;
    private SessionInterface $session;
    private LoggerInterface $logger;

    public function __construct(
        SessionInterface $session,
        LoggerInterface $logger,
        string $youtubeClientId,
        string $youtubeClientSecret,
        string $youtubeApiKey
    ) {
        $this->logger = $logger;
        $this->client = new Client();
        $this->client->setApplicationName(self::APPLICATION_NAME);
        $this->client->setScopes(self::YOUTUBE_URL);
        $this->client->setAccessType('offline');
        $this->client->setClientId($youtubeClientId);
        $this->client->setClientSecret($youtubeClientSecret);
        $this->client->setDeveloperKey($youtubeApiKey);
        $this->provider = new Provider($this->client);
        $this->session = $session;
    }

    public function getAuthUrl(string $url): string
    {
        try {
            $this->client->setRedirectUri($url);

            return $this->client->createAuthUrl();
        } catch (\Throwable $e) {
            $this->logger->info('Cannot get Auth url: ' . $e->getMessage());

            throw new \Exception('Something went wrong');
        }
    }

    public function checkIfSubscribed(string $channelId): bool
    {
        $this->client->setAccessToken($this->session->get('youtube_access_token'));

        $channel = $this->provider
            ->subscriptions
            ->listSubscriptions(
                'id,snippet',
                [
                    'mine' => 'true',
                    'forChannelId' => $channelId,
                ]
            );

        return count($channel->getItems()) > 0;
    }

    public function subscribe(string $channelId): ?string
    {
        $this->client->setAccessToken($this->session->get('youtube_access_token'));

        $subscription = $this->prepareSubscription($channelId);

        $response = $this->provider->subscriptions->insert('snippet', $subscription);

        return $response->getId() ?? null;
    }

    /**
     * @param array<string> $channelsIds
     * @return array<ChannelInfo>
     */
    public function getChannelsInfo(array $channelsIds): array
    {
        $validChannelsIds = [];
        $customUrls = [];

        foreach ($channelsIds as $channelId) {
            if ($this->isValidChannelId($channelId)) {
                $validChannelsIds[] = $channelId;
            } else {
                $customUrls[] = $channelId;
            }
        }

        return array_merge(
            $this->getChannelsInfoFromIds($validChannelsIds),
            $this->getChannelsInfoFromCustomUrl($customUrls)
        );
    }

    /**
     * @param array<string> $channelsIds
     * @return array<ChannelInfo>
     */
    private function getChannelsInfoFromIds(array $channelsIds): array
    {
        if (0 === count($channelsIds)) {
            return [];
        }

        $youtubeResponse = $this->provider
            ->channels
            ->listChannels(
                'snippet',
                [
                    'id' => $channelsIds,
                ]
            );

        if (!$channelsInfo = $youtubeResponse->getItems()) {
            return [];
        }

        $response = [];

        foreach ($channelsInfo as $channelInfo) {
            $channelSnippet = $channelInfo->getSnippet();
            $response[$channelInfo->id] = new ChannelInfo(
                $channelSnippet->getThumbnails()->getDefault()->getUrl(),
                $channelSnippet->getTitle(),
                $channelSnippet->getDescription()
            );
        }

        return $response;
    }

    /**
     * @param array<string> $customUrls
     * @return array<ChannelInfo>
     */
    private function getChannelsInfoFromCustomUrl(array $customUrls): array
    {
        if (0 === count($customUrls)) {
            return [];
        }

        $response = [];

        foreach ($customUrls as $customUrl) {
            $youtubeResponse = $this->provider
                ->search
                ->listSearch(
                    'snippet',
                    [
                        'maxResults' => 1,
                        'order' => 'relevance',
                        'q' => $customUrl,
                        'type' => 'channel',
                    ]
                );

            if ($channelInfo = $youtubeResponse->getItems()) {
                $channelSnippet = $channelInfo[0]->getSnippet();
                $response[$customUrl] = new ChannelInfo(
                    $channelSnippet->getThumbnails()->getDefault()->getUrl(),
                    $channelSnippet->getTitle(),
                    $channelSnippet->getDescription()
                );
            }
        }

        return $response;
    }

    private function isValidChannelId(string $channelId): bool
    {
        return 1 === preg_match('/^[a-zA-Z0-9_-]{24}$/', $channelId);
    }

    private function prepareSubscription(string $channelId): Subscription
    {
        $subscription = new Subscription();

        $subscriptionSnippet = new Snippet();
        $resourceId = new Resource();
        $resourceId->setChannelId($channelId);
        $resourceId->setKind('youtube#channel');
        $subscriptionSnippet->setResourceId($resourceId);
        $subscription->setSnippet($subscriptionSnippet);

        return $subscription;
    }
}
