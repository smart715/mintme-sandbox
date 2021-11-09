<?php declare(strict_types = 1);

namespace App\Command;

use App\Entity\Profile;
use App\Repository\ProfileRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\ConsoleSectionOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class UpdateProfiles extends Command
{
    private LoggerInterface $logger;
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em, LoggerInterface $logger)
    {
        $this->em = $em;
        $this->logger = $logger;

        parent::__construct();
    }

    /** {@inheritdoc} */
    protected function configure(): void
    {
        $this
            ->setName('app:update-profiles')
            ->setDescription('update the nicknames which created from "app:create-profiles" command')
            ->setHelp('update the nicknames which created from "app:create-profiles" command');
    }

    /** {@inheritdoc} */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $updatedUsers = 0;

        /** @var Profile[] $profiles */
        $profiles = $this->getProfileRepository()
            ->createQueryBuilder('p')
            ->where('p.nickname LIKE :nickname')
            ->andWhere('p.anonymous = :anonymous')
            ->andWhere('p.firstName != :firstname')
            ->andWhere('p.lastName != :lastname')
            ->setParameter('nickname', 'anonymous%')
            ->setParameter('anonymous', false)
            ->setParameter('firstname', '')
            ->setParameter('lastname', '')
            ->getQuery()
            ->getResult();

        /** @var ConsoleSectionOutput $section */
        /** @var ConsoleOutputInterface $output */
        $section = $output->section();
        $style = new SymfonyStyle($input, $section);
        $progressBar = new ProgressBar($section, count($profiles));
        $progressBar->start();

        foreach ($profiles as $profile) {
            $progressBar->advance();

            if (!preg_match('/anonymous\d+/', $profile->getNickname())) {
                continue;
            }

            $nickname = $this->getNickname($profile);
            $profile->setNickname($nickname);

            try {
                $this->em->persist($profile);
                $this->em->flush();
                $updatedUsers++;
            } catch (\Throwable $exception) {
                $this->logger->error("Can't update nickname({$nickname}): {$exception->getMessage()}");
            }
        }

        $progressBar->finish();
        $section->clear();
        $style->success("$updatedUsers users updated");

        return 0;
    }

    private function getNickname(Profile $profile, int $sequence = 0): string
    {
        $nickname = strtolower(
            substr(
                "{$profile->getFirstname()} {$profile->getLastname()}",
                0,
                30
            )
        );

        $nickname .= $sequence ?: '';

        if ($this->nicknameHasProfile($nickname)) {
            return $this->getNickname($profile, ++$sequence);
        }

        return mb_convert_encoding($nickname, "UTF-8");
    }

    private function nicknameHasProfile(string $nickname): bool
    {
        return (bool)$this->getProfileRepository()->findBy([
                'nickname' => $nickname,
            ]);
    }

    private function getProfileRepository(): ProfileRepository
    {
        return $this->em->getRepository(Profile::class);
    }
}
