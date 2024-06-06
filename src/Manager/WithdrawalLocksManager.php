<?php declare(strict_types = 1);

namespace App\Manager;

use App\Config\WithdrawalDelaysConfig;
use App\Services\TranslatorService\Translator;
use App\Utils\LockFactory;
use Symfony\Component\Lock\LockInterface;

class WithdrawalLocksManager
{
    private WithdrawalDelaysConfig $withdrawalDelaysConfig;
    private LockFactory $lockFactory;
    private Translator $translator;

    private LockInterface $lockBalance;

    public function __construct(
        WithdrawalDelaysConfig $withdrawalDelaysConfig,
        LockFactory $lockFactory,
        Translator $translator
    ) {
        $this->withdrawalDelaysConfig = $withdrawalDelaysConfig;
        $this->lockFactory = $lockFactory;
        $this->translator = $translator;
    }

    public function prepareDelayLocks(
        int $userId,
        bool $withdrawCommit = true
    ): ?string {
        $withdrawAfterLoginTime = $this->withdrawalDelaysConfig->getWithdrawAfterLoginTime();
        $withdrawAfterRegisterTime = $this->withdrawalDelaysConfig->getWithdrawAfterRegisterTime();
        $withdrawalDelay = $this->withdrawalDelaysConfig->getWithdrawalDelay();
        $orderDelay = $this->withdrawalDelaysConfig->getOrderDelay();

        $withdrawalDelayResource = $withdrawCommit
            ? LockFactory::LOCK_WITHDRAW_COMMIT . $userId
            : LockFactory::LOCK_WITHDRAW_INIT . $userId;

        $lockWithdrawalDelay = $this->lockFactory->createLock(
            $withdrawalDelayResource,
            $withdrawalDelay,
            false
        );
        $lockOrderDelay = $this->lockFactory->createLock(
            LockFactory::LOCK_ORDER . $userId,
            $orderDelay,
            false
        );

        if (!$this->isRegisterLockExpired($userId)) {
            return $this->translator->trans(
                'toasted.error.withdrawals.after.register',
                ['%hours%' => $withdrawAfterRegisterTime / 3600]
            );
        }

        if (!$this->isLoginLockExpired($userId)) {
            return $this->translator->trans(
                'toasted.error.withdrawals.after.login',
                ['%secondsTotal%' => $withdrawAfterLoginTime]
            );
        }

        $lockTypes = [
            LockFactory::LOCK_WITHDRAW_AFTER_PASSWORD_CHANGE => 'password',
            LockFactory::LOCK_WITHDRAW_AFTER_PHONE_CHANGE => 'phone',
            LockFactory::LOCK_WITHDRAW_AFTER_EMAIL_CHANGE => 'email',
            LockFactory::LOCK_WITHDRAW_AFTER_2FA_DISABLE => '2fa',
        ];

        foreach ($lockTypes as $lockType => $key) {
            if (!$this->isUserChangeLockExpired($userId, $lockType)) {
                return $this->getUserChangeLockTranslation($key);
            }
        }

        if (!$lockWithdrawalDelay->acquire()) {
            return $this->translator->trans(
                'wallet.withdrawal_delay',
                [
                    '%seconds%' => $withdrawalDelay,
                ]
            );
        }

        if (!$lockOrderDelay->acquire()) {
            $lockWithdrawalDelay->release();

            return $this->translator->trans(
                'wallet.withdrawal_delay',
                [
                    '%seconds%' => $orderDelay,
                ]
            );
        }

        return null;
    }

    public function acquireLockBalance(int $userId): bool
    {
        $this->lockBalance = $this->lockFactory->createLock(
            LockFactory::LOCK_BALANCE . $userId
        );

        return $this->lockBalance->acquire();
    }

    public function releaseLockBalance(): void
    {
        $this->lockBalance->release();
    }

    public function isLoginLockExpired(int $userId): bool
    {
        $lockWithdrawLogin = $this->lockFactory->createLock(
            LockFactory::LOCK_WITHDRAW_AFTER_LOGIN . $userId,
            $this->withdrawalDelaysConfig->getWithdrawAfterLoginTime(),
            false
        );

        if (!$lockWithdrawLogin->acquire()) {
            return false;
        }

        $lockWithdrawLogin->release();

        return true;
    }

    public function isRegisterLockExpired(int $userId): bool
    {
        $lockWithdrawRegister = $this->lockFactory->createLock(
            LockFactory::LOCK_WITHDRAW_AFTER_REGISTER . $userId,
            $this->withdrawalDelaysConfig->getWithdrawAfterRegisterTime(),
            false
        );

        if (!$lockWithdrawRegister->acquire()) {
            return false;
        }

        $lockWithdrawRegister->release();

        return true;
    }

    public function isUserChangeLockExpired(int $userId, string $lockKey): bool
    {
        $lockWithdraw = $this->lockFactory->createLock(
            $lockKey . $userId,
            $this->withdrawalDelaysConfig->getWithdrawAfterUserChangeTime(),
            false
        );

        if (!$lockWithdraw->acquire()) {
            return false;
        }

        $lockWithdraw->release();

        return true;
    }

    private function getUserChangeLockTranslation(string $action): string
    {
        $withdrawAfterUserChangeSeconds = $this->withdrawalDelaysConfig->getWithdrawAfterUserChangeTime();
        $withdrawAfterUserChangeHours = round($withdrawAfterUserChangeSeconds / 3600, 3);
        $actionTranslation = $this->translator->trans('user_change.' . $action);

        return $this->translator->trans(
            'toasted.error.withdrawals.after.' . ('2fa' === $action ? '2fa_disable' : 'user_change'),
            [
                '%action%' => '2fa' === $action ? '' : $actionTranslation,
                '%hours%' => $withdrawAfterUserChangeHours,
            ]
        );
    }
}
