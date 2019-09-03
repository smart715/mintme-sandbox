<?php declare(strict_types = 1);

namespace App\Communications;

interface DisposableEmailCommunicatorInterface
{
    /**
     * @return bool
     * @param string $email
     */
    public function checkDisposable(string $email): bool;
}
