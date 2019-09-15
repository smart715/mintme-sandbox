<?php declare(strict_types = 1);

namespace App\SmartContract;

use App\Entity\Token\Token;
use App\Entity\User;

interface DeploymentProcessInterface
{
    public function execute(User $user, Token $token): void;
}
