<?php declare(strict_types = 1);

namespace App\Tests\Controller\Traits;

use App\Controller\Traits\CheckTokenNameBlacklistTrait;
use App\Manager\BlacklistManagerInterface;

// phpcs:disable SlevomatCodingStandard.Classes.UnusedPrivateElements
class CheckTokenNameBlacklist
{

    use CheckTokenNameBlacklistTrait;

    /** @var BlacklistManagerInterface */
    private $blacklistManager;

    public function __construct(BlacklistManagerInterface $blacklistManager)
    {
        $this->blacklistManager = $blacklistManager;
    }

    public function test(string $name): bool
    {
        return $this->checkTokenNameBlacklist($name);
    }
}
