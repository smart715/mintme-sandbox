<?php declare(strict_types = 1);

namespace App\Command\Crypto;

use App\Entity\Crypto;
use App\Manager\CryptoManagerInterface;
use App\Manager\WrappedCryptoTokenManagerInterface;
use App\SmartContract\ContractHandlerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class UpdateCryptoNetworkStatus extends Command
{
    public const CRYPTO_OPT = 'crypto';
    public const CRYPTO_BLOCKCHAIN_OPT = 'blockchain';
    public const ENABLED_OPT= 'enabled';
    public const RUN_DELAYED_TRANSACTIONS= 'run-delayed-transactions';

    private WrappedCryptoTokenManagerInterface $wrappedCryptoTokenManager;
    private ContractHandlerInterface $contractHandler;
    private EntityManagerInterface $entityManager;
    private CryptoManagerInterface $cryptoManager;

    public function __construct(
        WrappedCryptoTokenManagerInterface $wrappedCryptoTokenManager,
        ContractHandlerInterface $contractHandler,
        EntityManagerInterface $entityManager,
        CryptoManagerInterface $cryptoManager
    ) {
        $this->contractHandler = $contractHandler;
        $this->wrappedCryptoTokenManager = $wrappedCryptoTokenManager;

        parent::__construct();
        $this->entityManager = $entityManager;
        $this->cryptoManager = $cryptoManager;
    }

    protected function configure(): void
    {
        $this
            ->setName('app:update-crypto-network-status')
            ->setDescription('Disable or enable crypto network')
            ->addOption(
                self::CRYPTO_OPT,
                null,
                InputOption::VALUE_REQUIRED,
                'Crypto symbol for which network should be disabled'
            )
            ->addOption(
                self::CRYPTO_BLOCKCHAIN_OPT,
                null,
                InputOption::VALUE_OPTIONAL,
                'Crypto symbol of network you want to disable. If not specified, all external networks of selected crypto will be disabled'
            )
            ->addOption(self::ENABLED_OPT, null, InputOption::VALUE_REQUIRED, '1 to enable, 0 to disable (default)')
            ->addOption(
                self::RUN_DELAYED_TRANSACTIONS,
                null,
                InputOption::VALUE_NONE,
                'Run delayed transactions for crypto network(only when enabling)'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $this->entityManager->beginTransaction();

        /** @var string $cryptoSymbol */
        $cryptoSymbol = $input->getOption(self::CRYPTO_OPT);
        /** @var string|null $blockchainSymbol */
        $blockchainSymbol = $input->getOption(self::CRYPTO_BLOCKCHAIN_OPT);
        $enabled = (bool)$input->getOption(self::ENABLED_OPT);
        $runDelayedTransactions = (bool)$input->getOption(self::RUN_DELAYED_TRANSACTIONS);

        $crypto = $this->cryptoManager->findBySymbol($cryptoSymbol);
        $cryptoDeploy = $blockchainSymbol
            ? $this->cryptoManager->findBySymbol($blockchainSymbol)
            : null;

        try {
            if (!$crypto) {
                $io->error("Crypto ${cryptoSymbol} symbol doesn't exist.");

                return 1;
            }

            if ($blockchainSymbol && !$cryptoDeploy) {
                $io->error(
                    "Wrong crypto ${blockchainSymbol} blockchain symbol provided. It doesn't exists in db. Aborting."
                );

                return 1;
            }

            return $this->updateCryptoNetworkStatus($io, $crypto, $cryptoDeploy, $enabled, $runDelayedTransactions);
        } catch (\Throwable $exception) {
            $this->entityManager->rollback();

            $io->warning('Failed to update crypto network status');
            $io->error($exception->getMessage());
            $io->error($exception->getTraceAsString());

            return 1;
        }
    }

    private function updateCryptoNetworkStatus(
        SymfonyStyle $io,
        Crypto $crypto,
        ?Crypto $cryptoNetwork,
        bool $enabled,
        bool $runDelayedTransactions
    ): int {
        $cryptoSymbol = $crypto->getSymbol();

        $wrappedCryptoToken = $cryptoNetwork
            ? $this->wrappedCryptoTokenManager->findByCryptoAndDeploy($crypto, $cryptoNetwork)
            : null;

        if ($cryptoNetwork && !$wrappedCryptoToken) {
            $cryptoNetworkSymbol = $cryptoNetwork->getSymbol();
            $io->error("Crypto ${cryptoSymbol} doesn't exists in ${cryptoNetworkSymbol} blockchain. Aborting...");

            return 1;
        }

        if (!$wrappedCryptoToken) {
            $io->writeln("Crypto network wasn't provided. Updating all networks status for ${cryptoSymbol} crypto...");
        } else {
            $io->writeln("Updating network status for ${cryptoSymbol} crypto...");
        }

        $io->writeln("Calling update token status for gateway...");
        $this->contractHandler->updateTokenStatus($crypto, $cryptoNetwork, $enabled, $runDelayedTransactions);

        $io->writeln("Updating statuses in panel db...");

        $updateMsg = $enabled
            ? 'enabled'
            : 'disabled';

        if ($wrappedCryptoToken) {
            if ($enabled === $wrappedCryptoToken->isEnabled()) {
                $io->warning("Crypto network already ${updateMsg}. Aborting...");

                return 1;
            }

            $this->wrappedCryptoTokenManager->updateWrappedCryptoTokenStatus($wrappedCryptoToken, $enabled);
            $cryptoNetworkSymbol = $cryptoNetwork->getSymbol();

            $io->success("${cryptoNetworkSymbol} network for ${cryptoSymbol} crypto was ${updateMsg}");
        } else {
            $this->wrappedCryptoTokenManager->updateCryptoStatuses($crypto, $enabled);

            $io->success("All networks for ${cryptoSymbol} crypto was ${updateMsg}");
        }

        $this->entityManager->commit();

        return 0;
    }
}
