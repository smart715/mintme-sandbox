<?php declare(strict_types = 1);

namespace App\Utils\ServiceInfo;

use App\Communications\RabbitMQCommunicatorInterface;
use App\Manager\TokenManagerInterface;
use App\SmartContract\ContractHandlerInterface;
use App\Utils\ServiceInfo\Model\ServiceInfo;
use Psr\Log\LoggerInterface;
use Symfony\Component\Process\Process;

class ServiceInfoBuilder implements ServiceInfoBuilderInterface
{
    private const SERVER_COMMAND = 'sudo^ssh^-t^-p^2279^mintme@10.81.143.1^'
        .'lxc exec branch-%branch% -- bash -c \'git -C %path% rev-parse --abbrev-ref HEAD\'';
    private TokenManagerInterface $tokenManager;
    private ServiceInfo $serviceInfo;
    private RabbitMQCommunicatorInterface $rabbitCommunicator;
    private ContractHandlerInterface $contractHandler;
    private string $depositWorkDir;
    private string $withdrawWorkDir;
    private string $contractWorkDir;
    private bool $isTestingServer;
    private ?string $panelBranch;
    private LoggerInterface $logger;

    public function __construct(
        string $depositWorkDir,
        string $withdrawWorkDir,
        string $contractWorkDir,
        bool $isTestingServer,
        TokenManagerInterface $tokenManager,
        RabbitMQCommunicatorInterface $rabbitCommunicator,
        ContractHandlerInterface $contractHandler,
        LoggerInterface $logger
    ) {
        $this->depositWorkDir = $depositWorkDir;
        $this->withdrawWorkDir = $withdrawWorkDir;
        $this->contractWorkDir = $contractWorkDir;
        $this->tokenManager = $tokenManager;
        $this->rabbitCommunicator = $rabbitCommunicator;
        $this->contractHandler = $contractHandler;
        $this->serviceInfo = new ServiceInfo();
        $this->isTestingServer = $isTestingServer;
        $this->logger = $logger;
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
        $this->panelBranch = str_replace(
            PHP_EOL,
            '',
            str_replace('.', '', $this->getGitBranch('../'))
        );

        $this->serviceInfo
            ->setPanelBranch($this->panelBranch)
            ->setDepositBranch($this->getGitBranch(
                $this->depositWorkDir,
                self::SERVER_COMMAND,
                $this->isTestingServer
            ))
            ->setWithdrawBranch($this->getGitBranch(
                $this->withdrawWorkDir,
                null,
                true
            ))
            ->setContractBranch($this->getGitBranch(
                $this->contractWorkDir,
                self::SERVER_COMMAND,
                $this->isTestingServer
            ));
    }

    public function addConsumersInfo(): void
    {
        $this->serviceInfo->setConsumersInfo($this->rabbitCommunicator->fetchConsumers());
    }

    public function getServiceInfo(): ServiceInfo
    {
        return $this->serviceInfo;
    }

    private function getGitBranch(
        string $path,
        ?string $externalCommand = null,
        bool $externalContainer = false
    ): ?string {
        if ($externalCommand && $externalContainer) {
            $externalCommand = str_replace('%path%', $path, $externalCommand);
            $externalCommand = $this->panelBranch
                ? str_replace('%branch%', $this->panelBranch, $externalCommand)
                : $externalCommand;

            $command = explode('^', $externalCommand);
            $process = new Process($command);
        } elseif ($externalContainer) {
            $process = new Process(
                ['git', 'rev-parse', '--abbrev-ref', 'HEAD'],
                str_replace('%branch%', $this->panelBranch, $path)
            );
        } else {
            $process = new Process(['git', 'rev-parse', '--abbrev-ref', 'HEAD'], $path);
        }

        try {
            $process->mustRun();

            return $process->getOutput();
        } catch (\Throwable $exception) {
            $this->logger->error('Failed to fetch git branch for '.$this->panelBranch. ' branch'.
                'with path: '.$path.'. Reason: '.$exception->getMessage());

            return null;
        }
    }

    public function addServicesStatus(): void
    {
        $this->serviceInfo->setIsTokenContractActive($this->contractHandler->ping());
    }
}
