<?php declare(strict_types = 1);

namespace App\Command;

use App\Entity\PendingTokenWithdraw;
use App\Entity\PendingWithdraw;
use App\Entity\Token\Token;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Exchange\Balance\Exception\RepeatUpdateException;
use App\Manager\CryptoManagerInterface;
use App\Repository\PendingTokenWithdrawRepository;
use App\Repository\PendingWithdrawRepository;
use App\Utils\DateTime;
use App\Utils\LockFactory;
use DateInterval;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

/* Cron job added to DB. */
class UpdatePendingWithdrawals extends Command
{
    /** @var LoggerInterface */
    private LoggerInterface $logger;

    /** @var EntityManagerInterface */
    private EntityManagerInterface $em;

    /** @var DateTime */
    private DateTime $date;

    /** @var BalanceHandlerInterface */
    private BalanceHandlerInterface $balanceHandler;

    /** @var CryptoManagerInterface */
    private CryptoManagerInterface $cryptoManager;

    /** @var int */
    public int $expirationTime;

    /** @var LockFactory */
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
        $lock = $this->lockFactory->createLock('update-pending-withdrawals');

        if (!$lock->acquire()) {
            return 0;
        }

        $this->logger->info("[withdrawals] Update job started with expiration time: {$this->expirationTime}S.. ");

        $expires = new DateInterval('PT' . $this->expirationTime . 'S');

        $items = $this->getPendingWithdrawRepository()->findAll();

        $itemsCount = count($items);
        $pendingCount = 0;

        /** @var PendingWithdraw $item */
        foreach ($items as $item) {
            if ($item->getDate()->add($expires) < $this->date->now()) {
                $crypto = $item->getCrypto();

                $fee   = $crypto->getFee();
                $token = Token::getFromCrypto($crypto);
                $this->em->beginTransaction();

                try {
                    $this->balanceHandler->deposit(
                        $item->getUser(),
                        $token,
                        $item->getAmount()->getAmount(),
                        $item->getId()
                    );

                    $this->balanceHandler->deposit(
                        $item->getUser(),
                        $token,
                        $fee,
                        $item->getId()
                    );

                    $this->em->remove($item);
                    $this->em->flush();
                    $this->logger->info("[withdrawals] $pendingCount Pending withdrawal to {$token->getName()} addr: {$token->getAddress()} (({$item->getAmount()->getAmount()->getAmount()} {$item->getAmount()->getAmount()->getCurrency()->getCode()} + {$fee->getAmount()}{$fee->getCurrency()->getCode()} ), user id={$item->getUser()->getId()}) returns.");
                    $this->em->commit();
                    $pendingCount++;
                } catch (Throwable $e) {
                    if ('repeat update' !== $e->getMessage()) {
                        $message = $e->getMessage();
                        $this->logger->info("[withdrawals] Pending withdrawal error: $message ...");
                    } else {
                        $message = $e->getMessage();
                        $this->logger->info("[withdrawals] Pending withdrawal error: $message ...");
                        $this->em->rollback();
                    }
                }
            }
        }

        $this->logger->info("[withdrawals] Pending withdrawal total: $itemsCount, deleted: $pendingCount ..");

        $items = $this->getPendingTokenWithdrawRepository()->findAll();

        $itemsCount = count($items);
        $pendingCount = 0;

        /** @var PendingTokenWithdraw $item */
        foreach ($items as $item) {
            if ($item->getDate()->add($expires) < $this->date->now()) {
                $token = $item->getToken();

                $this->em->beginTransaction();

                try {
                    $crypto = $this->cryptoManager->findBySymbol(Token::WEB_SYMBOL);

                    $fee = $crypto->getFee();

                    $feeToken = Token::getFromCrypto($crypto);

                    $this->balanceHandler->deposit(
                        $item->getUser(),
                        $token,
                        $item->getAmount()->getAmount(),
                        $item->getId()
                    );

                    $this->balanceHandler->deposit(
                        $item->getUser(),
                        $feeToken,
                        $fee,
                        $item->getId()
                    );

                    $this->em->remove($item);
                    $this->em->flush();
                    $this->em->commit();
                    $pendingCount++;
                    $this->logger->info("[withdrawals] $pendingCount Pending token withdrawal ({$item->getSymbol()}, user id={$item->getUser()->getId()}) returns.");
                } catch (Throwable $e) {
                    if ('repeat update' !== $e->getMessage()) {
                        $message = $e->getMessage();
                        $this->logger->info("[withdrawals] Pending token withdrawal error: $message ...");
                    }
                    else {
                        $message = $e->getMessage();
                        $this->logger->info("[withdrawals] Pending token withdrawal error: $message ...");
                        $this->em->rollback();
                    }
                }
            }
        }

        $this->logger->info("[withdrawals] Pending token withdrawal total: $itemsCount, deleted: $pendingCount ..");

        $this->logger->info('[withdrawals] Update job finished..');

        $lock->release();

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
