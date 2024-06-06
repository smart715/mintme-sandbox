<?php declare(strict_types = 1);

namespace App\Command\Token;

use App\Entity\Crypto;
use App\Entity\Token\Token;
use App\Entity\Token\TokenDeploy;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Exchange\Market;
use App\Manager\CryptoManagerInterface;
use App\Manager\MarketStatusManagerInterface;
use App\Manager\TokenManagerInterface;
use App\Repository\TokenDeployRepository;
use App\Wallet\Money\MoneyWrapperInterface;
use Doctrine\ORM\EntityManagerInterface;
use Money\Currency;
use Money\Money;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class DeleteDeployCommand extends Command
{
    private EntityManagerInterface $entityManager;
    private TokenManagerInterface $tokenManager;
    private CryptoManagerInterface $cryptoManager;
    private MarketStatusManagerInterface $marketStatusManager;
    private TokenDeployRepository $tokenDeployRepository;
    private MoneyWrapperInterface $moneyWrapper;
    private BalanceHandlerInterface  $balanceHandler;

    public function __construct(
        EntityManagerInterface $entityManager,
        TokenManagerInterface $tokenManager,
        CryptoManagerInterface $cryptoManager,
        MarketStatusManagerInterface $marketStatusManager,
        TokenDeployRepository $tokenDeployRepository,
        MoneyWrapperInterface $moneyWrapper,
        BalanceHandlerInterface $balanceHandler
    ) {
        $this->entityManager = $entityManager;
        $this->tokenManager = $tokenManager;
        $this->cryptoManager = $cryptoManager;
        $this->marketStatusManager = $marketStatusManager;
        $this->tokenDeployRepository = $tokenDeployRepository;
        $this->moneyWrapper = $moneyWrapper;
        $this->balanceHandler = $balanceHandler;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('app:delete-deploy')
            ->setDescription('Delete token deploy')
            ->addOption('token', null, InputOption::VALUE_REQUIRED, 'token name')
            ->addOption('crypto', null, InputOption::VALUE_REQUIRED, 'crypto symbol')
            ->addOption(
                'payback',
                null,
                InputOption::VALUE_NONE,
                'if true funds for creation will be returned for user'
            );
    }

    /** @inheritDoc */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $tokenOpt = (string)$input->getOption('token');
        $cryptoOpt = (string)$input->getOption('crypto');
        $payback = (bool)$input->getOption('payback');

        $token = $this->tokenManager->findByName($tokenOpt);

        if (!$token) {
            $io->error("Token with provided name doesn't exists. Provided name: ${tokenOpt}");

            return 1;
        }

        $crypto = $this->cryptoManager->findBySymbol($cryptoOpt);

        if (!$crypto) {
            $io->error("Crypto with provided symbol doesn't exists. Provided symbol: ${cryptoOpt}");

            return 1;
        }

        $this->entityManager->beginTransaction();

        try {
            $this->removeTokenDeploy($crypto, $token, $payback, $io);

            $this->entityManager->commit();
        } catch (\Throwable $ex) {
            $io->error("Something went wrong, aborting. Error: " . $ex->getMessage());
            $io->error($ex->getTraceAsString());

            $this->entityManager->rollback();

            return 1;
        }

        return 0;
    }

    private function removeTokenDeploy(
        Crypto $crypto,
        Token $token,
        bool $payback,
        SymfonyStyle $io
    ): void {
        $tokenName = $token->getName();
        $cryptoSymbol = $crypto->getSymbol();

        $io->writeln("Starting deleting token deploy for token ${tokenName} and crypto ${cryptoSymbol}");

        /** @var TokenDeploy|null $deploy */
        $deploy = $this->tokenDeployRepository->findOneBy([
            'token' => $token,
            'crypto' => $crypto,
        ]);

        if (!$deploy) {
            $io->warning(
                "Token deploy for ${tokenName} token doesn't exists. Skipping..."
            );
        } else {
            $costMoney = new Money($deploy->getDeployCost(), new Currency($deploy->getCrypto()->getSymbol()));
            $costStr = $this->moneyWrapper->format($costMoney);

            $io->success(
                "Deploy cost was: " . $costStr . " " . $deploy->getCrypto()->getSymbol()
                . " . Please save it in case you want to remove funds to user"
            );

            $this->entityManager->remove($deploy);
            $this->entityManager->flush();

            $io->writeln("Token deploy for ${tokenName} was removed");
        }

        $marketStatus = $this->marketStatusManager->getMarketStatus(new Market($crypto, $token));

        if (!$marketStatus) {
            $io->warning(
                "Market status for ${tokenName} token and ${cryptoSymbol} symbol doesn't exists. Skipping..."
            );
        } else {
            $networks = $marketStatus->getNetworks()
                ? array_filter($marketStatus->getNetworks(), fn(string $network) => $network !== $cryptoSymbol)
                : $marketStatus->getNetworks();

            $marketStatus->setNetworks($networks);

            $this->entityManager->persist($marketStatus);
            $this->entityManager->flush();

            $io->writeln("Token networks for ${tokenName} and crypto ${cryptoSymbol} were updated");
        }

        if ($payback && isset($costMoney)) {
            $this->balanceHandler->deposit(
                $deploy->getUser(),
                $crypto,
                $costMoney
            );

            $io->writeln("Payback for ${tokenName} token and ${cryptoSymbol} symbol was successfully finished");
        }

        $io->success("Token deploy was successfully removed.");
    }
}
