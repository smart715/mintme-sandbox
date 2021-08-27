<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\Crypto;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Entity\UserCrypto;
use App\Entity\UserToken;
use App\Mailer\MailerInterface;
use App\Repository\UserRepository;
use App\Utils\Symbols;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;
use FOS\UserBundle\Util\CanonicalFieldsUpdater;
use FOS\UserBundle\Util\PasswordUpdaterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class UserManager extends \FOS\UserBundle\Doctrine\UserManager implements UserManagerInterface
{
    private CryptoManagerInterface $cryptoManager;
    private MailerInterface $mailer;
    private EntityManagerInterface $entityManager;
    private TranslatorInterface $translator;

    public function __construct(
        PasswordUpdaterInterface $passwordUpdater,
        CanonicalFieldsUpdater $canonicalFieldsUpdater,
        ObjectManager $om,
        string $class,
        CryptoManagerInterface $cryptoManager,
        MailerInterface $mailer,
        EntityManagerInterface $entityManager,
        TranslatorInterface $translator
    ) {
        parent::__construct($passwordUpdater, $canonicalFieldsUpdater, $om, $class);
        $this->cryptoManager = $cryptoManager;
        $this->mailer = $mailer;
        $this->entityManager = $entityManager;
        $this->translator = $translator;
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
        $exchangeCryptos = array_filter(
            $this->cryptoManager->findAll(),
            fn (Crypto $crypto) => Symbols::WEB !== $crypto->getSymbol() && $crypto->isTradable()
        );

        $cryptosList = array_reduce(
            $exchangeCryptos,
            fn ($carry, Crypto $crypto) =>
             $exchangeCryptos[count($exchangeCryptos)] === $crypto
                ? $carry . ' ' . $this->translator->trans('and') . ' ' . $crypto->getSymbol()
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
        $qb = $this->getRepository()->createQueryBuilder('q');

        return $qb->select('ut')
                ->from(UserToken::class, 'ut')
                ->innerJoin('ut.user', 'u')
                ->innerJoin('u.profile', 'p')
                ->add('where', $qb->expr()->in('ut.user', $userIds))
                ->andWhere('ut.token = ?1')
                ->andWhere('ut.user != ?2')
                ->setParameter(1, $token->getId())
                ->setParameter(2, $token->getProfile()->getUser()->getId())
                ->getQuery()
                ->execute();
    }

    /** @inheritDoc */
    public function getUserCrypto(Crypto $crypto, array $userIds): array
    {
        $qb = $this->getRepository()->createQueryBuilder('q');

        return $qb->select('uc')
            ->from(UserCrypto::class, 'uc')
            ->innerJoin('uc.user', 'u')
            ->innerJoin('u.profile', 'p')
            ->add('where', $qb->expr()->in('uc.user', $userIds))
            ->andWhere('uc.crypto = ?1')
            ->andWhere('p.anonymous = 0')
            ->setParameter(1, $crypto->getId())
            ->getQuery()
            ->execute();
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
}
