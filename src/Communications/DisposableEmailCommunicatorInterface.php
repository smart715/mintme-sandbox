<?php declare(strict_types = 1);

namespace App\Communications;

interface DisposableEmailCommunicatorInterface
{
    /**
     * @return bool
     * @param mixed $email
     */
    public function checkDisposable($email): bool;
}
