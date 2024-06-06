<?php declare(strict_types = 1);

namespace App\Command\Crypto;

use App\Entity\Crypto;
use App\Entity\WrappedCryptoToken;
use App\Manager\CryptoManagerInterface;
use App\Manager\WrappedCryptoTokenManagerInterface;
use App\SmartContract\ContractHandlerInterface;
use App\Wallet\Money\MoneyWrapperInterface;
use Doctrine\ORM\EntityManagerInterface;
use Money\Currency;
use Money\Money;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class AddCryptoNetworkCommand extends Command
{
    private const CRYPTO_OPT = 'crypto';
    private const CRYPTO_BLOCKCHAIN_OPT = 'blockchain';
    private const ADDRESS_OPT = 'address';
    private const FEE_OPT = 'fee';

    private WrappedCryptoTokenManagerInterface $wrappedCryptoTokenManager;
    private CryptoManagerInterface $cryptoManager;
    private EntityManagerInterface $entityManager;
    private ContractHandlerInterface $contractHandler;
    private MoneyWrapperInterface $moneyWrapper;

    public function __construct(
        WrappedCryptoTokenManagerInterface $wrappedCryptoTokenManager,
        CryptoManagerInterface $cryptoManager,
        EntityManagerInterface $entityManager,
        ContractHandlerInterface $contractHandler,
        MoneyWrapperInterface $moneyWrapper
    ) {
        $this->wrappedCryptoTokenManager = $wrappedCryptoTokenManager;
        $this->cryptoManager = $cryptoManager;
        $this->entityManager = $entityManager;
        $this->contractHandler = $contractHandler;
        $this->moneyWrapper = $moneyWrapper;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('app:add-crypto-network')
            ->setDescription('Add crypto network')
            ->addOption(self::CRYPTO_OPT, null, InputOption::VALUE_REQUIRED, 'Crypto symbol that you want to add')
            ->addOption(
                self::CRYPTO_BLOCKCHAIN_OPT,
                null,
                InputOption::VALUE_REQUIRED,
                'Crypto symbol of networks you want to add new crypto'
            )
            ->addOption(self::ADDRESS_OPT, null, InputOption::VALUE_REQUIRED, 'Address')
            ->addOption(self::FEE_OPT, null, InputOption::VALUE_REQUIRED, 'Withdrawal fee. For ex.: 0.01, 0.1, 1 etc');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $this->entityManager->beginTransaction();

        $cryptoSymbol = strtoupper($input->getOption(self::CRYPTO_OPT));
        $cryptoDeploySymbol = strtoupper($input->getOption(self::CRYPTO_BLOCKCHAIN_OPT));

        $crypto = $this->cryptoManager->findBySymbol($cryptoSymbol);
        $cryptoDeploy = $this->cryptoManager->findBySymbol($cryptoDeploySymbol);

        if (!$crypto) {
            $io->error("Crypto ${cryptoSymbol} symbol doesn't exist.");

            return 1;
        }

        if (!$cryptoDeploy) {
            $io->error("Crypto ${cryptoDeploySymbol} blockchain symbol doesn't exist.");

            return 1;
        }

        try {
            $wrappedCryptoToken = $this->wrappedCryptoTokenManager->findByCryptoAndDeploy($crypto, $cryptoDeploy);

            if ($wrappedCryptoToken) {
                $io->error('Crypto for provided network already exists. Aborting...');

                return 1;
            }

            if ($crypto->getId() === $cryptoDeploy->getId()) {
                $io->error('Crypto and blockchain are the same. Aborting...');

                return 1;
            }

            /** @var string $address */
            $address = $input->getOption(self::ADDRESS_OPT);
            /** @var string $fee */
            $fee = $input->getOption(self::FEE_OPT);

            $this->addNewCryptoNetwork($io, $crypto, $cryptoDeploy, $address, $fee);
            $this->entityManager->commit();

            $io->success('Crypto network successfully added!');

            return 0;
        } catch (\Throwable $exception) {
            $this->entityManager->rollback();

            $io->warning('Failed to add crypto network');
            $io->error($exception->getMessage());

            return 1;
        }
    }

    private function addNewCryptoNetwork(
        SymfonyStyle $io,
        Crypto $crypto,
        Crypto $cryptoDeploy,
        string $address,
        string $fee
    ): void {
        $feeMoney = $this->moneyWrapper->parse($fee, $crypto->getMoneySymbol());

        $io->writeln('Adding crypto network to gateway...');
        $addTokenResult = $this->contractHandler->addToken($crypto, $cryptoDeploy, $address, null, true);

        if ($addTokenResult->alreadyExisted()) {
            $io->warning('Gateway already has ' . $crypto->getSymbol().'/'.$cryptoDeploy->getSymbol() .' added.');
        }

        $io->writeln('Creating new network in db...');
        $this->wrappedCryptoTokenManager->create(
            $crypto,
            $cryptoDeploy,
            $address,
            $feeMoney
        );
    }
}
