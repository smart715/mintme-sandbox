<?php declare(strict_types = 1);

namespace App\Services\BackendService;

use Symfony\Component\Process\Process;

class AsyncProcess extends Process
{
    public function __destruct()
    {
    }
}
