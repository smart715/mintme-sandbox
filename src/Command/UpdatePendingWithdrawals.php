<?php declare(strict_types = 1);

namespace App\Command;

use App\Entity\PendingTokenWithdraw;
use App\Entity\PendingWithdraw;
use App\Entity\Token\Token;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Manager\CryptoManagerInterface;
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

    /** @var CryptoManagerInterface */
    private $cryptoManager;

    public function __construct(
        LoggerInterface $logger,
        EntityManagerInterface $entityManager,
        DateTime $dateTime,
        BalanceHandlerInterface $balanceHandler,
        CryptoManagerInterface $cryptoManager
    ) {
        $this->logger = $logger;
        $this->em = $entityManager;
        $this->date = $dateTime;
        $this->balanceHandler = $balanceHandler;
        $this->cryptoManager = $cryptoManager;

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

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $this->logger->info('[withdrawals] Update job started..');

        $expires = new DateInterval('PT'.PendingWithdraw::EXPIRES_HOURS.'H');

        /** @var PendingWithdraw $item */
        foreach ($this->getPendingWithdrawRepository()->findAll() as $item) {
            if ($item->getDate()->add($expires) < $this->date->now()) {
                $this->em->beginTransaction();

                try {
                    $this->em->remove($item);
                    $this->em->flush();
                    $this->balanceHandler->deposit(
                        $item->getUser(),
                        Token::getFromCrypto($item->getCrypto()),
                        $item->getAmount()->getAmount()->add($item->getCrypto()->getFee())
                    );
                } catch (Throwable $exception) {
                    $this->em->rollback();
                }

                $this->em->commit();
            }
        }

        /** @var PendingTokenWithdraw $item */
        foreach ($this->getPendingTokenWithdrawRepository()->findAll() as $item) {
            $crypto = $this->cryptoManager->findBySymbol(Token::WEB_SYMBOL);

            if (!$crypto) {
                return;
            }

            if ($item->getDate()->add($expires) < $this->date->now()) {
                $this->em->beginTransaction();

                try {
                    $this->em->remove($item);
                    $this->em->flush();
                    $this->balanceHandler->deposit(
                        $item->getUser(),
                        $item->getToken(),
                        $item->getAmount()->getAmount()
                    );
                    $this->balanceHandler->deposit(
                        $item->getUser(),
                        Token::getFromCrypto($crypto),
                        $crypto->getFee()
                    );
                } catch (Throwable $exception) {
                    $this->em->rollback();
                }

                $this->em->commit();
            }
        }

        $this->logger->info('[withdrawals] Update job finished..');
    }

    private function getPendingWithdrawRepository(): PendingWithdrawRepository
    {
        return $this->em->getRepository(PendingWithdraw::class);
    }

    private function getPendingTokenWithdrawRepository(): PendingWithdrawRepository
    {
        return $this->em->getRepository(PendingTokenWithdraw::class);
    }
}
