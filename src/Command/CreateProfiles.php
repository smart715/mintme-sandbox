<?php declare(strict_types = 1);

namespace App\Command;

use App\Entity\Profile;
use App\Entity\User;
use App\Manager\UserManagerInterface;
use App\Repository\ProfileRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CreateProfiles extends Command
{
    /** @var UserManagerInterface */
    private $userManager;

    /** @var EntityManagerInterface */
    private $em;

    public function __construct(UserManagerInterface $userManager, EntityManagerInterface $em)
    {
        $this->userManager = $userManager;
        $this->em = $em;

        parent::__construct();
    }

    /** {@inheritdoc} */
    protected function configure(): void
    {
        $this
            ->setName('app:create-profiles')
            ->setDescription('create a profile or add nickname for existing profile for each user')
            ->setHelp('create a profile or add nickname for existing profile for each user');
    }

    /** {@inheritdoc} */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $updatedUsers = 0;
        $nickNameIndex = 0;
        $nickname = 'anonymous';
        /** @var Profile[] $ignoredProfiles */
        $ignoredProfiles = $this->getProfileRepository()
            ->createQueryBuilder('p')
            ->where('p.nickname LIKE :nickname')
            ->setParameter('nickname', 'anonymous%')
            ->getQuery()
            ->getResult();

        $ignoredNicknames = array_map(function (Profile $profile) {
            return $profile->getNickname();
        }, $ignoredProfiles);

        /** @var User[] $users */
        $users = $this->userManager->findUsers();

        foreach ($users as $user) {
            $profile = null;

            if (!$user->getProfile()->getNickname()) {
                $profile = $user->getProfile();
            }

            if ($profile) {
                do {
                    $userNickname = $nickname . ++$nickNameIndex;
                } while (in_array($userNickname, $ignoredNicknames));

                $profile->setNickname($userNickname);
                $user->setProfile($profile);
                $this->em->persist($user);
                $updatedUsers++;
            }
        }

        $this->em->flush();

        $output->writeln("$updatedUsers users updated");

        return 0;
    }

    private function getProfileRepository(): ProfileRepository
    {
        return $this->em->getRepository(Profile::class);
    }
}
