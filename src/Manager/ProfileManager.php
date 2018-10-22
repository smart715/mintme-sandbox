<?php

namespace App\Manager;

use App\Entity\Profile;
use App\Entity\User;
use App\Repository\ProfileRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

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
        $this->profileRepository = $entityManager->getRepository(Profile::class);
        $this->userRepository = $entityManager->getRepository(User::class);
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
    public function getProfileByPageUrl(string $pageUrl): ?Profile
    {
        return $this->profileRepository->getProfileByPageUrl($pageUrl);
    }

    public function findByEmail(string $email): ?Profile
    {
        $user = $this->userRepository->findByEmail($email);
        return is_null($user)
            ? null
            : $this->getProfile($user);
    }

    public function generatePageUrl(Profile $profile): string
    {
        $route = $profile->getFirstName() . '.' . $profile->getLastName();

        if ('.' === substr($route, 0, 1)) {
            throw new \Exception('Can\'t generate profile link for empty profile');
        }

        $existedProfile = $this->profileRepository->getProfileByPageUrl($route);

        return null === $existedProfile || $profile === $existedProfile
                ? strtolower($route)
                : $this->generateUniqueUrl($route);
    }

    public function findHash(User $user): User
    {
        if (null === $user->getHash() || '' === $user->getHash()) {
            $user->setHash(base64_encode(uniqid("", true)));
            $this->em->persist($user);
            $this->em->flush();
        }
        return $this->userRepository->findByHash($user->getHash());
    }

    public function validateUserApi(?string $token): ?User
    {
        if (null === $token || '' === $token) {
            return null;
        }

        return $this->userRepository->findByHash($token);
    }

    private function generateUniqueUrl(string $prefix): string
    {
        $str = strtolower($prefix . "." . bin2hex(openssl_random_pseudo_bytes(3) ?: uniqid()));

        if ($this->profileRepository->getProfileByPageUrl($str)) {
            return $this->generateUniqueUrl($prefix);
        }

        return $str;
    }
}
