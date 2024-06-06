<?php declare(strict_types = 1);

namespace App\Tests\Manager;

use App\Config\WithdrawalDelaysConfig;
use App\Manager\WithdrawalLocksManager;
use App\Services\TranslatorService\Translator;
use App\Utils\LockFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Lock\LockInterface;

class WithdrawalLocksManagerTest extends TestCase
{
    private const USER_ID = 1;
    private const REGISTER_DELAY = 1;
    private const LOGIN_DELAY = 2;
    private const WITHDRAW_DELAY = 3;
    private const ORDER_DELAY = 4;
    private const WITHDRAW_COMMIT_RESOURCE = 'withdraw-commit-' . self::USER_ID;
    private const WITHDRAW_INIT_RESOURCE = 'withdraw-init-' . self::USER_ID;
    private const REGISTER_RESOURCE = 'withdraw-register-' . self::USER_ID;
    private const LOGIN_RESOURCE = 'withdraw-login-' . self::USER_ID;
    private const ORDER_RESOURCE = 'order-' . self::USER_ID;
    private const BALANCE_RESOURCE = 'balance-' . self::USER_ID;
    public const WITHDRAW_AFTER_PASSWORD_CHANGE_RESOURCE = 'withdraw-password-change-' . self::USER_ID;
    public const WITHDRAW_AFTER_EMAIL_CHANGE_RESOURCE = 'withdraw-email-change-' . self::USER_ID;
    public const WITHDRAW_AFTER_PHONE_CHANGE_RESOURCE = 'withdraw-phone-change-' . self::USER_ID;

    public function testPrepareDelayLocksAllLocksAreFree(): void
    {
        $withdrawalLockManager = new WithdrawalLocksManager(
            $this->mockWithdrawalDelayConfig(),
            $this->mockLockFactoryForPrepareDelayLocks(
                self::WITHDRAW_COMMIT_RESOURCE,
                true,
                true,
                true,
                true,
            ),
            $this->mockTranslator(),
        );

        $response = $withdrawalLockManager->prepareDelayLocks(
            self::USER_ID
        );

        $this->assertEquals(
            null,
            $response
        );
    }

    public function testPrepareDelayLocksAllLocksAreFreeWithdrawalInit(): void
    {
        $withdrawalLockManager = new WithdrawalLocksManager(
            $this->mockWithdrawalDelayConfig(),
            $this->mockLockFactoryForPrepareDelayLocks(
                self::WITHDRAW_INIT_RESOURCE,
                true,
                true,
                true,
                true,
            ),
            $this->mockTranslator(),
        );

        $response = $withdrawalLockManager->prepareDelayLocks(
            self::USER_ID,
            false
        );

        $this->assertEquals(
            null,
            $response
        );
    }

    public function testPrepareDelayLocksRegisterLockNotExpired(): void
    {
        $withdrawalLockManager = new WithdrawalLocksManager(
            $this->mockWithdrawalDelayConfig(),
            $this->mockLockFactoryForPrepareDelayLocks(
                self::WITHDRAW_COMMIT_RESOURCE,
            ),
            $this->mockTranslator(),
        );

        $response = $withdrawalLockManager->prepareDelayLocks(
            self::USER_ID
        );

        $this->assertEquals(
            'toasted.error.withdrawals.after.register %hours% 0.00027777777777778',
            $response
        );
    }

    public function testPrepareDelayLocksLoginLockNotExpired(): void
    {
        $withdrawalLockManager = new WithdrawalLocksManager(
            $this->mockWithdrawalDelayConfig(),
            $this->mockLockFactoryForPrepareDelayLocks(
                self::WITHDRAW_COMMIT_RESOURCE,
                true,
            ),
            $this->mockTranslator(),
        );

        $response = $withdrawalLockManager->prepareDelayLocks(
            self::USER_ID
        );

        $this->assertEquals(
            'toasted.error.withdrawals.after.login %secondsTotal% 2',
            $response
        );
    }

    public function testPrepareDelayLocksWithdrawalCommitLockNotExpired(): void
    {
        $withdrawalLockManager = new WithdrawalLocksManager(
            $this->mockWithdrawalDelayConfig(),
            $this->mockLockFactoryForPrepareDelayLocks(
                self::WITHDRAW_COMMIT_RESOURCE,
                true,
                true,
            ),
            $this->mockTranslator(),
        );

        $response = $withdrawalLockManager->prepareDelayLocks(
            self::USER_ID
        );

        $this->assertEquals(
            'wallet.withdrawal_delay %seconds% 3',
            $response
        );
    }

    public function testPrepareDelayLocksWithdrawalInitLockNotExpired(): void
    {
        $withdrawalLockManager = new WithdrawalLocksManager(
            $this->mockWithdrawalDelayConfig(),
            $this->mockLockFactoryForPrepareDelayLocks(
                self::WITHDRAW_INIT_RESOURCE,
                true,
                true,
            ),
            $this->mockTranslator(),
        );

        $response = $withdrawalLockManager->prepareDelayLocks(
            self::USER_ID,
            false
        );

        $this->assertEquals(
            'wallet.withdrawal_delay %seconds% 3',
            $response
        );
    }

    public function testPrepareDelayLocksOrderLockNotExpired(): void
    {
        $withdrawalLockManager = new WithdrawalLocksManager(
            $this->mockWithdrawalDelayConfig(),
            $this->mockLockFactoryForPrepareDelayLocks(
                self::WITHDRAW_COMMIT_RESOURCE,
                true,
                true,
                true,
            ),
            $this->mockTranslator(),
        );

        $response = $withdrawalLockManager->prepareDelayLocks(
            self::USER_ID
        );

        $this->assertEquals(
            'wallet.withdrawal_delay %seconds% 4',
            $response
        );
    }

    public function testAcquireLockBalanceLockExpired(): void
    {
        $withdrawalLockManager = new WithdrawalLocksManager(
            $this->mockWithdrawalDelayConfig(),
            $this->mockLockFactory(
                self::BALANCE_RESOURCE,
                true,
                false,
            ),
            $this->mockTranslator(),
        );

        $response = $withdrawalLockManager->acquireLockBalance(self::USER_ID);

        $this->assertEquals(
            true,
            $response
        );
    }

    public function testAcquireLockBalanceLockNotExpired(): void
    {
        $withdrawalLockManager = new WithdrawalLocksManager(
            $this->mockWithdrawalDelayConfig(),
            $this->mockLockFactory(
                self::BALANCE_RESOURCE,
                false,
                false,
            ),
            $this->mockTranslator(),
        );

        $response = $withdrawalLockManager->acquireLockBalance(self::USER_ID);

        $this->assertEquals(
            false,
            $response
        );
    }

    public function testReleaseBalanceLock(): void
    {
        $withdrawalLockManager = new WithdrawalLocksManager(
            $this->mockWithdrawalDelayConfig(),
            $this->mockLockFactory(
                self::BALANCE_RESOURCE,
            ),
            $this->mockTranslator(),
        );

        $withdrawalLockManager->acquireLockBalance(self::USER_ID);
        $withdrawalLockManager->releaseLockBalance();
    }

    public function testIsLoginLockExpiredLockExpired(): void
    {
        $withdrawalLockManager = new WithdrawalLocksManager(
            $this->mockWithdrawalDelayConfig(),
            $this->mockLockFactory(
                self::LOGIN_RESOURCE,
            ),
            $this->mockTranslator(),
        );

        $response = $withdrawalLockManager->isLoginLockExpired(self::USER_ID);

        $this->assertEquals(
            true,
            $response
        );
    }

    public function testIsLoginLockExpiredLockNotExpired(): void
    {
        $withdrawalLockManager = new WithdrawalLocksManager(
            $this->mockWithdrawalDelayConfig(),
            $this->mockLockFactory(
                self::LOGIN_RESOURCE,
                false,
                false
            ),
            $this->mockTranslator(),
        );

        $response = $withdrawalLockManager->isLoginLockExpired(self::USER_ID);

        $this->assertEquals(
            false,
            $response
        );
    }

    public function testIsRegisterLockExpiredLockExpired(): void
    {
        $withdrawalLockManager = new WithdrawalLocksManager(
            $this->mockWithdrawalDelayConfig(),
            $this->mockLockFactory(
                self::REGISTER_RESOURCE,
            ),
            $this->mockTranslator(),
        );

        $response = $withdrawalLockManager->isRegisterLockExpired(self::USER_ID);

        $this->assertEquals(
            true,
            $response
        );
    }

    public function testIsRegisterLockExpiredLockNotExpired(): void
    {
        $withdrawalLockManager = new WithdrawalLocksManager(
            $this->mockWithdrawalDelayConfig(),
            $this->mockLockFactory(
                self::REGISTER_RESOURCE,
                false,
                false
            ),
            $this->mockTranslator(),
        );

        $response = $withdrawalLockManager->isRegisterLockExpired(self::USER_ID);

        $this->assertEquals(
            false,
            $response
        );
    }

    private function mockWithdrawalDelayConfig(): WithdrawalDelaysConfig
    {
        $config = $this->createMock(WithdrawalDelaysConfig::class);
        $config->method('getWithdrawAfterRegisterTime')->willReturn(self::REGISTER_DELAY);
        $config->method('getWithdrawAfterLoginTime')->willReturn(self::LOGIN_DELAY);
        $config->method('getWithdrawalDelay')->willReturn(self::WITHDRAW_DELAY);
        $config->method('getOrderDelay')->willReturn(self::ORDER_DELAY);
        $config->method('isUserChangeLockExpired')->willReturn(self::ORDER_DELAY);

        return $config;
    }

    private function mockLockFactoryForPrepareDelayLocks(
        string $withdrawalResource,
        bool $lockRegisterExpired = false,
        bool $lockLoginExpired = false,
        bool $lockWithdrawalDelayExpired = false,
        bool $lockOrderDelayExpired = false
    ): LockFactory {
        $returnMap = [
            [
                self::REGISTER_RESOURCE,
                (float)self::REGISTER_DELAY,
                false,
                $this->mockLock($lockRegisterExpired, $lockRegisterExpired),
            ],
            [
                self::LOGIN_RESOURCE,
                (float)self::LOGIN_DELAY,
                false,
                $this->mockLock($lockLoginExpired, $lockLoginExpired),
            ],
            [
                $withdrawalResource,
                (float)self::WITHDRAW_DELAY,
                false,
                $this->mockLock(
                    $lockWithdrawalDelayExpired,
                    $lockWithdrawalDelayExpired && !$lockOrderDelayExpired
                ),
            ],
            [
                self::ORDER_RESOURCE,
                (float)self::ORDER_DELAY,
                false,
                $this->mockLock($lockOrderDelayExpired),
            ],
            [
                self::WITHDRAW_AFTER_PASSWORD_CHANGE_RESOURCE,
                (float)self::WITHDRAW_DELAY,
                false,
                $this->mockLock($lockWithdrawalDelayExpired),
            ],
            [
                self::WITHDRAW_AFTER_EMAIL_CHANGE_RESOURCE,
                (float)self::WITHDRAW_DELAY,
                false,
                $this->mockLock($lockWithdrawalDelayExpired),
            ],
            [
                self::WITHDRAW_AFTER_EMAIL_CHANGE_RESOURCE,
                (float)self::WITHDRAW_AFTER_PHONE_CHANGE_RESOURCE,
                false,
                $this->mockLock($lockWithdrawalDelayExpired),
            ],
        ];
        $lockFactory = $this->createMock(LockFactory::class);
        $lockFactory
            ->method('createLock')
            ->willReturnMap($returnMap);

        return $lockFactory;
    }

    private function mockLock(bool $expired, bool $expectRelease = false): LockInterface
    {
        $lock = $this->createMock(LockInterface::class);
        $lock
            ->method('acquire')
            ->willReturn($expired);
        $lock
            ->expects($expectRelease
                ? $this->once()
                : $this->never())
            ->method('release');

        return $lock;
    }

    private function mockLockFactory(
        string $lockResource,
        bool $lockExpired = true,
        bool $expectRelease = true
    ): LockFactory {
        $lockFactory = $this->createMock(LockFactory::class);
        $lockFactory
            ->method('createLock')
            ->with($lockResource)
            ->willReturn($this->mockLock($lockExpired, $expectRelease));

        return $lockFactory;
    }

    private function mockTranslator(): Translator
    {
        $translator = $this->createMock(Translator::class);
        $translator
            ->method('trans')
            ->willReturnCallback(function ($transKey, $params): string {
                $result = $transKey;

                foreach ($params as $key => $param) {
                    if ($param) {
                        $result .= " $key $param";
                    }
                }

                return $result;
            });

        return $translator;
    }
}
