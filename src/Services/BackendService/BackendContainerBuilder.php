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

    public function __construct(LoggerInterface $logger, KernelInterface $kernel)
    {
        $this->logger = $logger;
        $this->kernel = $kernel;
    }

    public function createContainer(Request $request): void
    {
        $host = $request->getHttpHost();
        $hostExploded =  explode('.', $host);
        $branch = $hostExploded[0];

        $process =  new AsyncProcess(['sudo', 'create-branch-7116.sh', $branch]);

        try {
            $this->setMaintenanceMode('block');

            $process->start();

            $process->wait(function ($type, $buffer): void {
                if (Process::ERR === $type) {
                    $this->logger->error('CREATING-ERROR > '.$buffer);
                } else {
                    $this->logger->error('CREATING-OUTPUT > '.$buffer);
                }
            });
            $this->setMaintenanceMode('unblock');
        } catch (ProcessFailedException $exception) {
            $this->logger->error('Failed to create container services for the branch '.$branch.' Reason: '
                .$exception->getMessage());
        }
    }

    public function deleteContainer(Request $request): ?string
    {
        $host = $request->getHttpHost();
        $hostExploded =  explode('.', $host);
        $branch = $hostExploded[0];
        $process = new AsyncProcess(['sudo', 'delete-branch.sh', $branch]);

        try {
            $this->setMaintenanceMode('block');
            $process->start();

            $process->wait(function ($type, $buffer): void {
                if (Process::ERR === $type) {
                    $this->logger->error('DELETING-ERROR > '.$buffer);
                } else {
                    $this->logger->error('DELETING-OUTPUT > '.$buffer);
                }
            });
            $this->setMaintenanceMode('unblock');
        } catch (\Throwable $exception) {
            $this->logger->error(
                'Failed to delete container services for the '.$branch.' branch. Reason: '
                .$exception->getMessage()
            );
        }

        return $process->getOutput();
    }

    public function getStatusContainer(Request $request): ?int
    {
        $host = $request->getHttpHost();
        $hostExploded =  explode('.', $host);
        $branch = $hostExploded[0];

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

    private function setMaintenanceMode(?string $type): void
    {
        $workDir = $this->kernel->getProjectDir();
        $process = 'unblock' === $type ? new Process(['touch', $workDir.'/maintenance_on']) :
            new Process(['rm', '-Rf', $workDir.'/maintenance_on']);

        try {
            $process->mustRun();
            echo $process->getOutput();
        } catch (ProcessFailedException $exception) {
            $this->logger->error('Failed to set maintenance mode, Reason: '
                .$exception->getMessage());
        }
    }
}
