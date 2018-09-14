<?php

namespace App\Manager;

use App\Entity\GoogleAuthenticatorEntry;
use App\Entity\User;
use App\OrmAdapter\OrmAdapterInterface;
use App\Repository\GoogleAuthenticatorEntryRepository;
use PragmaRX\Random\Random;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Google\GoogleAuthenticatorInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class TwoFactorManager implements TwoFactorManagerInterface
{
    /** @var SessionInterface */
    private $session;

    /** @var OrmAdapterInterface */
    private $ormAdapter;

    /** @var GoogleAuthenticatorInterface */
    private $authenticator;

    public function __construct(
        SessionInterface $session,
        OrmAdapterInterface $ormAdapter,
        GoogleAuthenticatorInterface $authenticator
    ) {
        $this->session = $session;
        $this->ormAdapter = $ormAdapter;
        $this->authenticator = $authenticator;
    }

    public function checkCode(User $user, FormInterface $form): bool
    {
        $code = $form->getData()['code'] ?? '';
        $isBackupCode = in_array($code, $user->getGoogleAuthenticatorBackupCodes());
        return $isBackupCode || $this->authenticator->checkCode($user, $code);
    }
    
    public function generateBackupCodes(): array
    {
        $codes = [];
        for ($i = 0; $i < 5; $i++)
            $codes[] = (new Random())->size(12)->get();
        return $codes;
    }

    public function generateSecretCode(): string
    {
        if ($this->session->has('googleSecreteCode'))
            return $this->session->get('googleSecreteCode');
        $secrete = $this->authenticator->generateSecret();
        $this->session->set('googleSecreteCode', $secrete);
        return $secrete;
    }

    public function generateUrl(User $user): string
    {
        return $this->authenticator->getUrl($user);
    }

    public function getGoogleAuthEntry(int $userId): GoogleAuthenticatorEntry
    {
        /** @var GoogleAuthenticatorEntryRepository */
        $repository = $this->ormAdapter->getRepository(GoogleAuthenticatorEntry::class);
        return $repository->getGoogleAuthenticator($userId);
    }
}
