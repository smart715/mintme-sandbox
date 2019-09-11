<?php declare(strict_types = 1);

namespace App\Communications;

interface DisposableEmailCommunicatorInterface
{
    public function checkDisposable(?string $email): bool;
}
