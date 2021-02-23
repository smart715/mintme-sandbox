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
            $process->mustRun(function ($type, $buffer): void {
                if (Process::ERR === $type) {
                    echo 'ERR > '.$buffer;
                    $this->logger->error('CAMILO', $buffer);
                } else {
                    echo 'OUT > '.$buffer;
                    $this->logger->info('CAMILO', $buffer);
                }
            });
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

    public function getStatusContainer(Request $request): ?int
    {
        $host = $request->getHttpHost();
        $hostExploded =  explode('.', $host);
        $branch = $hostExploded[0];
        $this->logger->error('testing gustavo'.$this->isManagingBackendServices($branch));

        if ('1' === $this->isManagingBackendServices($branch)) {
            return 2;
        }

        $process = new Process(['sudo', 'list-branch.sh', '-I', $branch]);

        try {
            $process->mustRun();

            return false !== strpos($process->getOutput(), 'no matched branch was found')
                ? 0
                : 1;
        } catch (\Throwable $exception) {
            $this->logger->error('Failed getting the container status for the'.$branch. ' branch. Reason: '
                .$exception->getMessage());

            return null;
        }
    }

    private function isManagingBackendServices(string $branch): string
    {
        $checkLockFilesCommand = '[^-f^/tmp/delete-branch-%branch%.lock^]^'
            .'||^[^-f^/tmp/create-branch-%branch%.lock^]^&&^echo^1^||^echo^0';
        $lockFilesCommands = str_replace('%branch%', $branch, $checkLockFilesCommand);
        $checkLockFilesProcess = new Process(explode('^', $lockFilesCommands));

        try {
            $checkLockFilesProcess->mustRun();

            return $checkLockFilesProcess->getOutput();
        } catch (\Throwable $exception) {
            $this->logger->error(
                'Failed getting the lock file for  '.$branch.' branch. Reason: '
                .$exception->getMessage()
            );

            return '';
        }
    }
}
