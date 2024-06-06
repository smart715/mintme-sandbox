<?php declare(strict_types = 1);

namespace App\Command;

use App\Entity\PhoneNumber;
use App\Entity\Profile;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class SyncPhoneValidation extends Command
{
    private EntityManagerInterface $em;

    public function __construct(
        EntityManagerInterface $em
    ) {
        parent::__construct();
        $this->em = $em;
    }

    protected function configure(): void
    {
        $this
            ->setName('app:user:sync-phone-validation')
            ->setDescription('syncronize the user current rol whit the currect state of the phone validation.')
            ->addArgument('userId', InputArgument::OPTIONAL, 'the user id to syncronize', '*');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>></info> Syncing users...');
        $iterable = $this->getValidationQuery($input)->iterate();
        $progressBar = new ProgressBar($output);

        $resultLog = [
            "syncs" => 0,
        ];

        foreach ($progressBar->iterate($iterable) as $value) {
            $user = $value[0];
            $user->removeRole(User::ROLE_AUTHENTICATED);
            $user->addRole(User::ROLE_SEMI_AUTHENTICATED);
            $this->em->persist($user);
            $this->em->flush();
            $resultLog['syncs']++;
        }

        $this->printResult($input, $output, $resultLog);

        return 0;
    }

    protected function getValidationQuery(InputInterface $input): Query
    {
        $query = $this->em->createQueryBuilder()
            ->select('u')
            ->from(User::class, 'u')
            ->leftJoin(Profile::class, 'pr', 'WITH', 'pr.user = u.id')
            ->leftJoin(PhoneNumber::class, 'pn', 'WITH', 'pn.profile = pr.id')
            ->where('u.roles LIKE :role')
            ->setParameter('role', '%"ROLE_AUTHENTICATED"%')
            ->andWhere('pn.verified = 0');

        $this->setUserQuery($query, $input);

        return $query->getQuery();
    }

    protected function setUserQuery(QueryBuilder $query, InputInterface $input): void
    {
        $userId = $input->getArgument('userId');

        if ('string' !== gettype($userId)) {
            throw new InvalidArgumentException('The user id argument must be a string');
        }

        if ('*' !== $userId) {
            $query->andWhere('u.id = :userId')
                ->setParameter('userId', $userId);
        }
    }

    protected function printResult(InputInterface $input, OutputInterface $output, array $result): void
    {
        $io = new SymfonyStyle($input, $output);
        $io->table(['Type', 'Count'], [
            ['Successfully synced  profiles', $result['syncs']],
        ]);
    }
}
