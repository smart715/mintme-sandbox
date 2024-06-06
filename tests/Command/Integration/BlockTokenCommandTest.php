<?php declare(strict_types = 1);

namespace App\Tests\Command\Integration;

use App\Entity\Crypto;
use App\Entity\Profile;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Exchange\Market\MarketHandlerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ObjectManager;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpKernel\KernelInterface;

class BlockTokenCommandTest extends KernelTestCase
{
    private CommandTester $command;

    private Token $token;

    private User $user;

    private ObjectManager $em;

    public function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->em = $kernel->getContainer()->get('doctrine')->getManager();

        $application = new Application($kernel);

        $this->initDatabase($kernel);

        $command = $application->find('app:block');

        // market handler class does do real cURL request, so we need to mock it
        $this->mockMarketHandlerProperty($command);

        $this->user = new User();
        $this->user->setEmail("TEST@test.test");
        $this->user->setPlainPassword('TEST');

        $profile = new Profile($this->user);

        $crypto = $this->createDummyCrypto();

        $this->token = new Token();
        $this->token->setProfile($profile);
        $this->token->setName('TEST');

        try {
            $this->em->persist($this->user);
            $this->em->persist($profile);
            $this->em->persist($this->token);
            $this->em->persist($crypto);
            $this->em->flush();
            $this->em->clear();
        } catch (\Throwable $e) {
            $this->fail($e->getMessage());
        }

        $this->command = new CommandTester($command);
    }

    public function testSuccessWithEmailAsName(): void
    {
        $this->command->execute([
            'name' => $this->user->getEmail(),
        ]);

        $output = $this->command->getDisplay();

        $this->assertStringContainsString(
            "[OK]",
            $output
        );
        $this->assertStringContainsString(
            $this->token->getName(),
            $output
        );
        $this->assertStringContainsString(
            "User",
            $output
        );

        $this->assertStringContainsString(
            "blocked",
            $output
        );
    }

    public function testSuccessWithTokenNameAsName(): void
    {
        $this->command->execute([
            'name' => $this->token->getName(),
        ]);

        $output = $this->command->getDisplay();

        $this->assertStringContainsString(
            "[OK]",
            $output
        );
        $this->assertStringContainsString(
            "Token {$this->token->getName()}",
            $output
        );
        $this->assertStringContainsString(
            "blocked",
            $output
        );
    }

    public function testSuccessWithTokenNameAndUserOption(): void
    {
        $this->command->execute([
            'name' => $this->user->getEmail(),
            '--user' => true,
        ]);

        $output = $this->command->getDisplay();

        $this->assertStringContainsString(
            "[OK]",
            $output
        );
        $this->assertStringNotContainsString(
            "Token {$this->token->getName()}",
            $output
        );
        $this->assertStringContainsString(
            $this->user->getEmail(),
            $output
        );

        $this->assertStringContainsString(
            "blocked",
            $output
        );
    }

    public function testSuccessWithEmailAndTokenOption(): void
    {
        $this->command->execute([
            'name' => $this->token->getName(),
            '--user' => true,
        ]);

        $output = $this->command->getDisplay();

        $this->assertStringContainsString(
            "[OK]",
            $output
        );
        $this->assertStringNotContainsString(
            "Token {$this->token->getName()}",
            $output
        );
        $this->assertStringContainsString(
            $this->user->getEmail(),
            $output
        );

        $this->assertStringContainsString(
            "blocked",
            $output
        );
    }

    public function testSuccessWithEmailAndUserOption(): void
    {
        $this->command->execute([
            'name' => $this->user->getEmail(),
            '--user' => true,
        ]);

        $output = $this->command->getDisplay();

        $this->assertStringContainsString(
            "[OK]",
            $output
        );
        $this->assertStringNotContainsString(
            "Token {$this->token->getName()}",
            $output
        );
        $this->assertStringContainsString(
            $this->user->getEmail(),
            $output
        );

        $this->assertStringContainsString(
            "blocked",
            $output
        );
    }

    public function testSuccessWithUnblockOption(): void
    {
        $this->blockUserAndToken();

        $this->command->execute([
            'name' => $this->token->getName(),
            '--unblock' => true,
        ]);

        $output = $this->command->getDisplay();

        $this->assertStringContainsString(
            "[OK]",
            $output
        );
        $this->assertStringContainsString(
            "Token {$this->token->getName()}",
            $output
        );
        $this->assertStringContainsString(
            "unblocked",
            $output
        );
    }

    public function testSuccessWithUserAndUnblockOptions(): void
    {
        $this->blockUserAndToken();

        $this->command->execute([
            'name' => $this->token->getName(),
            '--user' => true,
            '--unblock' => true,
        ]);

        $output = $this->command->getDisplay();

        $this->assertStringContainsString(
            "[OK]",
            $output
        );


        $this->assertStringNotContainsString(
            "Token {$this->token->getName()}",
            $output
        );

        $this->assertStringContainsString(
            $this->user->getEmail(),
            $output
        );

        $this->assertStringContainsString(
            "unblocked",
            $output
        );
    }

    public function testFailureWithUserAndUnblockOptions(): void
    {
        $this->command->execute([
            'name' => $this->token->getName(),
            '--unblock' => true,
        ]);

        $output = $this->command->getDisplay();

        $this->assertStringContainsString(
            "[WARNING]",
            $output
        );

        $this->assertStringContainsString(
            "Token",
            $output
        );
        $this->assertStringContainsString(
            "is already unblocked",
            $output
        );
    }

    public function testSuccessWithTokenOption(): void
    {
        $this->command->execute([
            'name' => $this->token->getName(),
            '--token' => true,
        ]);

        $output = $this->command->getDisplay();

        $this->assertStringContainsString(
            "[OK]",
            $output
        );

        $this->assertStringContainsString(
            "Token",
            $output
        );

        $this->assertStringNotContainsString(
            "User",
            $output
        );

        $this->assertStringContainsString(
            "blocked",
            $output
        );
    }

    public function testFailureWithTokenOption(): void
    {
        $this->blockUserAndToken();

        $this->command->execute([
            'name' => $this->token->getName(),
            '--token' => true,
        ]);

        $output = $this->command->getDisplay();

        $this->assertStringContainsString(
            "[WARNING]",
            $output
        );

        $this->assertStringContainsString(
            "Token",
            $output
        );

        $this->assertStringContainsString(
            "is already blocked",
            $output
        );
    }

    public function testFailureWithTokenAndUnblockOption(): void
    {
        $this->command->execute([
            'name' => $this->token->getName(),
            '--token' => true,
            '--unblock' => true,
        ]);

        $output = $this->command->getDisplay();

        $this->assertStringContainsString(
            "[WARNING]",
            $output
        );

        $this->assertStringContainsString(
            "Token",
            $output
        );

        $this->assertStringContainsString(
            "is already unblocked",
            $output
        );
    }

    public function testSuccessWithTokenAndUnblockOption(): void
    {
        $this->blockUserAndToken();


        $this->command->execute([
            'name' => $this->token->getName(),
            '--token' => true,
            '--unblock' => true,
        ]);

        $output = $this->command->getDisplay();

        $this->assertStringContainsString(
            "[OK]",
            $output
        );

        $this->assertStringContainsString(
            "Token",
            $output
        );

        $this->assertStringContainsString(
            "unblocked",
            $output
        );
    }

    private function createDummyCrypto(): Crypto
    {
        // Create a dummy crypto, the crypto entity doesn't have setters, so we use a query instead
        // @phpstan-ignore-next-line
        $this->em->getConnection()->executeQuery(
            'INSERT INTO crypto (name, symbol, subunit, show_subunit, fee, tradable, exchangeble, is_token, native_subunit)
            VALUES
            (\'WEB\', \'WEB\', 4, 4, 4, 1, 1, 1, 12)'
        );

        return $this->em->getRepository(Crypto::class)->findOneBy(['name' => 'WEB']);
    }

    private function initDatabase(KernelInterface $kernel): void
    {
        $em = $kernel->getContainer()->get('doctrine.orm.entity_manager');
        $metaData = $em->getMetadataFactory()->getAllMetadata();
        $schemaTool = new SchemaTool($em);
        $schemaTool->dropSchema($metaData);
        $schemaTool->createSchema($metaData);
    }

    private function mockMarketHandlerProperty(Command $command): void
    {
        $reflection = new \ReflectionClass($command);
        $property = $reflection->getProperty('marketHandler');
        $property->setAccessible(true);
        $marketHandler = $this->createMock(MarketHandlerInterface::class);
        $property->setValue($command, $marketHandler);
    }

    private function blockUserAndToken(): void
    {
        $this->user->setIsBlocked(true);
        $this->token->setIsBlocked(true);
        $this->em->merge($this->token);
        $this->em->merge($this->user);
        $this->em->flush();
        $this->em->clear();
    }
}
