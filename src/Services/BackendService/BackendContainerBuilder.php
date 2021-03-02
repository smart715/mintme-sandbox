<?php declare(strict_types = 1);

namespace App\Services\BackendService;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class BackendContainerBuilder implements BackendContainerBuilderInterface
{
    private LoggerInterface $logger;

    private KernelInterface $kernel;

    public function __construct(
        LoggerInterface $logger,
        KernelInterface $kernel
    ) {
        $this->logger = $logger;
        $this->kernel = $kernel;
    }

    public function createContainer(Request $request): void
    {
        $host = $request->getHttpHost();
        $hostExploded =  explode('.', $host);
        $branch = $hostExploded[0];
        $process =  new Process(['sudo', 'create-branch.sh', $branch]);

        try {
            $process->setTimeout(3600);
            $process->mustRun();

            if ($process->isSuccessful()) {
                $this->setMaintenanceMode('unblock');
            }
        } catch (ProcessFailedException $exception) {
            $this->logger->error('Failed to create container services for the branch  Reason: '
                .$exception->getMessage());
        }
    }

    public function deleteContainer(Request $request): void
    {
        $host = $request->getHttpHost();
        $hostExploded =  explode('.', $host);
        $branch = $hostExploded[0];
        $process = new Process(['sudo', 'delete-branch.sh', $branch]);

        try {
            $process->setTimeout(3600);
            $process->mustRun();

            if ($process->isSuccessful()) {
                $this->setMaintenanceMode('unblock');
            }
        } catch (\Throwable $exception) {
            $this->logger->error(
                'Failed to delete container services for the '.$branch.' branch. Reason: '
                .$exception->getMessage()
            );
        }
    }

    public function getStatusContainer(Request $request): ?int
    {
        $host = $request->getHttpHost();
        $hostExploded =  explode('.', $host);
        $branch = $hostExploded[0];

        $process = new Process(['sudo', 'list-branch.sh', '-I', $branch]);

        try {
            $process->mustRun();

            if ($process->isSuccessful()) {
                return false !== strpos($process->getOutput(), 'no matched branch was found')
                    ? 0
                    : 1;
            }

            return null;
        } catch (\Throwable $exception) {
            $this->logger->error('Failed getting the container status for the'.$branch. ' branch. Reason: '
                .$exception->getMessage());

            return null;
        }
    }

    public function setMaintenanceMode(string $mode): ?string
    {
        $workDir = $this->kernel->getProjectDir();
        $process = 'block' === $mode ? new Process(['touch', $workDir.'/maintenance_on']) :
            new Process(['rm', '-Rf', $workDir.'/maintenance_on']);

        try {
            $process->start();

            $process->wait();

            return $process->isSuccessful()
                ? 'OK'
                : null;
        } catch (ProcessFailedException $exception) {
            $this->logger->error('Failed to set maintenance mode, Reason: '
                .$exception->getMessage());

            return null;
        }
    }
}
