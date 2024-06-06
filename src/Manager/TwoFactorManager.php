<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\GoogleAuthenticatorEntry;
use App\Entity\User;
use App\Entity\ValidationCode\BackupValidationCode;
use App\Repository\GoogleAuthenticatorEntryRepository;
use Doctrine\ORM\EntityManagerInterface;
use PragmaRX\Random\Random;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Google\GoogleAuthenticatorInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class TwoFactorManager implements TwoFactorManagerInterface
{
    /** @var SessionInterface */
    private $session;

    /** @var GoogleAuthenticatorInterface */
    private $authenticator;

    /** @var EntityManagerInterface */
    private $entityManager;

    public function __construct(
        SessionInterface $session,
        EntityManagerInterface $entityManager,
        GoogleAuthenticatorInterface $authenticator
    ) {
        $this->session = $session;
        $this->entityManager = $entityManager;
        $this->authenticator = $authenticator;
    }

    public function checkCode(User $user, string $code): bool
    {
        if ($user->isBackupCode($code)) {
            $user->invalidateBackupCode($code);

            return true;
        }

        return $this->authenticator->checkCode($user, $code);
    }

    public function generateBackupCodes(): array
    {
        $codes = [];

        for ($i = 0; $i < 5; $i++) {
            $codes[] = (new Random())->size(12)->get();
        }

        return $codes;
    }

    public function generateSecretCode(): string
    {
        if ($this->session->has('googleSecreteCode')) {
            return $this->session->get('googleSecreteCode');
        }

        $secrete = $this->authenticator->generateSecret();
        $this->session->set('googleSecreteCode', $secrete);

        return $secrete;
    }

    public function generateUrl(User $user): string
    {
        return $this->authenticator->getQRContent($user);
    }

    public function getGoogleAuthEntry(int $userId): GoogleAuthenticatorEntry
    {
        /** @var GoogleAuthenticatorEntryRepository */
        $repository = $this->entityManager->getRepository(GoogleAuthenticatorEntry::class);

        return $repository->getGoogleAuthenticator($userId);
    }

    public function initGoogleAuthEntry(User $user): void
    {
        $googleAuthEntry = $user->getGoogleAuthenticatorEntry();
        $smsValidationCode = new BackupValidationCode($user->getProfile()->getPhoneNumber());
        $smsValidationCode->setGoogleAuthEntry($googleAuthEntry);
        $googleAuthEntry->setSMSCode($smsValidationCode);
        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }
}
