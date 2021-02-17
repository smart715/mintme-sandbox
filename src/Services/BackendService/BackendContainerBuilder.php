<?php declare(strict_types = 1);

namespace App\Services\BackendService;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Process\Process;

class BackendContainerBuilder implements BackendContainerBuilderInterface
{
    private LoggerInterface $logger;
    private bool $isTestingServer;
    private KernelInterface $environment;

    public function __construct(LoggerInterface $logger, bool $isTestingServer, KernelInterface $environment)
    {
        $this->logger = $logger;
        $this->isTestingServer = $isTestingServer;
        $this->environment = $environment;
    }

    public function createContainer(Request $request): ?string
    {
        if ('dev' === $this->environment->getEnvironment() && !$this->isTestingServer) {
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
            $this->logger->error('Failed to create container services for the'.$branch. ' branch. 
            Reason: '.$exception->getMessage());

            return null;
        }
    }

    public function deleteContainer(string $branch): ?string
    {
        $process = new Process(['sudo', 'delete-branch.sh', $branch]);

        try {
            $process->mustRun();

            return $process->getOutput();
        } catch (\Throwable $exception) {
            $this->logger->error('Failed to delete container services for the'.$branch. ' branch. 
            Reason: '.$exception->getMessage());

            return null;
        }
    }

    public function getStatusContainer(string $branch): ?string
    {
        $process = new Process(['sudo', 'list-branch.sh', '-I', $branch]);

        try {
            $process->mustRun();

            return $process->getOutput();
        } catch (\Throwable $exception) {
            $this->logger->error('Failed to delete container services for the'.$branch. ' branch. 
            Reason: '.$exception->getMessage());

            return null;
        }
    }
}
