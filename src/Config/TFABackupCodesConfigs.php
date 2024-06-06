<?php declare(strict_types = 1);

namespace App\Config;

/** @codeCoverageIgnore  */
class TFABackupCodesConfigs extends ValidationCodeConfigs
{
    protected array $availableConfigs = [parent::SMS]; //phpcs:ignore
}
