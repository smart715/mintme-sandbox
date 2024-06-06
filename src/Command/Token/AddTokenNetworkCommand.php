<?php declare(strict_types = 1);

namespace App\Command\Token;

use App\Entity\Crypto;
use App\Entity\Token\Token;
use App\Entity\Token\TokenDeploy;
use App\Events\Activity\TokenImportedEvent;
use App\Manager\CryptoManagerInterface;
use App\Manager\TokenDeployManagerInterface;
use App\Manager\TokenManagerInterface;
use App\SmartContract\ContractHandlerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class AddTokenNetworkCommand extends Command
{
    private EntityManagerInterface $entityManager;
    private CryptoManagerInterface $cryptoManager;
    private TokenManagerInterface $tokenManager;
    private TokenDeployManagerInterface $tokenDeployManager;
    private ContractHandlerInterface $contractHandler;
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        EntityManagerInterface $entityManager,
        CryptoManagerInterface $cryptoManager,
        TokenManagerInterface $tokenManager,
        TokenDeployManagerInterface $tokenDeployManager,
        ContractHandlerInterface $contractHandler,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->entityManager = $entityManager;
        $this->cryptoManager = $cryptoManager;
        $this->tokenManager = $tokenManager;
        $this->tokenDeployManager = $tokenDeployManager;
        $this->contractHandler = $contractHandler;
        $this->eventDispatcher = $eventDispatcher;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('app:connect-token-network')
            ->setDescription('Connect token to the new network')
            ->addOption(
                'tokenName',
                null,
                InputOption::VALUE_REQUIRED,
                'Name of the token for which you want to connect to the new network'
            )
            ->addOption(
                'blockchain',
                null,
                InputOption::VALUE_REQUIRED,
                'Crypto symbol of network you want to add token'
            )
            ->addOption(
                'address',
                null,
                InputOption::VALUE_REQUIRED,
                'Token address on the new network'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $tokenName = $input->getOption('tokenName');
        $cryptoSymbol = strtoupper($input->getOption('blockchain'));
        $tokenAddress = $input->getOption('address');

        $token = $this->tokenManager->findByName($tokenName);

        if (!$token) {
            $io->error("Token with ${tokenName} name doesn't exist.");

            return 1;
        }

        $crypto = $this->cryptoManager->findBySymbol($cryptoSymbol);

        if (!$crypto) {
            $io->error("Network (crypto) with ${cryptoSymbol} symbol doesn't exist.");

            return 1;
        }

        if (!is_string($tokenAddress) || '' === $tokenAddress) {
            $io->error('Token address should be a string and not empty.');

            return 1;
        }

        try {
            $this->entityManager->beginTransaction();

            $tokenDeploy = $this->tokenDeployManager->findByAddressAndCrypto($tokenAddress, $crypto);

            if ($tokenDeploy) {
                $io->error('Token with this address already exists for provided network. Aborting...');

                return 1;
            }

            $this->addNewNetworkForToken($io, $token, $crypto, $tokenAddress);
            $this->entityManager->commit();

            $io->success('Token has been connected to the new network!');

            return 0;
        } catch (\Throwable $exception) {
            $this->entityManager->rollback();

            $io->error('Failed to connect token to the new network');
            $io->error($exception->getMessage());

            return 1;
        }
    }

    private function addNewNetworkForToken(
        SymfonyStyle $io,
        Token $token,
        Crypto $crypto,
        string $address
    ): void {
        $tokenName = $token->getName();
        $cryptoName = $crypto->getName();

        $io->writeln("Adding ${tokenName} token on the ${cryptoName} network to gateway...");
        $addTokenResult = $this->contractHandler->addToken($token, $crypto, $address, null);

        if ($addTokenResult->alreadyExisted()) {
            $io->warning("Token ${tokenName} already exists on ${cryptoName} network.");
        }

        $io->writeln('Connecting token to the new network in db...');

        $deploy = (new TokenDeploy())
            ->setToken($token)
            ->setCrypto($crypto)
            ->setAddress($address)
            ->setDeployDate(new \DateTimeImmutable());

        $token->addDeploy($deploy);

        $this->entityManager->persist($token);
        $this->entityManager->flush();
        $this->eventDispatcher->dispatch(
            new TokenImportedEvent($token, $deploy),
            TokenImportedEvent::NAME
        );
    }
}
