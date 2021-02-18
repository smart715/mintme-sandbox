<?php declare(strict_types = 1);

namespace App\Services\BackendService;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Process\Process;

class BackendContainerBuilder implements BackendContainerBuilderInterface
{
    private LoggerInterface $logger;
    private bool $isTestingServer;

    public function __construct(LoggerInterface $logger, bool $isTestingServer)
    {
        $this->logger = $logger;
        $this->isTestingServer = $isTestingServer;
    }

    public function createContainer(Request $request): ?string
    {
        if (!$this->isTestingServer) {
            return null;
        }

        $host = $request->getHttpHost();
        $hostExploded =  explode('.', $host);
        $branch = $hostExploded[1];
        $process = new Process(['sudo', 'create-branch.sh', $branch]);

        try {
            $process->mustRun();

            return $process->getOutput();
        } catch (\Throwable $exception) {
            $this->logger->error('Failed to create container services for the'.$branch.' branch. Reason: '
                .$exception->getMessage());

            return null;
        }
    }

    public function deleteContainer(Request $request): ?string
    {
        if (!$this->isTestingServer) {
            return null;
        }

        $host = $request->getHttpHost();
        $hostExploded =  explode('.', $host);
        $branch = $hostExploded[1];
        $process = new Process(['sudo', 'delete-branch.sh', $branch]);

        try {
            $process->mustRun();

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
        if (!$this->isTestingServer) {
            return null;
        }

        $host = $request->getHttpHost();
        $hostExploded =  explode('.', $host);
        $branch = $hostExploded[1];
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
