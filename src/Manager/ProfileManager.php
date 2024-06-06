<?php declare(strict_types = 1);

namespace App\Manager;

use App\Config\PhoneNumberConfig;
use App\Entity\PhoneNumber as EntityPhoneNumber;
use App\Entity\Profile;
use App\Entity\User;
use App\Entity\ValidationCode\ValidationCode;
use App\Repository\ProfileRepository;
use App\Repository\UserRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use libphonenumber\PhoneNumber;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Guard\Token\PostAuthenticationGuardToken;

class ProfileManager implements ProfileManagerInterface
{
    private ProfileRepository $profileRepository;
    private UserRepository $userRepository;
    private EntityManagerInterface $em;
    private PhoneNumberUtil $phoneNumberUtil;
    private TokenStorageInterface $tokenStorage;
    private PhoneNumberConfig $phoneNumberConfig;

    public function __construct(
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        PhoneNumberUtil $phoneNumberUtil,
        TokenStorageInterface $tokenStorage,
        PhoneNumberConfig $phoneNumberConfig
    ) {
        /** @var ProfileRepository $profileRepository */
        $profileRepository = $entityManager->getRepository(Profile::class);
        $this->profileRepository = $profileRepository;
        $this->userRepository = $userRepository;
        $this->em = $entityManager;
        $this->phoneNumberUtil = $phoneNumberUtil;
        $this->tokenStorage = $tokenStorage;
        $this->phoneNumberConfig = $phoneNumberConfig;
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

    /** {@inheritdoc} */
    public function findAllProfileWithEmptyDescriptionAndNotAnonymous(int $param = 14): array
    {
        return $this->profileRepository->findAllProfileWithEmptyDescriptionAndNotAnonymous($param);
    }

    public function updateProfile(Profile $profile): void
    {
        $this->em->persist($profile);
        $this->em->flush();
    }

    public function verifyPhone(Profile $profile, PhoneNumber $phoneNumber): void
    {
        $profile->getPhoneNumber()
            ->setPhoneNumber($phoneNumber)
            ->setTemPhoneNumber(null)
            ->setVerified(true)
            ->setEditDate(new DateTimeImmutable())
            ->setEditAttempts(0);

        $profile->getUser()->removeRole(User::ROLE_SEMI_AUTHENTICATED);
        $profile->getUser()->addRole(User::ROLE_AUTHENTICATED);

        $newToken = new PostAuthenticationGuardToken(
            $profile->getUser(),
            'authenticate',
            [User::ROLE_AUTHENTICATED, User::ROLE_DEFAULT],
        );
        $this->tokenStorage->setToken($newToken);

        $this->updateProfile($profile);
    }

    public function handlePhoneNumberFailedAttempt(Profile $profile): void
    {
        $profile->getPhoneNumber()->incrementFailedAttempts();
        $this->updateProfile($profile);
    }

    public function unverifyPhoneNumber(Profile $profile): void
    {
        $profile->getPhoneNumber()->setVerified(false);
        $this->updateProfile($profile);
    }

    public function isPhoneEditLimitReached(Profile $profile): bool
    {
        if (!$profile->getPhoneNumber() || !$profile->getPhoneNumber()->getEditDate()) {
            return false;
        }

        $now = new \DateTimeImmutable();
        $possibleEditDate = $profile->getPhoneNumber()->getEditDate()->add(
            new \DateInterval('P'.$this->phoneNumberConfig->getEditPhoneInterval())
        );

        $attempsLimit = $this->phoneNumberConfig->getEditPhoneAttempts();

        return $possibleEditDate > $now || $profile->getPhoneNumber()->getEditAttempts() >= $attempsLimit;
    }

    public function changePhone(Profile $profile, PhoneNumber $newPhoneNumber): void
    {
        $oldPhoneE164 = $profile->getPhoneNumber() ? $this->phoneNumberUtil->format(
            $profile->getPhoneNumber()->getPhoneNumber(),
            PhoneNumberFormat::E164
        ) : null;

        $newPhoneE164 = $this->phoneNumberUtil->format($newPhoneNumber, PhoneNumberFormat::E164);

        $phoneChanged = $newPhoneE164 !== $oldPhoneE164;

        $shouldVerifyPhone = $phoneChanged ||
            ((bool)$oldPhoneE164 && !$profile->getPhoneNumber()->isVerified());

        if ($shouldVerifyPhone) {
            $entityPhoneNumber = new EntityPhoneNumber();

            if (!$oldPhoneE164) {
                $profile->setPhoneNumber(
                    $entityPhoneNumber
                        ->setProfile($profile)
                        ->setPhoneNumber($newPhoneNumber)
                        ->setVerified(false)
                        ->setSMSCode(new ValidationCode($entityPhoneNumber))
                        ->setMailCode(new ValidationCode($entityPhoneNumber))
                );
            }

            $phoneNumber = $profile->getPhoneNumber();

            if ($oldPhoneE164 &&
                !$phoneNumber->getSMSCode() &&
                !$phoneNumber->getMailCode()
            ) {
                $phoneNumber->setEditDate(null);
                $phoneNumber->setSMSCode(new ValidationCode($phoneNumber));
                $phoneNumber->setMailCode(new ValidationCode($phoneNumber));
            }

            $phoneNumber->setTemPhoneNumber($newPhoneNumber);

            $smsCode = $phoneNumber->getSMSCode();
            $mailCode = $phoneNumber->getMailCode();

            !$smsCode ?: $smsCode->setCode(null);
            !$mailCode ?: $mailCode->setCode(null);

            $this->em->persist($profile);
            $this->em->flush();
        }
    }
}
