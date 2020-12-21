<?php declare(strict_types = 1);

namespace App\Utils\ServiceInfo;

use App\Communications\RabbitMQCommunicatorInterface;
use App\Manager\TokenManagerInterface;
use App\SmartContract\ContractHandlerInterface;
use App\Utils\ServiceInfo\Model\ServiceInfo;
use Symfony\Component\Process\Process;

class ServiceInfoBuilder implements ServiceInfoBuilderInterface
{
    /** @var TokenManagerInterface */
    private $tokenManager;

    /** @var ServiceInfo */
    private $serviceInfo;

    /** @var RabbitMQCommunicatorInterface */
    private $rabbitCommunicator;

    /** @var ContractHandlerInterface */
    private $contractHandler;

    /** @var string  */
    private $depositWorkDir;

    /** @var string */
    private $withdrawWorkDir;

    /** @var string */
    private $contractWorkDir;

    public function __construct(
        string $depositWorkDir,
        string $withdrawWorkDir,
        string $contractWorkDir,
        TokenManagerInterface $tokenManager,
        RabbitMQCommunicatorInterface $rabbitCommunicator,
        ContractHandlerInterface $contractHandler
    ) {
        $this->depositWorkDir = $depositWorkDir;
        $this->withdrawWorkDir = $withdrawWorkDir;
        $this->contractWorkDir = $contractWorkDir;
        $this->tokenManager = $tokenManager;
        $this->rabbitCommunicator = $rabbitCommunicator;
        $this->contractHandler = $contractHandler;
        $this->serviceInfo = new ServiceInfo();
    }

    public function addMintmeTokenInfo(): void
    {
        $token = $this->tokenManager->getOwnMintmeToken();

        $this->serviceInfo->setTokenName(
            $token ? $token->getName() : null
        );
    }

    public function addGitInfo(): void
    {
        $this->serviceInfo
            ->setPanelBranch($this->getGitBranch('../'))
            ->setDepositBranch($this->getGitBranch($this->depositWorkDir))
            ->setWithdrawBranch($this->getGitBranch($this->withdrawWorkDir))
            ->setContractBranch($this->getGitBranch($this->contractWorkDir));
    }

    public function addConsumersInfo(): void
    {
        $this->serviceInfo->setConsumersInfo($this->rabbitCommunicator->fetchConsumers());
    }

    public function getServiceInfo(): ServiceInfo
    {
        return $this->serviceInfo;
    }

    private function getGitBranch(string $path): ?string
    {
        $process = new Process(['git', 'rev-parse', '--abbrev-ref', 'HEAD'], $path);

        try {
            $process->mustRun();

            return $process->getOutput();
        } catch (\Throwable $exception) {
            return null;
        }
    }

    public function addServicesStatus(): void
    {
        $this->serviceInfo->setIsTokenContractActive($this->contractHandler->ping());
    }
}
