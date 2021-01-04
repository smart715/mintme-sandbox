<?php declare(strict_types = 1);

namespace App\Communications;

interface D7NetworksCommunicatorInterface
{
    public function send(string $content): array;
}
