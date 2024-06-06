<?php declare(strict_types = 1);

namespace App\Command\Token;

use App\Entity\Crypto;
use App\Entity\Token\Token;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Exchange\Market;
use App\Manager\CryptoManagerInterface;
use App\Manager\MarketStatusManagerInterface;
use App\Manager\TokenCryptoManagerInterface;
use App\Manager\TokenManagerInterface;
use App\Wallet\Money\MoneyWrapperInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class DeleteMarketCommand extends Command
{
    private EntityManagerInterface $entityManager;
    private TokenManagerInterface $tokenManager;
    private CryptoManagerInterface $cryptoManager;
    private MarketStatusManagerInterface $marketStatusManager;
    private TokenCryptoManagerInterface $tokenCryptoManager;
    private MoneyWrapperInterface $moneyWrapper;
    private BalanceHandlerInterface $balanceHandler;

    public function __construct(
        EntityManagerInterface $entityManager,
        TokenManagerInterface $tokenManager,
        CryptoManagerInterface $cryptoManager,
        MarketStatusManagerInterface $marketStatusManager,
        TokenCryptoManagerInterface $tokenCryptoManager,
        MoneyWrapperInterface $moneyWrapper,
        BalanceHandlerInterface $balanceHandler
    ) {
        $this->entityManager = $entityManager;
        $this->tokenManager = $tokenManager;
        $this->cryptoManager = $cryptoManager;
        $this->marketStatusManager = $marketStatusManager;
        $this->tokenCryptoManager = $tokenCryptoManager;
        $this->moneyWrapper = $moneyWrapper;
        $this->balanceHandler = $balanceHandler;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('app:delete-market')
            ->setDescription('Delete created market')
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
            $this->reverseMarketCreation($crypto, $token, $payback, $io);

            $this->entityManager->commit();
        } catch (\Throwable $ex) {
            $io->error("Something went wrong, aborting. Error: " . $ex->getMessage());
            $io->error($ex->getTraceAsString());

            $this->entityManager->rollback();

            return 1;
        }

        return 0;
    }

    private function reverseMarketCreation(Crypto $crypto, Token $token, bool $payback, SymfonyStyle $io): void
    {
        $io->writeln("Starting revert market creation");

        $market = new Market(
            $crypto,
            $token
        );

        $marketStr = $crypto->getSymbol() . "/" . $token->getName();

        $tokenCrypto = $this->tokenCryptoManager->getByCryptoAndToken($crypto, $token);

        if (!$tokenCrypto) {
            $io->warning(
                "Token crypto for ${marketStr} doesn't exists. Skipping..."
            );
        } else {
            $costStr = $this->moneyWrapper->format($tokenCrypto->getCost());
            $io->success(
                "Market cost was: " . $costStr . " " . $tokenCrypto->getCryptoCost()->getMoneySymbol()
                . " . Please save it in case you want to remove funds to user"
            );

            $this->entityManager->remove($tokenCrypto);
            $this->entityManager->flush();

            $io->writeln("Token crypto for ${marketStr} was removed");
        }

        $marketStatus = $this->marketStatusManager->getMarketStatus($market);

        if (!$marketStatus) {
            $io->warning(
                "Market status for ${marketStr} doesn't exists. Skipping..."
            );
        } else {
            $this->entityManager->remove($marketStatus);
            $this->entityManager->flush();

            $io->writeln("Market status for ${marketStr} was removed");
        }

        if ($payback && isset($costStr)) {
            $this->balanceHandler->deposit(
                $token->getOwner(),
                $tokenCrypto->getCryptoCost(),
                $tokenCrypto->getCost()
            );

            $io->writeln("Funds for market creation ${costStr} was returned to user");
        }

        $io->success("Market successfully reverted.");
    }
}
