<?php declare(strict_types = 1);

namespace App\Services\BackendService;

use Psr\Log\LoggerInterface;
use Symfony\Component\Process\Process;

class BackendContainerBuilder implements BackendContainerBuilderInterface
{
    private const CREATE_CONTAINER = ['sudo', 'create-branch.sh'];
    private const DELETE_CONTAINER = ['sudo', 'delete-branch.sh'];
    private const STATUS_CONTAINER = ['sudo', 'list-branch.sh', '-I'];

    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function createContainer(string $branch): ?string
    {
        $process = new Process(self::CREATE_CONTAINER);

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
        $process = new Process(self::DELETE_CONTAINER);

        try {
            $process->mustRun();

            return $process->getOutput();
        } catch (\Throwable $exception) {
            $this->logger->error('Failed to delete container services for the'.$branch. ' branch. 
            Reason: '.$exception->getMessage());

            return null;
        }
    }

    public function getStatusContainer(string $branch): void
    {
        // TODO: Implement getStatusContainer() method.
    }
}
