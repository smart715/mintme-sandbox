<?php declare(strict_types = 1);

namespace App\Communications\SMS;

use App\Communications\SMS\Model\SMS;

interface D7NetworksCommunicatorInterface
{
    public function send(SMS $SMS): array;

    public function getBalance(): array;
}
