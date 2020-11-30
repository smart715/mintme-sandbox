<?php declare(strict_types = 1);

namespace App\Utils;

use Symfony\Contracts\Translation\TranslatorInterface;

class NotificationTypes implements NotificationTypesInterface
{
    /** @var TranslatorInterface */
    private TranslatorInterface $translations;

    public function __construct(TranslatorInterface $translations)
    {
        $this->translations = $translations;
    }

    public const DEPOSIT = 'deposit';
    public const WITHDRAWAL = 'withdrawal';
    public const NEW_INVESTOR = 'new_investor';
    public const TOKEN_NEW_POST = 'new_post';
    public const TOKEN_DEPLOYED = 'deployed';
    public const ORDER_FILLED = 'filled';
    public const ORDER_CANCELLED = 'cancelled';

    public static function getAll(): array
    {
        return [
            self::DEPOSIT,
            self::WITHDRAWAL,
            self::NEW_INVESTOR,
            self::TOKEN_NEW_POST,
            self::TOKEN_DEPLOYED,
            self::ORDER_FILLED,
            self::ORDER_CANCELLED,
        ];
    }
    public static function getConfigurable(): array
    {
        return [
            self::DEPOSIT,
            self::WITHDRAWAL,
            self::NEW_INVESTOR,
            self::TOKEN_NEW_POST,
            self::TOKEN_DEPLOYED,
        ];
    }
    public function getText(): array
    {
        return [
            self::DEPOSIT => $this->translations->trans('userNotification.type.deposits'),
            self::WITHDRAWAL => $this->translations->trans('userNotification.type.withdrawals'),
            self::NEW_INVESTOR => $this->translations->trans('userNotification.type.new_investors'),
            self::TOKEN_NEW_POST => $this->translations->trans('userNotification.type.token_new_post'),
            self::TOKEN_DEPLOYED => $this->translations->trans('userNotification.type.token_deployment'),
            self::ORDER_FILLED => $this->translations->trans('userNotification.type.order_filled'),
            self::ORDER_CANCELLED => $this->translations->trans('userNotification.type.order_cancelled'),
        ];
    }

    public static function getStrategyText(): array
    {
        return [
            self::DEPOSIT => 'Deposit',
            self::WITHDRAWAL => 'Withdrawal',
            self::NEW_INVESTOR => 'newInvestor',
            self::TOKEN_NEW_POST => 'TokenPost',
            self::TOKEN_DEPLOYED => 'TokenDeployed',
            self::ORDER_FILLED => 'OrderFilled',
            self::ORDER_CANCELLED => 'OrderCancelled',
        ];
    }
}
