<?php declare(strict_types = 1);

namespace App\Command;

use App\Canonicalizer\EmailCanonicalizer;
use App\Entity\User;
use App\Manager\UserManagerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class UpdateCanonicalEmails extends Command
{
    /** @var EntityManagerInterface */
    private $em;

    /** @var UserManagerInterface */
    private $userManager;

    /** @var EmailCanonicalizer */
    private $emailCanonicializer;

    public function __construct(
        EntityManagerInterface $entityManager,
        UserManagerInterface $userManager,
        EmailCanonicalizer $emailCanonicalizer
    ) {
        $this->em = $entityManager;
        $this->userManager= $userManager;
        $this->emailCanonicializer = $emailCanonicalizer;

        parent::__construct();
    }

    /** {@inheritdoc} */
    protected function configure(): void
    {
        $this
            ->setName('app:update-canonical-emails')
            ->setDescription('Update old emails to canonical view')
            ->setHelp('This command updates all old emails to canonical view');
    }

    /** {@inheritdoc} */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $allUsers = $this->userManager->getRepository()->findAll();
        $io->progressStart(count($allUsers));

        foreach ($allUsers as $user) {
            if (!$this->isCanonicalExist($allUsers, $user)) {
                /** @var User $user */
                $user->setEmailCanonical(
                    $this->emailCanonicializer->canonicalize($user->getEmail())
                );
                $this->em->persist($user);
            }

            $io->progressAdvance();
        }

        $io->progressFinish();
        $io->comment('Updating db...');
        $this->em->flush();
        $io->success('All canonical fields was updated');

        return 0;
    }

    private function isCanonicalExist(array $allUsers, User $currentUser): bool
    {
        foreach ($allUsers as $user) {
            /** @var User $user */
            if (strtolower($user->getEmailCanonical())
                === strtolower($this->emailCanonicializer->canonicalize($currentUser->getEmail()))
            ) {
                return true;
            }
        }

        return false;
    }
}
