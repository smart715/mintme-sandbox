<?php declare(strict_types = 1);

namespace App\Command;

use App\Entity\Token\Token;
use App\Mailer\MailerInterface;
use App\Manager\TokenManagerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/* Cron job added to DB. */
class SendNoDeployTokenMail extends Command
{
    private MailerInterface $mail;
    protected TokenManagerInterface $tokenManager;
    private EntityManagerInterface $em;

    public function __construct(
        EntityManagerInterface $em,
        MailerInterface $mail,
        TokenManagerInterface $tokenManager
    ) {
        $this->tokenManager = $tokenManager;
        $this->mail = $mail;
        $this->em = $em;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('app:send-no-deployed-token-mail')
            ->setDescription('Send email notification to owners that have not deployed token')
            ->addArgument('type', InputArgument::OPTIONAL, 'send email to owners that have not deployed token.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var string|null $type */
        $type = $input->getArgument('type');

        $count = $this->sendMail($type);

        $output->writeln($count . ' emails have been sent');

        return 0;
    }

    private function sendMail(?string $type): int
    {
        $sendToNew = 'new' === $type;

        $queryBuilder = $this->tokenManager
            ->getRepository()
            ->createQueryBuilder('token')
            ->where('token.deployed = false');

        if ($sendToNew) {
            $dateTimeImmutable = new \DateTimeImmutable('-3 day');
            $from = $dateTimeImmutable->setTime(0, 0, 0)->format('Y-m-d H:i:s');
            $to = $dateTimeImmutable->setTime(23, 59, 59)->format('Y-m-d H:i:s');

            $queryBuilder
                ->andWhere('token.created BETWEEN :from AND :to')
                ->setParameters([
                    'from' => $from,
                    'to' => $to,
                ]);
        }

        $iterable = $queryBuilder->getQuery()->iterate();

        $count = 0;

        foreach ($iterable as $token) {
            /** @var Token $token */
            $user = $token->getOwner();

            if ($sendToNew) {
                $this->mail->sendNotListedTokenInfoMail($user, $token->getName());
            } else {
                $this->mail->sendTokenRemovedFromTradingInfoMail($user, $token->getName());
            }

            ++$count;

            if (0 === $count % 100) {
                $this->em->clear();
            }
        }

        return $count;
    }
}
