<?php declare(strict_types = 1);

namespace App\Tests\Command\Integration;

use App\Command\CheckScheduledNotificationsCommand;
use App\Entity\Profile;
use App\Entity\ScheduledNotification;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Exchange\Market\MarketHandler;
use App\Repository\ScheduledNotificationRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpKernel\KernelInterface;

class CheckScheduledNotificationsCommandTest extends KernelTestCase
{
    private CommandTester $commandTester;

    private EntityManager $em;

    private MarketHandler $mockMarketHandler;

    protected ScheduledNotificationRepository $notificationRepo;

    public function setUp(): void
    {
        $kernel = self::bootKernel();

        try {
            class_alias(
                'App\Tests\Command\Integration\FakeNotificationContext',
                'App\Notifications\Strategy\NotificationContext',
                true
            );
        } catch (\Throwable $e) {
            if (!str_contains($e->getMessage(), 'Cannot declare class')) {
                throw new \Exception($e->getMessage());
            }
        }

        /** @phpstan-ignore-next-line */
        $this->em = $kernel->getContainer()->get('doctrine')->getManager();

        $this->notificationRepo = $this->em->getRepository(
            ScheduledNotification::class
        );

        $this->mockMarketHandler = $this->getMockBuilder(MarketHandler::class)
            ->disableOriginalConstructor()
            ->getMock();

        $container = $kernel->getContainer();

        $container->set('test.App\Exchange\Market\MarketHandler', $this->mockMarketHandler);

        $application = new Application($kernel);

        $this->initDatabase($kernel);

        $command = $application->find('app:check-scheduled-notifications');

        $this->commandTester = new CommandTester($command);
    }

    public function testOrderTypesNotification(): void
    {
        $this->createDummyUser();
        $this->createDummyNotification('filled', '1 day');

        $this->commandTester->execute([]);

        $this->assertEquals(null, $this->notificationRepo->findOneBy(['id'=>1]));
    }

    public function testMarketingTypesNotification(): void
    {
        $this->createDummyUser();
        $this->createDummyNotification('token_marketing_tips', '1 day');

        $this->commandTester->execute([]);

        $this->assertEquals(null, $this->notificationRepo->findOneBy(['id'=>1]));
    }

    public function testOrderTypesNotificationWithTokenWithSellOrders(): void
    {
        /** @phpstan-ignore-next-line */
        $this->mockMarketHandler
            ->expects($this->once())
            ->method('getSellOrdersSummaryByUser')
            ->willReturn(['order1', 'order2']);

        $this->createDummyUser();
        $this->createDummyProfile();
        $this->createDummyToken();
        $this->createDummyCrypto();
        $this->createDummyNotification('filled', '1 day');

        $this->commandTester->execute([]);

        $this->assertEquals(null, $this->notificationRepo->findOneBy(['id'=>1]));
    }

    public function testOrderTypesNotificationWithToken(): void
    {
        $this->createDummyUser();
        $this->createDummyProfile();
        $this->createDummyToken();
        $this->createDummyCrypto();
        $this->createDummyNotification('filled', '1 day');

        $this->commandTester->execute([]);

        $this->assertEquals(
            "2022-07-21",
            $this->notificationRepo->findOneBy(['id'=>1])
                ->getDateToBeSend()
                ->format('Y-m-d')
        );
    }

    public function testMarketingTypesNotificationWithToken(): void
    {
        $this->createDummyUser();
        $this->createDummyToken();
        $this->createDummyUserToken();
        $this->createDummyNotification('token_marketing_tips', '1 day');

        $this->commandTester->execute([]);

        $this->assertEquals(
            "2022-06-25",
            $this->notificationRepo->findOneBy(['id'=>1])
                ->getDateToBeSend()
                ->format('Y-m-d')
        );
    }

    public function testMarketingTypesNotificationAirdropFeature(): void
    {
        $this->createDummyUser();
        $this->createDummyProfile();
        $this->createDummyToken();
        $this->createDummyUserToken();
        $this->createDummyNotification('marketing_airdrop_feature', '7 day');

        $this->commandTester->execute([]);

        $this->assertEquals(null, $this->notificationRepo->findOneBy(['id'=>1]));
    }

    private function initDatabase(KernelInterface $kernel): void
    {
        $em = $kernel->getContainer()->get('doctrine.orm.entity_manager');
        $metaData = $em->getMetadataFactory()->getAllMetadata();
        $schemaTool = new SchemaTool($em);
        $schemaTool->dropSchema($metaData);
        $schemaTool->createSchema($metaData);
    }

    private function createDummyNotification(string $type, string $interval): void
    {
        $this->em->getConnection()->executeQuery(
            "INSERT INTO scheduled_notifications (id, type, user_id, time_interval, `date`, date_to_be_send)
            VALUES
            (1,'$type',1,'$interval','2022-06-20 10:30:03.000','2022-06-21 10:30:03.000')"
        );
    }

    private function createDummyUser(): void
    {
        $this->em->getConnection()->executeQuery(
            "INSERT INTO `user` (id, username, username_canonical, email, email_canonical, enabled, salt, password, last_login, confirmation_token, password_requested_at, roles, referral_code, hash, referencer_id, auth_code, trusted_token_version, auth_code_expiration_time, bonus_id, is_blocked, coinify_offline_token, twitter_access_token, twitter_access_token_secret, airdrop_referrer_user_id, airdrop_referrer_id, discord_id, exchange_crypto_mail_sent, trading_fee, locale, session_id)
            VALUES(1, 'user@gmail.com', 'user@gmail.com', 'user@gmail.com', 'user@googlemail.com', 1, NULL, '123', '2022-06-28 09:07:17.000', NULL, NULL, 'a:1:{i:0;s:23:\"ROLE_SEMI_AUTHENTICATED\";}', '123', '123', NULL, NULL, 0, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 'en', '123');"
        );
    }

    private function createDummyToken(): void
    {
        $this->em->getConnection()->executeQuery(
            "INSERT INTO `token` (id, name, website_url, facebook_url, youtube_channel_id, description, website_confirmation_token, profile_id, created, mint_destination, telegram_url, discord_url, deployed, withdrawn, minted_amount, image_id, airdrops_amount, is_blocked, number_of_reminder, next_reminder_date, decimals, fee, show_deployed_modal, cover_image_id, is_hidden, created_on_mintme_site, is_quiet, twitter_url)
            VALUES(1, 'TOKEN1', NULL, NULL, NULL, 'testtest', NULL, 1, '2022-06-19 10:30:02.000', NULL, NULL, NULL, 1, '200', '2002402142267473363', NULL, '0', 0, 0, '2022-07-19 10:30:02.000', 12, NULL, 0, NULL, 0, 1, 0, NULL);"
        );
    }

    private function createDummyUserToken(): void
    {
        $this->em->getConnection()->executeQuery(
            "INSERT INTO `user_tokens` (user_id, token_id, id, created, is_holder, is_removed)
            VALUES(1, 1, 1, '2022-06-19 10:30:03.000', 1, 0);"
        );
    }

    private function createDummyProfile(): void
    {
        $this->em->getConnection()->executeQuery(
            "INSERT INTO `profile` (id, first_name, last_name, city, description, country, name_changed_date, user_id, anonymous, zip_code, nickname, image_id, number_of_reminder, next_reminder_date, created, phone_number_id)
            VALUES(1, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, NULL, 'nickname', NULL, 0, '2022-07-19', '2022-06-19 10:14:14.000', NULL);"
        );
    }

    private function createDummyCrypto(): void
    {
        $this->em->getConnection()->executeQuery(
            "INSERT INTO `crypto` (id, name, symbol, subunit, fee, tradable, exchangeble, show_subunit, image_path, is_token, native_subunit)
            VALUES(1, 'WEB', 'WEB', 18, 300, 1, 1, 8, 'test', 0, 12);"
        );
    }
}

// @codingStandardsIgnoreStart
class FakeNotificationContext
{
    public function sendNotification(User $user): string
    {
        return 'nothing';
    }
}
// @codingStandardsIgnoreEnd
