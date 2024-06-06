<?php declare(strict_types = 1);

namespace App\Tests\Exchange\Integration;

use App\Entity\Token\Token;
use App\Entity\User;
use App\Exchange\Balance\BalanceHandler;
use App\Manager\BonusBalanceTransactionManager;
use App\Utils\Converter\TokenNameConverter;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Money\Currency;
use Money\Money;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpKernel\KernelInterface;

class BalanceHandlerTest extends KernelTestCase
{
    protected BalanceHandler $balanceHandler;
    protected EntityManagerInterface $em;
    protected TokenNameConverter $tokenNameConverter;
    protected BonusBalanceTransactionManager $bonusBalanceTransactionManager;

    protected function setUp(): void
    {
        // TODO: remove after #8199 and #8785 is merged
        $this->markTestSkipped('
          Skipped for now until we have a proper setup on gitlab for integration tests, work fine locally.
        ');
        // @phpstan-ignore-next-line
        $kernel = self::bootKernel();

        $serviceContainer = self::$container;

        $this->balanceHandler = $serviceContainer->get(BalanceHandler::class);

        $this->tokenNameConverter = $serviceContainer->get(TokenNameConverter::class);

        $this->bonusBalanceTransactionManager = $serviceContainer->get(BonusBalanceTransactionManager::class);

        $this->initDatabase($kernel);

        $this->em = $serviceContainer->get('doctrine.orm.entity_manager');
    }

    protected function tearDown(): void
    {
        try {
            $this->balanceHandler->rollback();
        } catch (\Throwable $e) {
        }
    }

    public function testRollbackBonusWithDeposits(): void
    {
        $user = $this->createUser();
        $token = $this->createToken();

        $this->balanceHandler->beginTransaction();
        $this->balanceHandler->depositBonus($user, $token, new Money(100, new Currency('TOK')), 'test');
        $this->balanceHandler->depositBonus($user, $token, new Money(100, new Currency('TOK')), 'test');
        $this->balanceHandler->rollback();

        $this->assertEquals(0, $this->bonusBalanceTransactionManager->getBalance($user, $token)->getAmount());
    }

    public function testRollbackBonusWithWithdrawsWithEnoughBonusBalance(): void
    {
        $user = $this->createUser();
        $token = $this->createToken();

        $this->balanceHandler->depositBonus($user, $token, new Money(200, new Currency('TOK')), 'test');

        $this->balanceHandler->beginTransaction();
        $this->balanceHandler->withdrawBonus($user, $token, new Money(100, new Currency('TOK')), 'test');
        $this->balanceHandler->withdrawBonus($user, $token, new Money(100, new Currency('TOK')), 'test');
        $this->balanceHandler->rollback();

        $this->assertEquals(200, $this->bonusBalanceTransactionManager->getBalance($user, $token)->getAmount());
    }

    public function testRollbackBonusWithWithdrawWithEnoughBalance(): void
    {
        $user = $this->createUser();
        $token = $this->createToken();

        $startBalance = $this->balanceHandler->balance($user, $token);

        $this->balanceHandler->beginTransaction();
        $this->balanceHandler->withdrawBonus($user, $token, new Money(1000000, new Currency('TOK')), 'test');
        $this->balanceHandler->rollback();

        $this->assertEquals(0, $this->bonusBalanceTransactionManager->getBalance($user, $token)->getAmount());
        $this->assertEquals($startBalance, $this->balanceHandler->balance($user, $token));
    }

    public function testRollbackWithBonuses(): void
    {
        $user = $this->createUser();
        $token = $this->createToken();

        $this->balanceHandler->beginTransaction();
        $this->balanceHandler->depositBonus($user, $token, new Money(100, new Currency('TOK')), 'test');
        $this->balanceHandler->depositBonus($user, $token, new Money(100, new Currency('TOK')), 'test');
        $this->balanceHandler->withdrawBonus($user, $token, new Money(100, new Currency('TOK')), 'test');
        $this->balanceHandler->depositBonus($user, $token, new Money(100, new Currency('TOK')), 'test');
        $this->balanceHandler->rollback();

        $this->assertEquals(0, $this->bonusBalanceTransactionManager->getBalance($user, $token)->getAmount());
    }


    public function testRollback(): void
    {
        $amount = new Money(100, new Currency('USD'));
        $firstUser = $this->createUser();
        $secondUser = $this->createUser();

        $firstToken = $this->createToken();
        $secondToken = $this->createToken();

        $firstTokenFirstUserBalance = $this->balanceHandler->balance($firstUser, $firstToken)->getAvailable();
        $secondTokenFirstUserBalance = $this->balanceHandler->balance($firstUser, $secondToken)->getAvailable();
        $firstTokenSecondUserBalance = $this->balanceHandler->balance($secondUser, $firstToken)->getAvailable();
        $secondTokenSecondUserBalance = $this->balanceHandler->balance($secondUser, $secondToken)->getAvailable();

        $this->balanceHandler->beginTransaction();

        try {
            $this->balanceHandler->deposit($firstUser, $firstToken, $amount);
            $this->balanceHandler->deposit($firstUser, $firstToken, $amount);
            $this->balanceHandler->deposit($firstUser, $firstToken, $amount);
            $this->balanceHandler->withdraw($firstUser, $firstToken, $amount);

            $this->balanceHandler->deposit($secondUser, $secondToken, $amount);
            $this->balanceHandler->deposit($secondUser, $secondToken, $amount);
            $this->balanceHandler->withdraw($secondUser, $secondToken, $amount);
            $this->balanceHandler->withdraw($secondUser, $secondToken, $amount);

            $this->balanceHandler->deposit($firstUser, $secondToken, $amount);
            $this->balanceHandler->deposit($firstUser, $secondToken, $amount);
            $this->balanceHandler->withdraw($firstUser, $secondToken, $amount);
            $this->balanceHandler->withdraw($firstUser, $secondToken, $amount);
            $this->balanceHandler->withdraw($firstUser, $secondToken, $amount);

            $this->balanceHandler->withdraw($secondUser, $firstToken, $amount);

            $this->balanceHandler->rollback();
        } catch (\Throwable $e) {
            $this->fail('Failed to run rollback test, Reason: ' . $e->getMessage());
        }

        $firstTokenFirstUserEndBalance = $this->balanceHandler->balance($firstUser, $firstToken)->getAvailable();
        $secondTokenFirstUserEndBalance = $this->balanceHandler->balance($firstUser, $secondToken)->getAvailable();
        $firstTokenSecondUserEndBalance = $this->balanceHandler->balance($secondUser, $firstToken)->getAvailable();
        $secondTokenSecondUserEndBalance = $this->balanceHandler->balance($secondUser, $secondToken)->getAvailable();

        $this->assertEquals(
            [
                $firstTokenFirstUserBalance->getAmount(),
                $secondTokenFirstUserBalance->getAmount(),
                $firstTokenSecondUserBalance->getAmount(),
                $secondTokenSecondUserBalance->getAmount(),
            ],
            [
                $firstTokenFirstUserEndBalance->getAmount(),
                $secondTokenFirstUserEndBalance->getAmount(),
                $firstTokenSecondUserEndBalance->getAmount(),
                $secondTokenSecondUserEndBalance->getAmount(),
            ],
        );
    }

    public function testSmallRollback(): void
    {
        $amount = new Money(100, new Currency('USD'));
        $firstUser = $this->createUser();
        $firstToken = $this->createToken();

        $firstTokenFirstUserBalance = $this->balanceHandler->balance($firstUser, $firstToken)->getAvailable();

        try {
            $this->balanceHandler->deposit($firstUser, $firstToken, $amount);
            $this->balanceHandler->deposit($firstUser, $firstToken, $amount);
            $this->balanceHandler->deposit($firstUser, $firstToken, $amount);

            $this->balanceHandler->beginTransaction();
            $this->balanceHandler->deposit($firstUser, $firstToken, $amount);
            $this->balanceHandler->rollback();

            $this->balanceHandler->withdraw($firstUser, $firstToken, $amount);
            $this->balanceHandler->withdraw($firstUser, $firstToken, $amount);
            $this->balanceHandler->withdraw($firstUser, $firstToken, $amount);
        } catch (\Throwable $e) {
            $this->fail('Failed to run rollback test, Reason: ' . $e->getMessage());
        }

        $firstTokenFirstUserEndBalance = $this->balanceHandler->balance($firstUser, $firstToken)->getAvailable();

        $this->assertEquals(
            $firstTokenFirstUserBalance->getAmount(),
            $firstTokenFirstUserEndBalance->getAmount(),
        );
    }

    public function testSlowRollback(): void
    {
        $amount = new Money(100, new Currency('USD'));
        $firstUser = $this->createUser();
        $firstToken = $this->createToken();

        $firstTokenFirstUserBalance = $this->balanceHandler->balance($firstUser, $firstToken)->getAvailable();

        try {
            $this->balanceHandler->beginTransaction();

            $this->balanceHandler->deposit($firstUser, $firstToken, $amount);
            sleep(1);
            $this->balanceHandler->deposit($firstUser, $firstToken, $amount);
            $this->balanceHandler->rollback();
        } catch (\Throwable $e) {
            $this->fail('Failed to run rollback test, Reason: ' . $e->getMessage());
        }

        $firstTokenFirstUserEndBalance = $this->balanceHandler->balance($firstUser, $firstToken)->getAvailable();

        $this->assertEquals(
            $firstTokenFirstUserBalance->getAmount(),
            $firstTokenFirstUserEndBalance->getAmount(),
        );
    }

    public function testNormalDepositsNoRollback(): void
    {
        $amount = new Money(100, new Currency('USD'));
        $firstUser = $this->createUser();
        $secondUser = $this->createUser();

        $firstToken = $this->createToken();
        $secondToken = $this->createToken();

        $firstTokenFirstUserBalance = $this->balanceHandler->balance($firstUser, $firstToken)->getAvailable();
        $secondTokenFirstUserBalance = $this->balanceHandler->balance($firstUser, $secondToken)->getAvailable();
        $firstTokenSecondUserBalance = $this->balanceHandler->balance($secondUser, $firstToken)->getAvailable();
        $secondTokenSecondUserBalance = $this->balanceHandler->balance($secondUser, $secondToken)->getAvailable();

        $this->balanceHandler->beginTransaction();

        try {
            $this->balanceHandler->deposit($firstUser, $firstToken, $amount);
            $this->balanceHandler->deposit($secondUser, $secondToken, $amount);
            $this->balanceHandler->deposit($firstUser, $secondToken, $amount);
            $this->balanceHandler->deposit($secondUser, $firstToken, $amount);
        } catch (\Throwable $e) {
            $this->fail('Failed to run normal updates test, Reason: ' . $e->getMessage());
        }

        $firstTokenFirstUserEndBalance = $this->balanceHandler->balance($firstUser, $firstToken)->getAvailable();
        $secondTokenFirstUserEndBalance = $this->balanceHandler->balance($firstUser, $secondToken)->getAvailable();
        $firstTokenSecondUserEndBalance = $this->balanceHandler->balance($secondUser, $firstToken)->getAvailable();
        $secondTokenSecondUserEndBalance = $this->balanceHandler->balance($secondUser, $secondToken)->getAvailable();


        $this->assertNotEquals($firstTokenFirstUserBalance->getAmount(), $firstTokenFirstUserEndBalance->getAmount());
        $this->assertNotEquals($secondTokenFirstUserBalance->getAmount(), $secondTokenFirstUserEndBalance->getAmount());
        $this->assertNotEquals($firstTokenSecondUserBalance->getAmount(), $firstTokenSecondUserEndBalance->getAmount());
        $this->assertNotEquals(
            $secondTokenSecondUserBalance->getAmount(),
            $secondTokenSecondUserEndBalance->getAmount()
        );
    }

    public function testNormalWithdrawsNoRollback(): void
    {
        $amount = new Money(100, new Currency('USD'));
        $firstUser = $this->createUser();
        $secondUser = $this->createUser();

        $firstToken = $this->createToken();
        $secondToken = $this->createToken();

        $firstTokenFirstUserBalance = $this->balanceHandler->balance($firstUser, $firstToken)->getAvailable();
        $secondTokenFirstUserBalance = $this->balanceHandler->balance($firstUser, $secondToken)->getAvailable();
        $firstTokenSecondUserBalance = $this->balanceHandler->balance($secondUser, $firstToken)->getAvailable();
        $secondTokenSecondUserBalance = $this->balanceHandler->balance($secondUser, $secondToken)->getAvailable();

        $this->balanceHandler->beginTransaction();

        try {
            $this->balanceHandler->withdraw($firstUser, $firstToken, $amount);
            $this->balanceHandler->withdraw($secondUser, $secondToken, $amount);
            $this->balanceHandler->withdraw($firstUser, $secondToken, $amount);
            $this->balanceHandler->withdraw($secondUser, $firstToken, $amount);
        } catch (\Throwable $e) {
            $this->fail('Failed to run normal updates test, Reason: ' . $e->getMessage());
        }

        $firstTokenFirstUserEndBalance = $this->balanceHandler->balance($firstUser, $firstToken)->getAvailable();
        $secondTokenFirstUserEndBalance = $this->balanceHandler->balance($firstUser, $secondToken)->getAvailable();
        $firstTokenSecondUserEndBalance = $this->balanceHandler->balance($secondUser, $firstToken)->getAvailable();
        $secondTokenSecondUserEndBalance = $this->balanceHandler->balance($secondUser, $secondToken)->getAvailable();

        $this->assertNotEquals($firstTokenFirstUserBalance->getAmount(), $firstTokenFirstUserEndBalance->getAmount());
        $this->assertNotEquals($secondTokenFirstUserBalance->getAmount(), $secondTokenFirstUserEndBalance->getAmount());
        $this->assertNotEquals($firstTokenSecondUserBalance->getAmount(), $firstTokenSecondUserEndBalance->getAmount());
        $this->assertNotEquals(
            $secondTokenSecondUserBalance->getAmount(),
            $secondTokenSecondUserEndBalance->getAmount()
        );
    }

    public function testNormalDepositWithWithdraws(): void
    {
        $amount = new Money(100, new Currency('USD'));
        $firstUser = $this->createUser();
        $secondUser = $this->createUser();

        $firstToken = $this->createToken();
        $secondToken = $this->createToken();

        $firstTokenFirstUserBalance = $this->balanceHandler->balance($firstUser, $firstToken)->getAvailable();
        $secondTokenFirstUserBalance = $this->balanceHandler->balance($firstUser, $secondToken)->getAvailable();
        $firstTokenSecondUserBalance = $this->balanceHandler->balance($secondUser, $firstToken)->getAvailable();
        $secondTokenSecondUserBalance = $this->balanceHandler->balance($secondUser, $secondToken)->getAvailable();

        $this->balanceHandler->beginTransaction();

        try {
            $this->balanceHandler->withdraw($firstUser, $firstToken, $amount);
            $this->balanceHandler->withdraw($secondUser, $secondToken, $amount);
            $this->balanceHandler->withdraw($firstUser, $secondToken, $amount);
            $this->balanceHandler->withdraw($secondUser, $firstToken, $amount);
            $this->balanceHandler->deposit($firstUser, $firstToken, $amount);
            $this->balanceHandler->deposit($secondUser, $secondToken, $amount);
            $this->balanceHandler->deposit($firstUser, $secondToken, $amount);
            $this->balanceHandler->deposit($secondUser, $firstToken, $amount);
        } catch (\Throwable $e) {
            $this->fail('Failed to run normal updates test, Reason: ' . $e->getMessage());
        }

        $firstTokenFirstUserEndBalance = $this->balanceHandler->balance($firstUser, $firstToken)->getAvailable();
        $secondTokenFirstUserEndBalance = $this->balanceHandler->balance($firstUser, $secondToken)->getAvailable();
        $firstTokenSecondUserEndBalance = $this->balanceHandler->balance($secondUser, $firstToken)->getAvailable();
        $secondTokenSecondUserEndBalance = $this->balanceHandler->balance($secondUser, $secondToken)->getAvailable();

        $this->assertEquals($firstTokenFirstUserBalance->getAmount(), $firstTokenFirstUserEndBalance->getAmount());
        $this->assertEquals($secondTokenFirstUserBalance->getAmount(), $secondTokenFirstUserEndBalance->getAmount());
        $this->assertEquals($firstTokenSecondUserBalance->getAmount(), $firstTokenSecondUserEndBalance->getAmount());
        $this->assertEquals(
            $secondTokenSecondUserBalance->getAmount(),
            $secondTokenSecondUserEndBalance->getAmount()
        );
    }

    public function testWillNotRollbackUpdatesAfterItGotCalled(): void
    {
        $amount = new Money(100, new Currency('USD'));
        $user = $this->createUser();
        $token = $this->createToken();

        $startBalance = $this->balanceHandler->balance($user, $token)->getAvailable();
        $this->balanceHandler->beginTransaction();

        try {
            $this->balanceHandler->withdraw($user, $token, $amount);
            $this->balanceHandler->rollback();
            $this->balanceHandler->deposit($user, $token, $amount);
        } catch (\Throwable $e) {
            $this->fail('Failed to run rollback test, Reason: ' . $e->getMessage());
        }

        $endBalance = $this->balanceHandler->balance($user, $token)->getAvailable();
        $this->assertNotEquals($startBalance->getAmount(), $endBalance->getAmount());
    }

    public function testWillNotRollbackStartedBeforeBeginTransaction(): void
    {
        $amount = new Money(100, new Currency('USD'));
        $user = $this->createUser();
        $token = $this->createToken();

        $startBalance = $this->balanceHandler->balance($user, $token)->getAvailable();

        try {
            $this->balanceHandler->deposit($user, $token, $amount);
            $this->balanceHandler->beginTransaction();
            $this->balanceHandler->deposit($user, $token, $amount);
            $this->balanceHandler->rollback();
        } catch (\Throwable $e) {
            $this->fail('Failed to run rollback test, Reason: ' . $e->getMessage());
        }

        $endBalance = $this->balanceHandler->balance($user, $token)->getAvailable();
        $this->assertNotEquals($startBalance->getAmount(), $endBalance->getAmount());
    }

    public function testRollbackWouldThrowIfTransactionDidntStart(): void
    {
        $amount = new Money(100, new Currency('USD'));
        $user = $this->createUser();
        $token = $this->createToken();

        $this->expectException(\RuntimeException::class);

        try {
            $this->balanceHandler->deposit($user, $token, $amount);
        } catch (\Throwable $e) {
            $this->fail('Failed to run rollback test, Reason: ' . $e->getMessage());
        }

        $this->balanceHandler->rollback();
    }

    public function testRollbackWithNoTransactionWontThrow(): void
    {
        $this->balanceHandler->beginTransaction();
        $this->balanceHandler->rollback();

        $this->assertTrue(true);
    }


    public function testHistory(): void
    {
        #TODO: remove once we have test env for viabtc
        $this->markTestSkipped('Have problems with viabtc persisting history');

        // @phpstan-ignore-next-line
        $user = $this->createUser();
        $token = $this->createToken();

        $amount = new Money(100, new Currency('USD'));

        $now = time();
        $this->balanceHandler->beginTransaction();
        $this->balanceHandler->deposit($user, $token, $amount);
        $until = time() + 1;

        $tokenName = $this->tokenNameConverter->convert($token);

        $balanceHistory = $this->balanceHandler->history($user->getId(), $tokenName, 'deposit', $now, $until);

        $this->assertEquals(1, count($balanceHistory->getRecords()));
        $this->assertEquals(0, $balanceHistory->getOffset());
        $this->assertEquals(50, $balanceHistory->getLimit());
        $this->assertEquals(
            [
                "time" => (int)round($now, -3),
                "asset" => $tokenName,
                "business" => "deposit",
                "change" => "1",

            ],
            [
                "time" => (int)round($balanceHistory->getRecords()[0]['time'], -3),
                "asset" => $balanceHistory->getRecords()[0]['asset'],
                "business" => $balanceHistory->getRecords()[0]['business'],
                "change" => $balanceHistory->getRecords()[0]['change'],
            ]
        );
    }

    protected function initDatabase(KernelInterface $kernel): void
    {
        $em = $kernel->getContainer()->get('doctrine.orm.entity_manager');
        $metaData = $em->getMetadataFactory()->getAllMetadata();
        $schemaTool = new SchemaTool($em);
        $schemaTool->dropSchema($metaData);
        $schemaTool->createSchema($metaData);
    }

    private function createUser(): User
    {
        $user = new User();
        $user->setUsername('test');
        $user->setEmail("test@test.test" . rand(0, 999999));
        $user->setPlainPassword('test');

        $reflectionClass = new \ReflectionClass(User::class);
        $reflectionProperty = $reflectionClass->getProperty('tokens');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($user, new ArrayCollection());

        try {
            $this->em->persist($user);
            $this->em->flush();
        } catch (\Throwable $e) {
            $this->fail('Failed to persist user and token' . $e->getMessage());
        }

        return $user;
    }

    private function createToken(): Token
    {
        $token = new Token();

        $token->setSymbol('TOK' . rand(0, 999999));

        try {
            $this->em->persist($token);
            $this->em->flush();
        } catch (\Throwable $e) {
            $this->fail('Failed to persist user and token' . $e->getMessage());
        }

        return $token;
    }
}
