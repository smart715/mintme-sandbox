<?php declare(strict_types = 1);

namespace App\Controller;

use App\Communications\DiscordOAuthClientInterface;
use App\Config\WithdrawalDelaysConfig;
use App\Controller\Traits\ViewOnlyTrait;
use App\Entity\ApiKey;
use App\Entity\DiscordRoleUser;
use App\Entity\Unsubscriber;
use App\Entity\User;
use App\Events\PasswordChangeEvent;
use App\Events\TwoFactorDisableEvent;
use App\Events\UserChangeEvents;
use App\Exception\ApiBadRequestException;
use App\Exception\Discord\DiscordException;
use App\Exchange\Config\DeployCostConfig;
use App\Form\ChangePasswordType;
use App\Form\DisconnectDiscordType;
use App\Form\TwoFactorType;
use App\Form\UnsubscribeType;
use App\Logger\UserActionLogger;
use App\Manager\CryptoManagerInterface;
use App\Manager\DiscordManagerInterface;
use App\Manager\ProfileManagerInterface;
use App\Manager\TwoFactorManagerInterface;
use App\Security\TwoFactorVoter;
use App\Services\TranslatorService\TranslatorInterface;
use App\Utils\Symbols;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\UserBundle\Event\FilterUserResponseEvent;
use FOS\UserBundle\Model\UserManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class UserController extends AbstractController implements TwoFactorAuthenticatedInterface
{
    protected UserManagerInterface $userManager;
    protected ProfileManagerInterface $profileManager;
    private UserActionLogger $userActionLogger;
    private EventDispatcherInterface $eventDispatcher;
    private NormalizerInterface $normalizer;
    private TranslatorInterface $translator;
    private DiscordManagerInterface $discordManager;
    private DiscordOAuthClientInterface $discordOAuthClient;
    private EntityManagerInterface $entityManager;
    protected SessionInterface $session;

    use ViewOnlyTrait;

    public function __construct(
        UserManagerInterface $userManager,
        ProfileManagerInterface $profileManager,
        UserActionLogger $userActionLogger,
        EventDispatcherInterface $eventDispatcher,
        NormalizerInterface $normalizer,
        TranslatorInterface $translator,
        DiscordManagerInterface $discordManager,
        DiscordOAuthClientInterface $discordOAuthClient,
        EntityManagerInterface $entityManager,
        SessionInterface $session
    ) {
        $this->userManager = $userManager;
        $this->profileManager = $profileManager;
        $this->userActionLogger = $userActionLogger;
        $this->eventDispatcher = $eventDispatcher;
        $this->normalizer = $normalizer;
        $this->translator = $translator;
        $this->discordManager = $discordManager;
        $this->discordOAuthClient = $discordOAuthClient;
        $this->entityManager = $entityManager;
        $this->session = $session;
    }

    /**
     * @Route("/settings", name="settings", options={"expose"=true})
     * @Route("/settings/update", name="fos_user_profile_show")
     */
    public function editUser(Request $request, WithdrawalDelaysConfig $withdrawalDelaysConfig): Response
    {
        /** @var User|null $user */
        $user = $this->getUser();
        $keys = $user
            ? $user->getApiKey()
            : null;
        $clients = $user
            ? $user->getApiClients()
            : null;

        $passwordForm = $this->getPasswordForm();
        $passwordForm->handleRequest($request);

        if ($passwordForm->isSubmitted() && $this->isViewOnly()) {
            $this->addFlash('error', 'View only');

            return $this->redirectToRoute('settings');
        }

        if ($passwordForm->isSubmitted() && $passwordForm->isValid()) {
            if ($passwordForm['plainPassword']->getData() === $passwordForm['current_password']->getData()) {
                $this->addFlash(
                    'error',
                    $this->translator->trans('passwordmeter.duplicate')
                );
            } else {
                $this->userManager->updatePassword($user);
                $this->userManager->updateUser($user);
                $this->addFlash('success', $this->translator->trans('toasted.success.password_updated'));
                $this->userActionLogger->info('Password was updated successfully');

                /** @psalm-suppress TooManyArguments */
                $this->eventDispatcher->dispatch(
                    new FilterUserResponseEvent(
                        $user,
                        $request,
                        new Response(Response::HTTP_OK)
                    ),
                    UserChangeEvents::PASSWORD_UPDATED_MSG
                );

                $this->eventDispatcher->dispatch(
                    new PasswordChangeEvent($withdrawalDelaysConfig, $user),
                    UserChangeEvents::PASSWORD_UPDATED
                );

                return $this->redirectToRoute('settings');
            }
        }

        if (!$this->isViewOnly()) {
            $disconnectDiscordForm = $this->getDisconnectDiscordForm($request);
            $this->entityManager->flush();
        }

        return $this->addDownloadCodesToResponse(
            $this->renderSettings($passwordForm, $keys, $clients, $disconnectDiscordForm ?? null)
        );
    }

    /**
     * @Route("/referral-program", name="referral-program", options={"expose"=true})
     */
    public function referralProgram(DeployCostConfig $deployCostConfig, CryptoManagerInterface $cryptoManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $token =  $user->getProfile()->getFirstToken();

        return $this->render('pages/referral.html.twig', [
            'referralCode' => $user->getReferralCode(),
            'referralPercentage' => $this->getParameter('referral_fee') * 100,
            'deployCostReward' => $deployCostConfig->getDeployCostRewardPercent(
                $token
                    ? $token->getCryptoSymbol()
                    : Symbols::WEB
            ),
            'referralsCount' => count($user->getReferrals()),
            'userToken' => $token ? $token->getName() : null,
            'enabledCryptos' => $this->normalizer->normalize($cryptoManager->findAll(), null, ["groups" => ["API"]]),
        ]);
    }

    /**
     * @Rest\Route("/invite/{code}", name="register-referral-by-code", schemes={"https"})
     */
    public function registerReferralByCode(
        string $code,
        AuthorizationCheckerInterface $authorizationChecker
    ): Response {
        if ($this->isViewOnly()) {
            throw new AccessDeniedException();
        }

        $response = $authorizationChecker->isGranted('IS_AUTHENTICATED_REMEMBERED')
            ? $this->redirectToRoute('homepage', [], 301)
            : $this->redirectToRoute('fos_user_registration_register', ['withReferral' => true], 301);

        $response->headers->setCookie(
            new Cookie('referral-code', $code)
        );
        $response->headers->setCookie(new Cookie('referral-type', TokenController::TOKEN_REFERRAL_TYPE));

        return $response;
    }

    /** @Route("/settings/2fa", name="two_factor_auth", options={"expose"=true})*/
    public function twoFactorAuth(
        Request $request,
        TwoFactorManagerInterface $twoFactorManager
    ): Response {
        if ($this->isViewOnly()) {
            $this->addFlash('error', 'View only');

            return $this->redirectToRoute('settings');
        }

        /** @var User $user */
        $user = $this->getUser();
        $form = $this->createForm(TwoFactorType::class);
        $isTwoFactor = $user->isGoogleAuthenticatorEnabled();

        if (!$this->isGranted(TwoFactorVoter::TWO_FA_ENABLE)) {
            $this->addFlash('error', $this->translator->trans('2fa.phone_number.required'));

            return $this->redirectToRoute('settings');
        }

        if ($isTwoFactor) {
            return $this->redirectToRoute('settings');
        }

        $user->setGoogleAuthenticatorSecret($twoFactorManager->generateSecretCode());
        $imgUrl = $twoFactorManager->generateUrl($user);

        $form->handleRequest($request);

        $parameters = [
            'form' => $form->createView(),
            'imgUrl' => $imgUrl,
            'formHeader' => $this->translator->trans('page.manager_2fa.form_header.enable'),
            'backupCodes' => [],
            'isTwoFactor' => $isTwoFactor,
            'twoFactorKey' => $user->getGoogleAuthenticatorSecret(),
            'havePhoneNumber' => null !== $user->getProfile()->getPhoneNumber(),
        ];

        if (!$form->isSubmitted() || !$form->isValid()) {
            return $this->render('security/2fa_manager.html.twig', $parameters);
        }

        $parameters['backupCodes'] = $this->turnOnAuthenticator($twoFactorManager, $user);
        $parameters['formHeader'] = $this->translator->trans('page.manager_2fa.form_header.backup_codes');

        /** @var mixed $bag */
        $bag = $this->container->get('session')->getBag('attributes');

        $bag->set('download_backup_codes', 'download');

        return $this->addDownloadCodesToResponse($this->render('security/2fa_manager.html.twig', $parameters));
    }

    /** @Route("/settings/2fa/disable", name="two_factor_auth_disable", options={"expose"=true})*/
    public function disableTwoFactorAuth(
        Request $request,
        TwoFactorManagerInterface $twoFactorManager,
        WithdrawalDelaysConfig $withdrawalDelaysConfig
    ): Response {
        if ($this->isViewOnly()) {
            $this->addFlash('error', 'View only');

            return $this->redirectToRoute('settings');
        }

        /** @var User $user */
        $user = $this->getUser();
        $form = $this->createForm(TwoFactorType::class);
        $isTwoFactor = $user->isGoogleAuthenticatorEnabled();

        if (!$isTwoFactor) {
            return $this->redirectToRoute('settings');
        }

        $form->handleRequest($request);

        $parameters = [
            'form' => $form->createView(),
            'imgUrl' => '',
            'formHeader' => $this->translator->trans('page.manager_2fa.form_header.disable'),
            'backupCodes' => [],
            'isTwoFactor' => $isTwoFactor,
            'twoFactorKey' => $user->getGoogleAuthenticatorSecret(),
            'havePhoneNumber' => null !== $user->getProfile()->getPhoneNumber(),
        ];

        if (!$form->isSubmitted() || !$form->isValid()) {
            return $this->render('security/2fa_manager.html.twig', $parameters);
        }

        $this->turnOffAuthenticator($twoFactorManager, $user);

        $this->eventDispatcher->dispatch(
            new TwoFactorDisableEvent($withdrawalDelaysConfig, $user),
            UserChangeEvents::TWO_FACTOR_DISABLED
        );

        return $this->redirectToRoute('settings');
    }

    /** @Route("/settings/2fa/backupcodes/download", name="download_backup_codes")*/
    public function downloadBackupCodes(Request $request): Response
    {
        if ($this->isViewOnly()) {
            throw new AccessDeniedException();
        }

        /** @var string */
        $userAgent = $request->headers->get('User-Agent');

        $lineBreak = preg_match('/Windows/i', $userAgent)
            ? "\r\n"
            : "\n";

        /** @var mixed $bag */
        $bag = $this->container->get('session')->getBag('attributes');

        if (!$bag->remove('download_backup_codes')) {
            return $this->redirectToRoute('settings');
        }

        /** @var User $user*/
        $user = $this->getUser();
        $backupCodes = $user->getGoogleAuthenticatorBackupCodes();

        $content = implode($lineBreak, $backupCodes);

        $response = new Response($content);

        $disposition = HeaderUtils::makeDisposition(
            HeaderUtils::DISPOSITION_ATTACHMENT,
            $this->generateBackupCodesFileName()
        );

        $response->headers->set('Content-Disposition', $disposition);

        return $response;
    }

    private function addDownloadCodesToResponse(Response $response): Response
    {
        /** @var mixed $bag */
        $bag = $this->container->get('session')->getBag('attributes');

        if ($bag->has('download_backup_codes')) {
            $response->headers->set('Refresh', "5;{$this->generateUrl('download_backup_codes', [], UrlGeneratorInterface::ABSOLUTE_URL)}");
        }

        return $response;
    }

    public function getBackupCodes(TwoFactorManagerInterface $twoFactorManager): array
    {
        if ($this->isViewOnly()) {
            throw new AccessDeniedException();
        }

        $backupCodes = $twoFactorManager->generateBackupCodes();

        /** @var User $user*/
        $user = $this->getUser();
        $user->setGoogleAuthenticatorBackupCodes($backupCodes);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $backupCodes;
    }

    /** @Route("/settings/2fa/backupcodes/generate", name="generate_backup_codes")*/
    public function generateBackupCodes(TwoFactorManagerInterface $twoFactorManager): Response
    {
        if ($this->isViewOnly()) {
            throw new AccessDeniedException();
        }

        /** @var User $user*/
        $user = $this->getUser();

        if (!$user->isGoogleAuthenticatorEnabled()) {
            throw new BadRequestHttpException();
        }

        $this->getBackupCodes($twoFactorManager);
        $this->addFlash('success', $this->translator->trans('2fa.notification.download_backup_code'));
        $this->userActionLogger->info('Downloaded Two-Factor backup codes');

        return $this->redirectToRoute('settings');
    }

    private function getPasswordForm(): FormInterface
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->createForm(ChangePasswordType::class, $user, [
            'action' => $this->generateUrl('settings'),
            'method' => 'POST',
            ]);
    }

    private function getDisconnectDiscordForm(Request $request): FormInterface
    {
        /** @var User $user */
        $user = $this->getUser();
        $disconnectDiscordForm = $this->createForm(DisconnectDiscordType::class);
        $disconnectDiscordForm->handleRequest($request);

        if ($disconnectDiscordForm->isSubmitted() && $disconnectDiscordForm->isValid()) {
            /** @var DiscordRoleUser $roleUser */
            foreach ($user->getDiscordRoleUsers() as $roleUser) {
                try {
                    $this->discordManager->removeGuildMemberRole($user, $roleUser->getDiscordRole());
                    $this->entityManager->remove($roleUser);
                } catch (DiscordException $e) {
                    continue;
                }
            }

            $user->setDiscordId(null);
            $this->userManager->updateUser($user);
            $this->addFlash('success', $this->translator->trans('discord.account.disconnected'));
        }

        return $disconnectDiscordForm;
    }

    private function renderSettings(
        FormInterface $passwordForm,
        ?ApiKey $apiKey,
        ?array $clients,
        ?FormInterface $disconnectDiscordForm = null
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        $this->session->set('locale_lang', $this->session->get('_locale'));

        $discordCallbackUrl = $this->generateUrl(
            'discord_callback_user',
            ['_locale' => 'en'],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $discordAuthUrl = $this->discordOAuthClient->generateAuthUrl('identify', $discordCallbackUrl);

        $response = $this->render('pages/settings.html.twig', [
            'keys' => $this->normalizer->normalize($apiKey ?? [], null, [
                "groups" => ["API"],
            ]),
            'clients' => $this->normalizer->normalize($clients ?? [], null, [
                "groups" => ["API"],
            ]),
            'passwordForm' => $passwordForm->createView(),
            'twoFactorAuth' => $user->isGoogleAuthenticatorEnabled(),
            'discordAuthUrl' => $discordAuthUrl,
            'isSignedInWithDiscord' => $user->isSignedInWithDiscord(),
            'disconnectDiscordForm' => $disconnectDiscordForm ? $disconnectDiscordForm->createView() : null,
            'needPhoneFor2fa' => $this->getParameter('auth_make_disable_2fa'),
        ]);

        $response->headers->set('Cache-Control', 'no-store, no-cache');

        return $response;
    }

    private function turnOnAuthenticator(TwoFactorManagerInterface $twoFactorManager, User $user): array
    {
        $twoFactorManager->initGoogleAuthEntry($user);
        $backupCodes = $this->getBackupCodes($twoFactorManager);
        $this->addFlash('success', $this->translator->trans('2fa.notification.enabled.download_backup_code'));
        $this->userActionLogger->info('Enable Two-Factor Authentication');

        return $backupCodes;
    }

    private function turnOffAuthenticator(TwoFactorManagerInterface $twoFactorManager, User $user): void
    {
        $googleAuth = $twoFactorManager->getGoogleAuthEntry($user->getId());
        $this->entityManager->remove($googleAuth);
        $this->entityManager->flush();
        $this->container->get('session')->remove('googleSecreteCode');
        $this->addFlash('success', $this->translator->trans('2fa.notification.disabled'));
        $this->userActionLogger->info('Disable Two-Factor Authentication');
    }

    private function generateBackupCodesFileName(): string
    {
        /** @var User $user */
        $user = $this->getUser();

        $name = $user->getUsername();
        $time = date("H-i-d-m-Y");

        return "backup-codes-{$name}-{$time}.txt";
    }

    /**
     * @Route("user/unsubscribe/{key}/{mail}", name="unsubscribe")
     */
    public function unsubscribe(
        Request $request,
        string $key,
        string $mail,
        LoggerInterface $unsubscribeLogger
    ): Response {
        if ($this->isViewOnly()) {
            throw new AccessDeniedException();
        }

        if (!filter_var($mail, FILTER_VALIDATE_EMAIL)) {
            return $this->render('pages/404.html.twig');
        }

        $repo = $this->entityManager->getRepository(Unsubscriber::class);

        if ($repo->findOneBy(['email' => $mail])) {
            return $this->render('pages/unsubscribe.html.twig', [
                'mail' => $mail,
                'alreadyUnsubscribed' => true,
            ]);
        }

        $form = $this->createForm(UnsubscribeType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $date = new \DateTimeImmutable();
                $unsubscriber = new Unsubscriber($mail, $date);
                $this->entityManager->persist($unsubscriber);
                $this->entityManager->flush();
            } catch (\Throwable $e) {
                $form->addError(new FormError("Error when unsubscribing {$mail}"));

                return $this->render('pages/unsubscribe.html.twig', [
                    'mail' => $mail,
                    'form' => $form->createView(),
                    'alreadyUnsubscribed' => false,
                ]);
            }

            $unsubscribeLogger->info(
                sprintf("%s %s\n", $mail, $date->format('Y-m-d H:i:s'))
            );

            $this->addFlash('success', $this->translator->trans('page.unsubscribe.success', [
                '%mail%' => $mail,
            ]));

            return $this->redirectToRoute('homepage');
        }

        if (hash_hmac('sha1', $mail, $this->getParameter('hmac_sha_one_key')) === $key) {
            return $this->render('pages/unsubscribe.html.twig', [
                'mail' => $mail,
                'form' => $form->createView(),
                'alreadyUnsubscribed' => false,
            ]);
        }

        return $this->render('pages/404.html.twig');
    }
}
