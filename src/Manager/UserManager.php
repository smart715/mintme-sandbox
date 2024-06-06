<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\Crypto;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Entity\UserChangeEmailRequest;
use App\Entity\ValidationCode\ChangeEmailValidationCode;
use App\Mailer\MailerInterface;
use App\Repository\UserChangeEmailRequestRepository;
use App\Repository\UserCryptoRepository;
use App\Repository\UserRepository;
use App\Repository\UserTokenRepository;
use App\Utils\Symbols;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;
use FOS\UserBundle\Util\CanonicalFieldsUpdater;
use FOS\UserBundle\Util\PasswordUpdaterInterface;

class UserManager extends \FOS\UserBundle\Doctrine\UserManager implements UserManagerInterface
{
    private CryptoManagerInterface $cryptoManager;
    private MailerInterface $mailer;
    private EntityManagerInterface $entityManager;
    private UserChangeEmailRequestRepository $userChangeEmailRequestRepository;
    private UserTokenRepository $userTokenRepository;
    private UserCryptoRepository $userCryptoRepository;
    private string $secret;

    public function __construct(
        PasswordUpdaterInterface $passwordUpdater,
        CanonicalFieldsUpdater $canonicalFieldsUpdater,
        ObjectManager $om,
        string $class,
        string $secret,
        CryptoManagerInterface $cryptoManager,
        MailerInterface $mailer,
        EntityManagerInterface $entityManager,
        UserChangeEmailRequestRepository $userChangeEmailRequestRepository,
        UserTokenRepository $userTokenRepository,
        UserCryptoRepository $userCryptoRepository
    ) {
        parent::__construct($passwordUpdater, $canonicalFieldsUpdater, $om, $class);
        $this->cryptoManager = $cryptoManager;
        $this->mailer = $mailer;
        $this->entityManager = $entityManager;
        $this->userChangeEmailRequestRepository = $userChangeEmailRequestRepository;
        $this->userTokenRepository = $userTokenRepository;
        $this->userCryptoRepository = $userCryptoRepository;
        $this->secret = $secret;
    }

    public function find(int $id): ?User
    {
        return $this->getRepository()->find($id);
    }

    /** @psalm-suppress ImplementedReturnTypeMismatch  */
    public function getRepository(): UserRepository
    {
        /** @var UserRepository $repository */
        $repository = parent::getRepository();

        return $repository;
    }

    public function findByReferralCode(string $code): ?User
    {
        return $this->getRepository()->findOneBy([
            'referralCode' => $code,
        ]);
    }

    public function findByDomain(string $domain): array
    {
        return $this->getRepository()->findByDomain($domain);
    }

    /**
     * {@inheritdoc}
     */
    public function findUserByEmail($email)
    {
        return $this->findUserBy(['email' => $email]);
    }

    public function findByDiscordId(int $discordId): ?User
    {
        /** @var User|null $user */
        $user = $this->findUserBy(['discordId' => $discordId]);

        return $user;
    }

    public function checkExistCanonicalEmail(string $email): bool
    {
        return $this->getRepository()->checkExistCanonicalEmail($email);
    }

    public function sendMintmeExchangeMail(User $user): void
    {
        $exchangeCryptos = array_values(array_filter(
            $this->cryptoManager->findAll(),
            fn (Crypto $crypto) => Symbols::WEB !== $crypto->getSymbol() && $crypto->isTradable()
        ));

        $cryptosList = array_reduce(
            $exchangeCryptos,
            fn ($carry, Crypto $crypto) =>
             $exchangeCryptos[count($exchangeCryptos)-1] === $crypto && 1 !== count($exchangeCryptos)
                ? $carry . ' ' . 'and' . ' ' . $crypto->getSymbol()
                : ('' === $carry
                    ? $crypto->getSymbol()
                    : $carry . ', ' . $crypto->getSymbol()),
            ''
        );

        $this->mailer->sentMintmeExchangeMail($user, $exchangeCryptos, $cryptosList);

        $user->setExchangeCryptoMailSent(true);
        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }

    /** @inheritDoc */
    public function getUserToken(Token $token, array $userIds): array
    {
        return $this->userTokenRepository->getUserToken($token, $userIds);
    }

    /** @inheritDoc */
    public function getUserCrypto(Crypto $crypto, array $userIds): array
    {
        return $this->userCryptoRepository->getUserCrypto($crypto, $userIds);
    }

    /**
     * @param array $domains
     * @return array|null
     */
    public function getUsersByDomains(array $domains): ?array
    {
        $emailDomains = [];

        foreach ($domains as $domain) {
            $emailDomains = array_merge($emailDomains, $this->getRepository()->findByDomain($domain));
        }

        return $emailDomains;
    }

    public function getUserChangeEmailRequest(User $user): ?UserChangeEmailRequest
    {
        return $this->userChangeEmailRequestRepository->findLastActiveRequest($user);
    }

    public function changeEmail(user $user, string $newEmail): void
    {
        $this->deleteExpiredChangeEmailRequests($user);

        $changeEmailRequest = new UserChangeEmailRequest($user, $newEmail);

        $currentEmailCode = (new ChangeEmailValidationCode(null))->setChangeEmail($changeEmailRequest);
        $newEmailCode = (new ChangeEmailValidationCode(null))->setChangeEmail($changeEmailRequest);

        $changeEmailRequest
            ->setCurrentEmailCode($currentEmailCode)
            ->setNewEmailCode($newEmailCode);

        $this->entityManager->persist($changeEmailRequest);
    }

    public function verifyNewEmail(user $user): ?UserChangeEmailRequest
    {
        $changeEmailRequest = $this->userChangeEmailRequestRepository->findLastActiveRequest($user);

        if (!$changeEmailRequest) {
            return null;
        }

        $user->setEmail($changeEmailRequest->getNewEmail());
        $changeEmailRequest->setConfirmedAt();

        $this->entityManager->persist($user);
        $this->entityManager->persist($changeEmailRequest);

        return $changeEmailRequest;
    }

    public function saveSessionId(User $user, string $sessionId): void
    {
        $encryptedSessionId = $this->encryptSessionId($sessionId);
        $user->setSessionId($encryptedSessionId);

        $this->entityManager->persist($user);
    }

    public function isSessionIdValid(User $user, string $sessionId): bool
    {
        $storedSessionId = $user->getSessionId();

        if (!$storedSessionId) {
            return false;
        }

        $encryptedSessionId = $this->encryptSessionId($sessionId);

        return hash_equals($storedSessionId, $encryptedSessionId);
    }

    private function encryptSessionId(string $sessionId): string
    {
        return hash_hmac('sha256', $sessionId, $this->secret);
    }
    
    private function deleteExpiredChangeEmailRequests(User $user): void
    {
        $expiredChangeEmailRequest = $this->userChangeEmailRequestRepository->findExpiredRequestsForUser(
            $user,
            new \DateTimeImmutable('-1 month'),
        );
        
        foreach ($expiredChangeEmailRequest as $request) {
            $this->entityManager->remove($request);
        }
    }
}
