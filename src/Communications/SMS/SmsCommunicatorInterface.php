<?php declare(strict_types = 1);

namespace App\Communications\SMS;

use App\Communications\SMS\Model\SMS;

interface SmsCommunicatorInterface
{
    public function send(SMS $sms): array;
    public function getBalance(): array;
}
