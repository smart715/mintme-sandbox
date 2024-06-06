<?php declare(strict_types = 1);

namespace App\Events;

use App\Activity\ActivityTypes;
use App\Entity\Token\Token;
use App\Entity\Token\TokenDeploy;
use App\Events\Activity\TokenEventActivity;

/** @codeCoverageIgnore */
class ConnectCompletedEvent extends TokenEventActivity
{
    protected TokenDeploy $tokenDeploy;

    public function __construct(Token $token, TokenDeploy $tokenDeploy)
    {
        parent::__construct($token, ActivityTypes::TOKEN_CONNECTED);

        $this->tokenDeploy = $tokenDeploy;
    }

    public function getTokenDeploy(): TokenDeploy
    {
        return $this->tokenDeploy;
    }
}
