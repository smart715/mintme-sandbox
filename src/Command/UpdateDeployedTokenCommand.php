<?php declare(strict_types = 1);

namespace App\Command;

use App\Entity\Token\Token;
use App\Repository\TokenRepository;
use App\SmartContract\ContractHandlerInterface;
use App\Utils\LockFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateDeployedTokenCommand extends Command
{
    private EntityManagerInterface $em;

    private LockFactory $lockFactory;

    private ContractHandlerInterface $contractHandler;

    public function __construct(
        ContractHandlerInterface $contractHandler,
        EntityManagerInterface $entityManager,
        LockFactory $lockFactory
    ) {
        $this->lockFactory = $lockFactory;
        $this->em = $entityManager;
        $this->contractHandler = $contractHandler;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('app:update-deployed-token')
            ->setDescription('Update tx_hash column for tokens that were deployed')
            ->setHelp('This command updates all token\'s tx_hash that were deployed');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $lock = $this->lockFactory->createLock('update-token-release');

        if (!$lock->acquire()) {
            return 0;
        }

        $count = 0;

        foreach ($deployed as $token) {
            if (!$token->getTxHash() && $token->isMintmeToken() && !$token->isBlocked()) {
                $token->setTxHash($this->contractHandler->getTxHash($token->getName()));
                $this->em->persist($token);
                $count += 1;
            }
        }

        $updateMessage = $count. ' tokens were updated. Saving to DB..';
        $output->writeln($updateMessage);
        $this->em->flush();
        $lock->release();

        return 0;
    }

    private function getTokenRepository(): TokenRepository
    {
        return $this->em->getRepository(Token::class);
    }
}
