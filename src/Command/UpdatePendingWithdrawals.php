<?php declare(strict_types = 1);

namespace App\Command;

use App\Entity\PendingTokenWithdraw;
use App\Entity\PendingWithdraw;
use App\Entity\Token\Token;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Manager\CryptoManager;
use App\Manager\CryptoManagerInterface;
use App\Repository\PendingTokenWithdrawRepository;
use App\Repository\PendingWithdrawRepository;
use App\Utils\DateTime;
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
    private $logger;

    /** @var EntityManagerInterface */
    private $em;

    /** @var DateTime */
    private $date;

    /** @var BalanceHandlerInterface */
    private $balanceHandler;

    /** @var int */
    public $expirationTime;

    public function __construct(
        LoggerInterface $logger,
        EntityManagerInterface $entityManager,
        DateTime $dateTime,
        BalanceHandlerInterface $balanceHandler
    ) {
        $this->logger = $logger;
        $this->em = $entityManager;
        $this->date = $dateTime;
        $this->balanceHandler = $balanceHandler;

        parent::__construct();
    }

    /** {@inheritdoc} */
    protected function configure(): void
    {
        $this
            ->setName('app:update-pending-withdrawals')
            ->setDescription('Deletes expired withdrawals and does payment rollback')
            ->setHelp('This command deletes all expired withdrawals and do a payment rollback');
    }

    /** @inheritDoc */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->logger->info('[withdrawals] Update job started..');

        $expires = new DateInterval('PT'.$this->expirationTime.'S');

        $items = $this->getPendingWithdrawRepository()->findAll();

        $itemsCount = count($items);
        $pendingCount = 0;

        /** @var PendingWithdraw $item */
        foreach ($items as $item) {
            if ($item->getDate()->add($expires) < $this->date->now()) {
                $crypto = $item->getCrypto();

                $fee = $crypto->getFee();
                $token = Token::getFromCrypto($crypto);
                $this->em->beginTransaction();

                try {
                    $this->balanceHandler->deposit(
                        $item->getUser(),
                        $token,
                        $item->getAmount()->getAmount()
                    );

                    $this->balanceHandler->deposit(
                        $item->getUser(),
                        $token,
                        $fee
                    );

                    $this->em->remove($item);
                    $this->em->flush();
                    $this->em->commit();
                    $pendingCount++;
                    $this->logger->info("[withdrawals] $pendingCount Pending withdraval ({$item->getSymbol()}, user id={$item->getUser()->getId()}) returns.");
                } catch (Throwable $exception) {
                    $message = $exception->getMessage();
                    $this->logger->info("[withdrawals] Pending withdraval error: $message ...");
                    $this->em->rollback();
                }
            }
        }

        $this->logger->info("[withdrawals] Pending withdraval total: $itemsCount, deleted: $pendingCount ..");

        $items = $this->getPendingTokenWithdrawRepository()->findAll();

        $itemsCount = count($items);
        $pendingCount = 0;

        /** @var PendingTokenWithdraw $item */
        foreach ($items as $item) {
            if ($item->getDate()->add($expires) < $this->date->now()) {
                $token = $item->getToken();

                $this->em->beginTransaction();

                try {
                    $cmi = $this->getCryptoManager();

                    $crypto = $cmi->findBySymbol(Token::WEB_SYMBOL);

                    $fee = $crypto->getFee();

                    $feeToken = Token::getFromCrypto($crypto);

                    $this->balanceHandler->deposit(
                        $item->getUser(),
                        $token,
                        $item->getAmount()->getAmount()
                    );

                    $this->balanceHandler->deposit(
                        $item->getUser(),
                        $feeToken,
                        $fee
                    );

                    $this->em->remove($item);
                    $this->em->flush();
                    $this->em->commit();
                    $pendingCount++;
                    $this->logger->info("[withdrawals] $pendingCount Pending token withdraval ({$item->getSymbol()}, user id={$item->getUser()->getId()}) returns.");
                } catch (Throwable $exception) {
                    $message = $exception->getMessage();
                    $this->logger->info("[withdrawals] Pending token withdraval error: $message ...");
                    $this->em->rollback();
                }
            }
        }

        $this->logger->info("[withdrawals] Pending token withdraval total: $itemsCount, deleted: $pendingCount ..");

        $this->logger->info('[withdrawals] Update job finished..');

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

    private function getCryptoManager(): CryptoManagerInterface
    {
        return new CryptoManager($this->em);
    }
}
