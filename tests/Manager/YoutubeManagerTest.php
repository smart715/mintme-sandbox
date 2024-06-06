<?php declare(strict_types = 1);

namespace App\Tests\Manager;

use App\Manager\YoutubeManager;
use Google\Service\YouTube\Subscription;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class YoutubeManagerTest extends TestCase
{
    private YoutubeManager $manager;
    
    public function setUp(): void
    {
        try {
            class_alias(
                'App\Tests\Manager\FakeProvider',
                'Google\Service\YouTube',
                true
            );
        } catch (\Throwable $e) {
            if (!str_contains($e->getMessage(), 'Cannot declare class')) {
                throw new \Exception($e->getMessage());
            }
        }

        /** @var SessionInterface|MockObject */
        $session = $this->mockSession();
        $session
            ->method('get')
            ->willReturn('1/fFAGRNJru1FTz70BzhT3Zg');

        $this->manager = new YoutubeManager(
            $session,
            $this->createMock(LoggerInterface::class),
            '123',
            '123',
            '123'
        );
    }

    public function testGetAuthUrl(): void
    {
        $expectedUrl = 'https://accounts.google.com/o/oauth2/v2/auth?response_type=code&access_type=offline&client_id=123&redirect_uri=https%3A%2F%2Flocalhost%2Fapi%2Fyoutube%2Fcallback&state&scope=https%3A%2F%2Fwww.googleapis.com%2Fauth%2Fyoutube.readonly%20https%3A%2F%2Fwww.googleapis.com%2Fauth%2Fyoutube&approval_prompt=auto';

        $actualUrl = $this->manager->getAuthUrl("https://localhost/api/youtube/callback");

        $this->assertEquals($expectedUrl, $actualUrl);
    }

    public function testSubscribe(): void
    {
        $subscrided = $this->manager->subscribe('channel123');
        $failed = $this->manager->subscribe('subscribe-failed');

        $this->assertEquals($subscrided, 'success123');
        $this->assertEquals($failed, null);
    }

    private function mockSession(): SessionInterface
    {
        return $this->createMock(SessionInterface::class);
    }
}

// @codingStandardsIgnoreStart
class FakeProvider
{
    public object $subscriptions;
    public string $subscriptionId;

    function __construct() {
        $this->subscriptionId = '';
        $this->subscriptions = new class ($this->subscriptionId) {

            public string $id;

            function __construct(string $subscriptionId) {
                $this->id = $subscriptionId;
            }

            function insert(
                string $snippet,
                Subscription $subscription
            ): object {
                $this->id = $subscription
                    ->getSnippet()
                    ->getResourceId()
                    ->getChannelId();
                return $this;
            }

            function getId(): ?string {
                if ($this->id == 'subscribe-failed') {
                    return null;
                }

                return 'success123';
            }
        };
    }
}
// @codingStandardsIgnoreEnd
