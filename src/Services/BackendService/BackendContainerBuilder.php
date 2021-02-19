<?php declare(strict_types = 1);

namespace App\Services\BackendService;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class BackendContainerBuilder implements BackendContainerBuilderInterface
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function createContainer(Request $request): ?string
    {
        $host = $request->getHttpHost();
        $hostExploded =  explode('.', $host);
        $branch = $hostExploded[0];

        $process = new Process(['sudo', 'create-branch.sh', $branch]);

        try {
            $process->mustRun();

            echo $process->getOutput();
        } catch (ProcessFailedException $exception) {
            $this->logger->error('Failed to create container services for the branch '.$branch.' Reason: '
                .$exception->getMessage());
        }

        return $process->getOutput();
    }

    public function deleteContainer(Request $request): ?string
    {
        $host = $request->getHttpHost();
        $hostExploded =  explode('.', $host);
        $branch = $hostExploded[0];
        $process = new Process(['sudo', 'delete-branch.sh', $branch]);

        try {
            $process->mustRun(function ($type, $buffer): void {
                if (Process::ERR === $type) {
                    echo 'ERR > '.$buffer;
                } else {
                    echo 'OUT > '.$buffer;
                }
            });

            return $process->getOutput();
        } catch (\Throwable $exception) {
            $this->logger->error(
                'Failed to delete container services for the '.$branch.' branch. Reason: '
                .$exception->getMessage()
            );

            return null;
        }
    }

    public function getStatusContainer(Request $request): ?string
    {
        $host = $request->getHttpHost();
        $hostExploded =  explode('.', $host);
        $branch = $hostExploded[0];
        $process = new Process(['sudo', 'list-branch.sh', '-I', $branch]);

        try {
            $process->mustRun();

            return $process->getOutput();
        } catch (\Throwable $exception) {
            $this->logger->error('Failed to delete container services for the'.$branch. ' branch. Reason: '
                .$exception->getMessage());

            return null;
        }
    }
}
