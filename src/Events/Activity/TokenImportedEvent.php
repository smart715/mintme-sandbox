<?php declare(strict_types = 1);

namespace App\Events\Activity;

use App\Activity\ActivityTypes;
use App\Events\ConnectCompletedEvent;

/** @codeCoverageIgnore */
class TokenImportedEvent extends ConnectCompletedEvent
{
    public const NAME = 'token.imported.activity';

    public function getType(): int
    {
        return ActivityTypes::TOKEN_ADDED;
    }
}
