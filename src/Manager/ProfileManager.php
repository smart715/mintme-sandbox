<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\Profile;
use App\Entity\User;
use App\Repository\ProfileRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;

class ProfileManager implements ProfileManagerInterface
{
    /** @var ProfileRepository */
    private $profileRepository;

    /** @var UserRepository */
    private $userRepository;

    /** @var EntityManagerInterface */
    private $em;

    public function __construct(EntityManagerInterface $entityManager)
    {
        /** @var ProfileRepository $profileRepository */
        $profileRepository = $entityManager->getRepository(Profile::class);
        $this->profileRepository = $profileRepository;

        /** @var  UserRepository $userRepository */
        $userRepository = $entityManager->getRepository(User::class);
        $this->userRepository = $userRepository;
        $this->em = $entityManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getProfile($user): ?Profile
    {
        if (!$user instanceof User) {
            return null;
        }

        return $this->profileRepository->getProfileByUser($user);
    }

    /** @codeCoverageIgnore */
    public function getProfileByNickname(string $nickname): ?Profile
    {
        return $this->profileRepository->getProfileByNickname($nickname);
    }

    public function findByEmail(string $email): ?Profile
    {
        $user = $this->userRepository->findByEmail($email);

        return is_null($user)
            ? null
            : $this->getProfile($user);
    }

    public function findByNickname(string $nickname): ?Profile
    {
        return $this->profileRepository->findOneBy([
            'nickname' => $nickname,
        ]);
    }

    /** @codeCoverageIgnore */
    public function createHash(User $user, bool $hash = true, bool $enforceSecurity = true): User
    {
        $user->setHash($hash ?
            ($enforceSecurity ? hash('sha256', Uuid::uuid4()->toString()) : (string)$user->getId())
            : null);
        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    public function findProfileByHash(?string $hash): ?User
    {
        if (null === $hash || '' === $hash) {
            return null;
        }

        return $this->userRepository->findByHash($hash);
    }

    public function findAllProfileWithEmptyDescriptionAndNotAnonymous(int $param = 14): ?array
    {
        return $this->profileRepository->findAllProfileWithEmptyDescriptionAndNotAnonymous($param);
    }
}
