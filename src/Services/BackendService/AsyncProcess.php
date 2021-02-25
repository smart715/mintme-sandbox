<?php declare(strict_types = 1);

namespace App\Services\BackendService;

use Symfony\Component\Process\Process;

class AsyncProcess extends Process
{
    /**
     * Avoid stopping the running process when SIGTERM is received
     */
    public function __destruct()
    {
    }
}
