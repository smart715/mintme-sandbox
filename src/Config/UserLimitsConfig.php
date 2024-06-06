<?php declare(strict_types = 1);

namespace App\Config;

/** @codeCoverageIgnore  */
class UserLimitsConfig
{
    private array $userLimit;

    public function __construct(array $userLimit)
    {
        $this->userLimit = $userLimit;
    }

    public function getMaxPostsLimit(): int
    {
        return $this->userLimit['posts_max_per_day'];
    }

    public function getMaxCommentsLimit(): int
    {
        return $this->userLimit['comments_max_per_day'];
    }

    public function getMaxVotingsLimit(): int
    {
        return $this->userLimit['votings_max_per_day'];
    }

    public function getMaxClientsLimit(): int
    {
        return $this->userLimit['oauth_keys_limit'];
    }

    public function getMonthlyBackupCodesLimit(): int
    {
        return $this->userLimit['backup_codes_monthly_limit'];
    }

    public function getMaxLikesLimit(): int
    {
        return $this->userLimit['likes_max_per_day'] ?? 30;
    }
}
