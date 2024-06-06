<?php declare(strict_types = 1);

namespace App\Exchange\Balance;

abstract class BalanceTransactionBonusType
{
    public const POST_SHARE = 'post_share';
    public const AIRDROP_REWARD = 'withdrawal';
    public const AIRDROP_REFFERAL = 'new_investor';
    public const REWARD_PARTICIPATE = 'reward_participate';
    public const MOVE_MAIN_BALANCE = 'move_main_balance';
    public const SIGN_UP = 'sign_up';
    public const DEPLOY_TOKEN = 'deploy_token';
}
