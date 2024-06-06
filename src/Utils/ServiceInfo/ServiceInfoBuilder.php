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
    private const SERVER_COMMAND = 'sudo^ssh^-t^-p^2279^mintme@%url%^'
        .'lxc exec branch-%branch% -- sh -c "su mintme-gateway -c \'git -C %path% branch --show-current\'"';
    private TokenManagerInterface $tokenManager;
    private ServiceInfo $serviceInfo;
    private RabbitMQCommunicatorInterface $rabbitCommunicator;
    private ContractHandlerInterface $contractHandler;
    private string $serviceContainerIp;
    private string $gatewayWorkDir;
    private bool $isTestingServer;
    private ?string $panelBranch;
    private LoggerInterface $logger;

    public function __construct(
        string $serviceContainerIp,
        string $gatewayWorkDir,
        bool $isTestingServer,
        TokenManagerInterface $tokenManager,
        RabbitMQCommunicatorInterface $rabbitCommunicator,
        ContractHandlerInterface $contractHandler,
        LoggerInterface $logger
    ) {
        $this->serviceContainerIp = $serviceContainerIp;
        $this->gatewayWorkDir = $gatewayWorkDir;
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
            ->setGatewayBranch($this->getGitBranch(
                $this->gatewayWorkDir,
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
            $search = ['%path%', '%url%'];
            $replace = [$path, $this->serviceContainerIp];
            $externalCommand = str_replace($search, $replace, $externalCommand);

            $externalCommand = $this->panelBranch
                ? str_replace('%branch%', $this->panelBranch, $externalCommand)
                : $externalCommand;

            $command = explode('^', $externalCommand);
            $process = new Process($command);
        } elseif ($externalContainer) {
            $process = new Process(
                ['git', 'branch', '--show-current'],
                str_replace('%branch%', $this->panelBranch, $path)
            );
        } else {
            $process = new Process(['git', 'branch', '--show-current'], $path);
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
        try {
            $this->serviceInfo->setIsGatewayActive($this->contractHandler->ping());
        } catch (\Throwable $exception) {
            $this->logger->error('Failed to ping for gateway status. Reason: '.$exception->getMessage());
            
            $this->serviceInfo->setIsGatewayActive(false);
        }
    }
}
