<?php declare(strict_types = 1);

namespace App\Command;

use App\Entity\PendingTokenWithdraw;
use App\Entity\PendingWithdraw;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Manager\CryptoManagerInterface;
use App\Repository\PendingTokenWithdrawRepository;
use App\Repository\PendingWithdrawRepository;
use App\Utils\DateTime;
use App\Utils\LockFactory;
use App\Utils\Symbols;
use DateInterval;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/* Cron job added to DB. */
class UpdatePendingWithdrawals extends Command
{
    private LoggerInterface $logger;
    private EntityManagerInterface $em;
    private DateTime $date;
    private BalanceHandlerInterface $balanceHandler;
    private CryptoManagerInterface $cryptoManager;
    public int $withdrawExpirationTime;
    public int $viabtcResponseTimeout;
    private LockFactory $lockFactory;

    public function __construct(
        LoggerInterface $logger,
        EntityManagerInterface $entityManager,
        DateTime $dateTime,
        BalanceHandlerInterface $balanceHandler,
        CryptoManagerInterface $cryptoManager,
        LockFactory $lockFactory
    ) {
        $this->logger = $logger;
        $this->em = $entityManager;
        $this->date = $dateTime;
        $this->balanceHandler = $balanceHandler;
        $this->cryptoManager = $cryptoManager;
        $this->lockFactory = $lockFactory;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('app:update-pending-withdrawals')
            ->setDescription('Deletes expired withdrawals and does payment rollback')
            ->setHelp('This command deletes all expired withdrawals and do a payment rollback');
    }

    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $lock = $this->lockFactory->createLock('update-pending-withdrawals', $this->viabtcResponseTimeout + 10);

        if (!$lock->acquire()) {
            $this->logger->info('Cannot acquire lock, another app:update-pending-withdrawals is in progress.');

            return 0;
        }

        try {
            $this->logger->info("[withdrawals] Update job started with expiration time: {$this->withdrawExpirationTime}S.. ");
            $this->balanceHandler->beginTransaction();
            $expires = new DateInterval('PT' . $this->withdrawExpirationTime . 'S');
            $items = $this->getPendingWithdrawRepository()->findAll();
            $itemsCount = count($items);
            $pendingCount = 0;

            /** @var PendingWithdraw $item */
            foreach ($items as $item) {
                if ($item->getDate()->add($expires) < $this->date->now()) {
                    $pendingCount++;
                    $errorMessage = '';
                    $crypto = $item->getCrypto();
                    $hasFee = (bool)$item->getFeeCurrency();

                    $this->em->beginTransaction();

                    try {
                        $this->balanceHandler->beginTransaction();
                        $this->balanceHandler->deposit(
                            $item->getUser(),
                            $crypto,
                            $item->getAmount()->getAmount(),
                            $item->getId()
                        );
                    } catch (\Throwable $e) {
                        $this->balanceHandler->rollback();
                        $errorMessage = $e->getMessage();
                        $this->logger->info("[withdrawals] Pending withdrawal error: $errorMessage");
                    }

                    $feeMsg = 'There is no fee because fee currency is null.';

                    if ($hasFee) {
                        $fee = $item->getFee();
                        $feeMsg = $fee->getAmount() . $fee->getCurrency()->getCode();

                        try {
                            $this->balanceHandler->beginTransaction();
                            $this->balanceHandler->deposit(
                                $item->getUser(),
                                $crypto,
                                $fee,
                                $item->getId() + 1000000
                            );
                        } catch (\Throwable $e) {
                            $this->balanceHandler->rollback();
                            $errorMessage = $e->getMessage();
                            $this->logger->info("[withdrawals] Pending withdrawal error: $errorMessage");
                        }
                    }

                    if ('' !== $errorMessage && 'repeat update' !== $errorMessage) {
                        break;
                    }

                    $this->em->remove($item);
                    $this->em->flush();
                    $this->logger->info(
                        "[withdrawals] $pendingCount Pending withdrawal to {$crypto->getName()} "
                        ."(({$item->getAmount()->getAmount()->getAmount()} "
                        ."{$item->getAmount()->getAmount()->getCurrency()->getCode()} + "
                        ."$feeMsg), "
                        ."user id={$item->getUser()->getId()}) returns."
                    );
                    $this->em->commit();

                    $lock->refresh();
                }
            }

            $this->logger->info("[withdrawals] Pending withdrawal total: $itemsCount, deleted: $pendingCount ..");
            $items = $this->getPendingTokenWithdrawRepository()->findAll();
            $itemsCount = count($items);
            $pendingCount = 0;

            /** @var PendingTokenWithdraw $item */
            foreach ($items as $item) {
                if ($item->getDate()->add($expires) < $this->date->now()) {
                    $pendingCount++;
                    $errorMessage = '';
                    $token = $item->getToken();
                    $this->em->beginTransaction();
                    $fee = $item->getFee();
                    $isFeeInToken = Symbols::TOK === $fee->getCurrency()->getCode();

                    $amount = $isFeeInToken
                        ? $item->getAmount()->getAmount()->add($token->getFee())
                        : $item->getAmount()->getAmount()->add();

                    try {
                        $this->balanceHandler->beginTransaction();
                        $this->balanceHandler->deposit(
                            $item->getUser(),
                            $token,
                            $amount,
                            $item->getId()
                        );
                    } catch (\Throwable $e) {
                        $this->balanceHandler->rollback();
                        $errorMessage = $e->getMessage();
                        $this->logger->info("[withdrawals] Pending token withdrawal error: $errorMessage");
                    }

                    $feeCrypto = $this->cryptoManager->findBySymbol($fee->getCurrency()->getCode());

                    if (!$isFeeInToken && $feeCrypto) {
                        try {
                            $this->balanceHandler->beginTransaction();
                            $this->balanceHandler->deposit(
                                $item->getUser(),
                                $feeCrypto,
                                $fee,
                                $item->getId() + 1000000
                            );
                        } catch (\Throwable $e) {
                            $this->balanceHandler->rollback();
                            $errorMessage = $e->getMessage();
                            $this->logger->info("[withdrawals] Pending token withdrawal error: $errorMessage");
                        }
                    }

                    if ('' !== $errorMessage && 'repeat update' !== $errorMessage) {
                        break;
                    }

                    $this->em->remove($item);
                    $this->em->flush();
                    $this->em->commit();
                    $this->logger->info("[withdrawals] $pendingCount Pending token withdrawal ({$item->getSymbol()}, user id={$item->getUser()->getId()}) returns.");

                    $lock->refresh();
                }
            }

            $this->logger->info("[withdrawals] Pending token withdrawal total: $itemsCount, deleted: $pendingCount ..");
            $this->logger->info('[withdrawals] Update job finished..');
        } finally {
            $lock->release();
        }

        return 0;
    }

    private function getPendingWithdrawRepository(): PendingWithdrawRepository
    {
        return $this->em->getRepository(PendingWithdraw::class);
    }

    private function getPendingTokenWithdrawRepository(): PendingTokenWithdrawRepository
    {
        return $this->em->getRepository(PendingTokenWithdraw::class);
    }
}
