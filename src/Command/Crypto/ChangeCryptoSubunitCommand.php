<?php declare(strict_types = 1);

namespace App\Command\Crypto;

use App\Entity\BonusBalanceTransaction;
use App\Entity\CommentTip;
use App\Entity\Crypto;
use App\Entity\DeployTokenReward;
use App\Entity\Donation;
use App\Entity\InternalTransaction\CryptoInternalTransaction;
use App\Entity\InternalTransaction\InternalTransaction;
use App\Entity\PendingTokenWithdraw;
use App\Entity\PendingWithdraw;
use App\Entity\Token\TokenDeploy;
use App\Entity\Token\TokenReleaseAddressHistory;
use App\Entity\TokenCrypto;
use App\Entity\Voting\UserVoting;
use App\Entity\WrappedCryptoToken;
use App\Manager\CryptoManagerInterface;
use App\Repository\BonusBalanceTransactionRepository;
use App\Repository\CommentTipRepository;
use App\Repository\CryptoInternalTransactionRepository;
use App\Repository\DeployTokenRewardRepository;
use App\Repository\DonationRepository;
use App\Repository\PendingTokenWithdrawRepository;
use App\Repository\PendingWithdrawRepository;
use App\Repository\TokenCryptoRepository;
use App\Repository\TokenDeployRepository;
use App\Repository\TokenReleaseAddressHistoryRepository;
use App\Repository\UserVotingRepository;
use App\Repository\WrappedCryptoTokenRepository;
use App\Wallet\Money\MoneyWrapperInterface;
use Doctrine\ORM\EntityManagerInterface;
use Money\Currency;
use Money\Money;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\ConsoleSectionOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Skipped tables:
 * bonus - broken table, #9668. There is no need to update something since it's already broken and should be fixed.
 * lock_in - works with TOK symbol, with tokens.
 * post - amount and share_reward are in TOK symbol. Not related to cryptos.
 * reward, reward_participant/volunteer - frozen amount and price are in TOK, in tokens.
 * token - minted_amount, airdrops_amount etc. are in TOK.
 * token_signup_bonus_code and token_signup_history - amount and locked_amount are in TOK.
 * top_holders - amount in TOK.
 */
class ChangeCryptoSubunitCommand extends Command
{
    private const BATCH_SIZE = 100;

    private CryptoManagerInterface $cryptoManager;
    private EntityManagerInterface $entityManager;
    private MoneyWrapperInterface $moneyWrapper;
    private CryptoInternalTransactionRepository $cryptoInternalTransactionRepository;
    private DonationRepository $donationRepository;
    private BonusBalanceTransactionRepository $bonusBalanceTransactionRepository;
    private CommentTipRepository $commentTipRepository;
    private DeployTokenRewardRepository $deployTokenRewardRepository;
    private PendingTokenWithdrawRepository $pendingTokenWithdrawRepository;
    private PendingWithdrawRepository $pendingWithdrawRepository;
    private TokenCryptoRepository $tokenCryptoRepository;
    private TokenDeployRepository $tokenDeployRepository;
    private TokenReleaseAddressHistoryRepository $tokenReleaseAddressHistoryRepository;
    private UserVotingRepository $userVotingRepository;
    private WrappedCryptoTokenRepository $wrappedCryptoTokenRepository;

    public function __construct(
        CryptoManagerInterface $cryptoManager,
        EntityManagerInterface $entityManager,
        MoneyWrapperInterface $moneyWrapper,
        CryptoInternalTransactionRepository $cryptoInternalTransactionRepository,
        DonationRepository $donationRepository,
        BonusBalanceTransactionRepository $bonusBalanceTransactionRepository,
        CommentTipRepository $commentTipRepository,
        DeployTokenRewardRepository $deployTokenRewardRepository,
        PendingTokenWithdrawRepository $pendingTokenWithdrawRepository,
        PendingWithdrawRepository $pendingWithdrawRepository,
        TokenCryptoRepository $tokenCryptoRepository,
        TokenDeployRepository $tokenDeployRepository,
        TokenReleaseAddressHistoryRepository $tokenReleaseAddressHistoryRepository,
        UserVotingRepository $userVotingRepository,
        WrappedCryptoTokenRepository $wrappedCryptoTokenRepository
    ) {
        parent::__construct();
        $this->cryptoManager = $cryptoManager;
        $this->entityManager = $entityManager;
        $this->moneyWrapper = $moneyWrapper;
        $this->cryptoInternalTransactionRepository = $cryptoInternalTransactionRepository;
        $this->donationRepository = $donationRepository;
        $this->bonusBalanceTransactionRepository = $bonusBalanceTransactionRepository;
        $this->commentTipRepository = $commentTipRepository;
        $this->deployTokenRewardRepository = $deployTokenRewardRepository;
        $this->pendingTokenWithdrawRepository = $pendingTokenWithdrawRepository;
        $this->pendingWithdrawRepository = $pendingWithdrawRepository;
        $this->tokenCryptoRepository = $tokenCryptoRepository;
        $this->tokenDeployRepository = $tokenDeployRepository;
        $this->tokenReleaseAddressHistoryRepository = $tokenReleaseAddressHistoryRepository;
        $this->userVotingRepository = $userVotingRepository;
        $this->wrappedCryptoTokenRepository = $wrappedCryptoTokenRepository;
    }

    protected function configure(): void
    {
        $this->setName('app:crypto:change-subunit')
            ->addOption(
                'crypto',
                null,
                InputOption::VALUE_REQUIRED,
                'Crypto symbol'
            )
            ->addOption(
                'subunit',
                null,
                InputOption::VALUE_REQUIRED,
                'New subunit value'
            );
    }

    /** @inheritDoc */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $cryptoSymbol = (string)$input->getOption('crypto');
        $subunit = (int)$input->getOption('subunit');

        if ($subunit <= 0) {
            $io->error("Wrong subunit. Value should be more than 0. Provided: ${subunit}");

            return 1;
        }

        $crypto = $this->cryptoManager->findBySymbol($cryptoSymbol);

        if (!$crypto) {
            $io->error("Crypto with provided symbol doesn't exists. Provided crypto symbol: ${cryptoSymbol}");

            return 1;
        }

        $oldSubunit = $crypto->getSubunit();
        $subunitDiff = $subunit - $oldSubunit;

        if (0 === $subunitDiff) {
            $io->error("Crypto subunit is already ${subunit}. Aborting...");

            return 1;
        }

        /** @var ConsoleSectionOutput $section */
        /** @var ConsoleOutputInterface $output */
        $section = $output->section();

        $this->entityManager->beginTransaction();

        try {
            $crypto->setSubunit($subunit);

            if ($crypto->getFee()) {
                $crypto->setFee($this->moneyWrapper->convertAmountSubunits($crypto->getFee(), $subunitDiff));
            }

            $this->entityManager->persist($crypto);
            $this->entityManager->flush();

            $io->writeln('Updating internal transactions...');
            $this->updateInternalTransactions($crypto, $subunitDiff, $section);

            $io->writeln('Updating donations...');
            $this->updateDonations($crypto, $subunitDiff, $section);

            $io->writeln('Updating bonus balance transactions...');
            $this->updateBonusBalanceTransactions($crypto, $subunitDiff, $section);

            $io->writeln('Updating comment tips...');
            $this->updateCommentTip($crypto, $subunitDiff, $section);

            $io->writeln('Updating deploy token rewards...');
            $this->updateDeployTokenReward($crypto, $subunitDiff, $section);

            $io->writeln('Updating pending token withdrawals...');
            $this->updatePendingTokenWithdraw($crypto, $subunitDiff, $section);

            $io->writeln('Updating pending withdrawals...');
            $this->updatePendingWithdraw($crypto, $subunitDiff, $section);

            $io->writeln('Updating token cryptos...');
            $this->updateTokenCrypto($crypto, $subunitDiff, $section);

            $io->writeln('Updating token deploys...');
            $this->updateTokenDeploy($crypto, $subunitDiff, $section);

            $io->writeln('Updating token release address histories...');
            $this->updateTokenReleaseAddressHistory($crypto, $subunitDiff, $section);

            $io->writeln('Updating user votings...');
            $this->updateUserVoting($crypto, $subunitDiff, $section);

            $io->writeln('Updating wrapped crypto tokens...');
            $this->updateWrappedCryptoToken($crypto, $subunitDiff, $section);

            $this->entityManager->commit();
        } catch (\Throwable $exception) {
            $io->error("Something went wrong, aborting. Error: " . $exception->getMessage());
            $io->error($exception->getTraceAsString());

            $this->entityManager->rollback();

            return 1;
        }

        $io->success("Subunit for ${cryptoSymbol} was successfully changed from ${oldSubunit} to ${subunit}");

        return 0;
    }

    private function updateWrappedCryptoToken(Crypto $crypto, int $subunitChange, ConsoleSectionOutput $section): void
    {
        $wrappedCryptoTokens = $this
            ->wrappedCryptoTokenRepository
            ->createQueryBuilder('wct')
            ->where('wct.feeCurrency = :cryptoSymbol')
            ->setParameter('cryptoSymbol', $crypto->getSymbol())
            ->getQuery()
            ->iterate();

        $progressBar = $this->startProgressBar($section);

        /** @var WrappedCryptoToken[] $wrappedCryptoToken */
        foreach ($wrappedCryptoTokens as $i => $wrappedCryptoToken) {
            $wrappedCryptoToken = $wrappedCryptoToken[0];

            $convertedFee = $this->moneyWrapper->convertAmountSubunits($wrappedCryptoToken->getFee(), $subunitChange);
            $wrappedCryptoToken->setFee($convertedFee);

            $this->entityManager->persist($wrappedCryptoToken);

            if (0 === ($i % self::BATCH_SIZE)) {
                $this->entityManager->flush();
                $this->entityManager->clear();
            }

            $progressBar->advance();
        }

        $this->entityManager->flush();
        $this->entityManager->clear();

        $progressBar->finish();
    }

    private function updateUserVoting(Crypto $crypto, int $subunitChange, ConsoleSectionOutput $section): void
    {
        $userVotings = $this
            ->userVotingRepository
            ->createQueryBuilder('uv')
            ->where('uv.amountSymbol = :cryptoSymbol')
            ->setParameter('cryptoSymbol', $crypto->getSymbol())
            ->getQuery()
            ->iterate();

        $progressBar = $this->startProgressBar($section);

        /** @var UserVoting[] $userVoting */
        foreach ($userVotings as $i => $userVoting) {
            $userVoting = $userVoting[0];

            $convertedAmount = $this->moneyWrapper->convertAmountSubunits($userVoting->getAmountMoney(), $subunitChange);
            $userVoting->setAmount($convertedAmount->getAmount());

            $this->entityManager->persist($userVoting);

            if (0 === ($i % self::BATCH_SIZE)) {
                $this->entityManager->flush();
                $this->entityManager->clear();
            }

            $progressBar->advance();
        }

        $this->entityManager->flush();
        $this->entityManager->clear();

        $progressBar->finish();
    }

    private function updateTokenReleaseAddressHistory(Crypto $crypto, int $subunitChange, ConsoleSectionOutput $section): void
    {
        $tokReleaseAddrHistories = $this
            ->tokenReleaseAddressHistoryRepository
            ->createQueryBuilder('trah')
            ->where('trah.crypto = :crypto')
            ->setParameter('crypto', $crypto)
            ->getQuery()
            ->iterate();

        $progressBar = $this->startProgressBar($section);

        /** @var TokenReleaseAddressHistory[] $releaseAddrHistory */
        foreach ($tokReleaseAddrHistories as $i => $releaseAddrHistory) {
            $releaseAddrHistory = $releaseAddrHistory[0];

            $convertedCost = $this->moneyWrapper->convertAmountSubunits($releaseAddrHistory->getCost(), $subunitChange);
            $releaseAddrHistory->setCost($convertedCost);

            $this->entityManager->persist($releaseAddrHistory);

            if (0 === ($i % self::BATCH_SIZE)) {
                $this->entityManager->flush();
                $this->entityManager->clear();
            }

            $progressBar->advance();
        }

        $this->entityManager->flush();
        $this->entityManager->clear();

        $progressBar->finish();
    }

    private function updateTokenDeploy(Crypto $crypto, int $subunitChange, ConsoleSectionOutput $section): void
    {
        $tokenDeploys = $this
            ->tokenDeployRepository
            ->createQueryBuilder('td')
            ->where('td.crypto = :crypto')
            ->setParameter('crypto', $crypto)
            ->getQuery()
            ->iterate();

        $progressBar = $this->startProgressBar($section);

        /** @var TokenDeploy[] $tokenDeploy */
        foreach ($tokenDeploys as $i => $tokenDeploy) {
            $tokenDeploy = $tokenDeploy[0];
            $oldDeployCost = $tokenDeploy->getDeployCost();

            if (!$oldDeployCost) {
                continue;
            }

            $convertedDeployCost = $this->moneyWrapper->convertAmountSubunits(
                new Money($tokenDeploy->getDeployCost(), new Currency($tokenDeploy->getCrypto()->getSymbol())),
                $subunitChange
            );
            $tokenDeploy->setDeployCost($convertedDeployCost->getAmount());

            $this->entityManager->persist($tokenDeploy);

            if (0 === ($i % self::BATCH_SIZE)) {
                $this->entityManager->flush();
                $this->entityManager->clear();
            }

            $progressBar->advance();
        }

        $this->entityManager->flush();
        $this->entityManager->clear();

        $progressBar->finish();
    }

    private function updateTokenCrypto(Crypto $crypto, int $subunitChange, ConsoleSectionOutput $section): void
    {
        $tokenCryptos = $this
            ->tokenCryptoRepository
            ->createQueryBuilder('tc')
            ->where('tc.cryptoCost = :crypto')
            ->setParameter('crypto', $crypto)
            ->getQuery()
            ->iterate();

        $progressBar = $this->startProgressBar($section);

        /** @var TokenCrypto[] $tokenCrypto */
        foreach ($tokenCryptos as $i => $tokenCrypto) {
            $tokenCrypto = $tokenCrypto[0];

            $convertedCost = $this->moneyWrapper->convertAmountSubunits($tokenCrypto->getCost(), $subunitChange);

            $tokenCrypto->setCost($convertedCost);

            $this->entityManager->persist($tokenCrypto);

            if (0 === ($i % self::BATCH_SIZE)) {
                $this->entityManager->flush();
                $this->entityManager->clear();
            }

            $progressBar->advance();
        }

        $this->entityManager->flush();
        $this->entityManager->clear();

        $progressBar->finish();
    }

    private function updatePendingWithdraw(Crypto $crypto, int $subunitChange, ConsoleSectionOutput $section): void
    {
        $pendingWithdrawals = $this
            ->pendingWithdrawRepository
            ->createQueryBuilder('pw')
            ->where('pw.crypto = :crypto')
            ->orWhere('pw.feeCurrency = :cryptoSymbol')
            ->setParameter('crypto', $crypto)
            ->setParameter('cryptoSymbol', $crypto->getSymbol())
            ->getQuery()
            ->iterate();

        $progressBar = $this->startProgressBar($section);

        /** @var PendingWithdraw[] $pendingWithdrawal */
        foreach ($pendingWithdrawals as $i => $pendingWithdrawal) {
            $pendingWithdrawal = $pendingWithdrawal[0];

            if ($crypto->getId() === $pendingWithdrawal->getCrypto()->getId()) {
                $convertedAmount = $this->moneyWrapper->convertAmountSubunits($pendingWithdrawal->getAmount()->getAmount(), $subunitChange);
                $pendingWithdrawal->setAmount($convertedAmount);
            }

            if ($crypto->getSymbol() === $pendingWithdrawal->getFeeCurrency()) {
                $convertedFee = $this->moneyWrapper->convertAmountSubunits($pendingWithdrawal->getFee(), $subunitChange);
                $pendingWithdrawal->setFee($convertedFee);
            }

            $this->entityManager->persist($pendingWithdrawal);

            if (0 === ($i % self::BATCH_SIZE)) {
                $this->entityManager->flush();
                $this->entityManager->clear();
            }

            $progressBar->advance();
        }

        $this->entityManager->flush();
        $this->entityManager->clear();

        $progressBar->finish();
    }

    private function updatePendingTokenWithdraw(Crypto $crypto, int $subunitChange, ConsoleSectionOutput $section): void
    {
        $pendingTokenWithdrawals = $this
            ->pendingTokenWithdrawRepository
            ->createQueryBuilder('ptw')
            ->where('ptw.feeCurrency = :cryptoSymbol')
            ->setParameter('cryptoSymbol', $crypto->getSymbol())
            ->getQuery()
            ->iterate();

        $progressBar = $this->startProgressBar($section);

        /** @var PendingTokenWithdraw[] $pendingTokenWithdrawal */
        foreach ($pendingTokenWithdrawals as $i => $pendingTokenWithdrawal) {
            $pendingTokenWithdrawal = $pendingTokenWithdrawal[0];

            $convertedAmount = $this->moneyWrapper->convertAmountSubunits(
                $pendingTokenWithdrawal->getFee(),
                $subunitChange
            );
            $pendingTokenWithdrawal->setFee($convertedAmount);

            $this->entityManager->persist($pendingTokenWithdrawal);

            if (0 === ($i % self::BATCH_SIZE)) {
                $this->entityManager->flush();
                $this->entityManager->clear();
            }

            $progressBar->advance();
        }

        $this->entityManager->flush();
        $this->entityManager->clear();

        $progressBar->finish();
    }

    private function updateDeployTokenReward(Crypto $crypto, int $subunitChange, ConsoleSectionOutput $section): void
    {
        $deployTokenRewards = $this
            ->deployTokenRewardRepository
            ->createQueryBuilder('dtr')
            ->where('dtr.currency = :cryptoSymbol')
            ->setParameter('cryptoSymbol', $crypto->getSymbol())
            ->getQuery()
            ->iterate();

        $progressBar = $this->startProgressBar($section);

        /** @var DeployTokenReward[] $deployTokenReward */
        foreach ($deployTokenRewards as $i => $deployTokenReward) {
            $deployTokenReward = $deployTokenReward[0];

            $convertedAmount = $this->moneyWrapper->convertAmountSubunits(
                $deployTokenReward->getReward(),
                $subunitChange
            );
            $deployTokenReward->setReward($convertedAmount);

            $this->entityManager->persist($deployTokenReward);

            if (0 === ($i % self::BATCH_SIZE)) {
                $this->entityManager->flush();
                $this->entityManager->clear();
            }

            $progressBar->advance();
        }

        $this->entityManager->flush();
        $this->entityManager->clear();

        $progressBar->finish();
    }

    private function updateCommentTip(Crypto $crypto, int $subunitChange, ConsoleSectionOutput $section): void
    {
        $commentTips = $this
            ->commentTipRepository
            ->createQueryBuilder('ct')
            ->where('ct.currency = :cryptoSymbol')
            ->setParameter('cryptoSymbol', $crypto->getSymbol())
            ->getQuery()
            ->iterate();

        $progressBar = $this->startProgressBar($section);

        /** @var CommentTip[] $commentTip */
        foreach ($commentTips as $i => $commentTip) {
            $commentTip = $commentTip[0];

            $convertedAmount = $this->moneyWrapper->convertAmountSubunits($commentTip->getAmount(), $subunitChange);
            $commentTip->setAmount($convertedAmount);

            $this->entityManager->persist($commentTip);

            if (0 === ($i % self::BATCH_SIZE)) {
                $this->entityManager->flush();
                $this->entityManager->clear();
            }

            $progressBar->advance();
        }

        $this->entityManager->flush();
        $this->entityManager->clear();

        $progressBar->finish();
    }

    private function updateBonusBalanceTransactions(Crypto $crypto, int $subunitChange, ConsoleSectionOutput $section): void
    {
        $transactions = $this
            ->bonusBalanceTransactionRepository
            ->createQueryBuilder('bbt')
            ->where('bbt.crypto = :crypto')
            ->setParameter('crypto', $crypto)
            ->getQuery()
            ->iterate();

        $progressBar = $this->startProgressBar($section);

        /** @var BonusBalanceTransaction[] $transaction */
        foreach ($transactions as $i => $transaction) {
            $transaction = $transaction[0];

            $convertedAmount = $this->moneyWrapper->convertAmountSubunits($transaction->getAmount(), $subunitChange);
            $transaction->setAmount($convertedAmount);

            $this->entityManager->persist($transaction);

            if (0 === ($i % self::BATCH_SIZE)) {
                $this->entityManager->flush();
                $this->entityManager->clear();
            }

            $progressBar->advance();
        }

        $this->entityManager->flush();
        $this->entityManager->clear();

        $progressBar->finish();
    }

    private function updateInternalTransactions(
        Crypto $crypto,
        int $subunitChange,
        ConsoleSectionOutput $section
    ): void {
        $entities = $this->cryptoInternalTransactionRepository->createQueryBuilder('it')
            ->where('it.crypto = :crypto')
            ->orWhere('it.feeCurrency = :symbol')
            ->setParameter('crypto', $crypto)
            ->setParameter('symbol', $crypto->getSymbol())
            ->getQuery()
            ->iterate();

        $progressBar = $this->startProgressBar($section);

        /** @var InternalTransaction[] $transaction */
        foreach ($entities as $i => $transaction) {
            $transaction = $transaction[0];

            if ($transaction instanceof CryptoInternalTransaction
                && $crypto->getSymbol() === $transaction->getCrypto()->getSymbol()
            ) {
                $transaction->setAmount(
                    $this->moneyWrapper->convertAmountSubunits($transaction->getAmount()->getAmount(), $subunitChange)
                );

                $this->entityManager->persist($transaction);
            }

            if ($crypto->getSymbol() === $transaction->getFee()->getCurrency()->getCode()) {
                $transaction->setFee(
                    $this->moneyWrapper->convertAmountSubunits($transaction->getFee(), $subunitChange)
                );

                $this->entityManager->persist($transaction);
            }

            if (0 === ($i % self::BATCH_SIZE)) {
                $this->entityManager->flush();
                $this->entityManager->clear();
            }

            $progressBar->advance();
        }

        $this->entityManager->flush();
        $this->entityManager->clear();

        $progressBar->finish();
    }

    private function updateDonations(Crypto $crypto, int $subunitChange, ConsoleSectionOutput $section): void
    {
        $symbol = $crypto->getSymbol();

        $entities = $this->donationRepository->createQueryBuilder('d')
            ->where('d.currency = :symbol')
            ->orWhere('d.receiverCurrency = :symbol')
            ->setParameter('symbol', $symbol)
            ->setParameter('symbol', $symbol)
            ->getQuery()
            ->iterate();

        $progressBar = $this->startProgressBar($section);

        /** @var Donation[] $donation */
        foreach ($entities as $i => $donation) {
            $donation = $donation[0];

            if ($symbol === $donation->getAmount()->getCurrency()->getCode()) {
                $donation->setAmount(
                    $this->moneyWrapper->convertAmountSubunits($donation->getAmount(), $subunitChange)
                );
                $donation->setFeeAmount(
                    $this->moneyWrapper->convertAmountSubunits($donation->getFeeAmount(), $subunitChange)
                );

                $this->entityManager->persist($donation);
            }

            $receiverAmount = $donation->getReceiverAmount();

            if ($receiverAmount && $symbol === $receiverAmount->getCurrency()->getCode()) {
                $donation->setReceiverAmount(
                    $this->moneyWrapper->convertAmountSubunits($receiverAmount, $subunitChange)
                );

                if ($donation->getReceiverFeeAmount()) {
                    $donation->setReceiverFeeAmount(
                        $this->moneyWrapper->convertAmountSubunits($donation->getReceiverFeeAmount(), $subunitChange)
                    );
                }

                $this->entityManager->persist($donation);
            }

            if (0 === ($i % self::BATCH_SIZE)) {
                $this->entityManager->flush();
                $this->entityManager->clear();
            }

            $progressBar->advance();
        }

        $this->entityManager->flush();
        $this->entityManager->clear();

        $progressBar->finish();
    }

    protected function startProgressBar(ConsoleSectionOutput $section): ProgressBar
    {
        $progressBar = new ProgressBar($section);
        $progressBar->start();

        return $progressBar;
    }
}
