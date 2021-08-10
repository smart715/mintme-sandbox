<?php declare(strict_types = 1);

namespace App\Controller;

use App\Communications\DiscordOAuthClientInterface;
use App\Entity\ApiKey;
use App\Entity\DiscordRoleUser;
use App\Entity\Token\Token;
use App\Entity\Unsubscriber;
use App\Entity\User;
use App\Events\UserEvents;
use App\Exception\Discord\DiscordException;
use App\Exchange\Config\DeployCostConfig;
use App\Form\ChangePasswordType;
use App\Form\DisconnectDiscordType;
use App\Form\TwoFactorType;
use App\Form\UnsubscribeType;
use App\Logger\UserActionLogger;
use App\Manager\DiscordManagerInterface;
use App\Manager\ProfileManagerInterface;
use App\Manager\TokenManager;
use App\Manager\TwoFactorManagerInterface;
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
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class UserController extends AbstractController implements TwoFactorAuthenticatedInterface
{
    protected UserManagerInterface $userManager;
    protected ProfileManagerInterface $profileManager;
    private UserActionLogger $userActionLogger;
    private EventDispatcherInterface $eventDispatcher;
    private NormalizerInterface $normalizer;
    private TokenManager $tokenManager;
    private TranslatorInterface $translator;
    private DiscordManagerInterface $discordManager;
    private DiscordOAuthClientInterface $discordOAuthClient;
    private EntityManagerInterface $entityManager;

    public function __construct(
        UserManagerInterface $userManager,
        ProfileManagerInterface $profileManager,
        UserActionLogger $userActionLogger,
        EventDispatcherInterface $eventDispatcher,
        NormalizerInterface $normalizer,
        TokenManager $tokenManager,
        TranslatorInterface $translator,
        DiscordManagerInterface $discordManager,
        DiscordOAuthClientInterface $discordOAuthClient,
        EntityManagerInterface $entityManager
    ) {
        $this->userManager = $userManager;
        $this->profileManager = $profileManager;
        $this->userActionLogger = $userActionLogger;
        $this->eventDispatcher = $eventDispatcher;
        $this->normalizer = $normalizer;
        $this->tokenManager = $tokenManager;
        $this->translator = $translator;
        $this->discordManager = $discordManager;
        $this->discordOAuthClient = $discordOAuthClient;
        $this->entityManager = $entityManager;
    }

    /**
     * @Route("/settings", name="settings")
     * @Route("/settings/update", name="fos_user_profile_show")
     */
    public function editUser(Request $request): Response
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

        if ($passwordForm->isSubmitted() && $passwordForm->isValid()) {
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
                UserEvents::PASSWORD_UPDATED
            );

            return $this->redirectToRoute('settings');
        }

        $disconnectDiscordForm = $this->getDisconnectDiscordForm($request);

        $this->entityManager->flush();

        return $this->addDownloadCodesToResponse(
            $this->renderSettings($passwordForm, $keys, $clients, $disconnectDiscordForm)
        );
    }

    /**
     * @Route("/referral-program", name="referral-program")
     */
    public function referralProgram(DeployCostConfig $deployCostConfig): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $token = $user->getProfile()->getFirstToken();

        return $this->render('pages/referral.html.twig', [
            'referralCode' => $user->getReferralCode(),
            'referralPercentage' => $this->getParameter('referral_fee') * 100,
            'deployCostReward' => $token ? $deployCostConfig->getDeployCostRewardPercent($token->getCryptoSymbol()) : 0,
            'referralsCount' => count($user->getReferrals()),
            'userToken' => $token ? $token->getName() : null,
        ]);
    }

    /**
     * @Rest\Route("/token/{userToken}/invite", name="register-referral-by-token", schemes={"https"})
     */
    public function registerReferralByToken(
        string $userToken,
        AuthorizationCheckerInterface $authorizationChecker
    ): Response {

        /** @var Token $token */
        $token = $this->tokenManager->findByName($userToken);

        if (!$token) {
            throw new NotFoundHttpException();
        }

        $referralCode = $token->getProfile()->getUser()->getReferralCode();
        $response = $authorizationChecker->isGranted('IS_AUTHENTICATED_REMEMBERED')
            ? $this->redirectToRoute('token_show', ['name' => $userToken], 301)
            : $this->redirectToRoute('fos_user_registration_register', [], 301);

        $response->headers->setCookie(new Cookie('referral-code', $referralCode));
        $response->headers->setCookie(new Cookie('referral-type', 'invite'));

        return $response;
    }

    /**
     * @Rest\Route("/invite/{code}", name="register-referral-by-code", schemes={"https"})
     */
    public function registerReferralByCode(
        string $code,
        AuthorizationCheckerInterface $authorizationChecker
    ): Response {
        $response = $authorizationChecker->isGranted('IS_AUTHENTICATED_REMEMBERED')
            ? $this->redirectToRoute('homepage', [], 301)
            : $this->redirectToRoute('fos_user_registration_register', [], 301);

        $response->headers->setCookie(
            new Cookie('referral-code', $code)
        );
        $response->headers->setCookie(new Cookie('referral-type', 'invite'));

        return $response;
    }

    /** @Route("/settings/2fa", name="two_factor_auth")*/
    public function twoFactorAuth(
        Request $request,
        TwoFactorManagerInterface $twoFactorManager
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        $form = $this->createForm(TwoFactorType::class);
        $isTwoFactor = $user->isGoogleAuthenticatorEnabled();

        if (!$isTwoFactor) {
            $user->setGoogleAuthenticatorSecret($twoFactorManager->generateSecretCode());
            $imgUrl = $twoFactorManager->generateUrl($user);
            $formHeader = 'Enable two-factor authentication';
        }

        $form->handleRequest($request);

        $parameters = [
            'form' => $form->createView(),
            'imgUrl' => $imgUrl ?? '',
            'formHeader' => $formHeader ?? 'Disable two-factor authentication',
            'backupCodes' => [],
            'isTwoFactor' => $isTwoFactor,
            'twoFactorKey' => $user->getGoogleAuthenticatorSecret(),
        ];

        if ($request->get('backupCodes') && is_array($request->get('backupCodes'))) {
            $parameters['backupCodes'] = $request->get('backupCodes');
            $parameters['formHeader'] = 'Two-Factor authentication backup codes';

            return $this->addDownloadCodesToResponse($this->render('security/2fa_manager.html.twig', $parameters));
        }

        if (!$form->isSubmitted() || !$form->isValid()) {
            return $this->render('security/2fa_manager.html.twig', $parameters);
        }

        if ($isTwoFactor) {
            $this->turnOffAuthenticator($twoFactorManager);

            return $this->redirectToRoute('settings');
        }

        return $this->forward(
            'App\Controller\UserController::twoFactorAuth',
            ['backupCodes' => $this->turnOnAuthenticator($twoFactorManager) ]
        );
    }

    /** @Route("/settings/2fa/backupcodes/download", name="download_backup_codes")*/
    public function downloadBackupCodes(Request $request): Response
    {
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
        $backupCodes = $twoFactorManager->generateBackupCodes();

        /** @var User $user*/
        $user = $this->getUser();
        $user->setGoogleAuthenticatorBackupCodes($backupCodes);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        /** @var mixed $bag */
        $bag = $this->container->get('session')->getBag('attributes');

        $bag->set('download_backup_codes', 'download');

        return $backupCodes;
    }

    /** @Route("/settings/2fa/backupcodes/generate", name="generate_backup_codes")*/
    public function generateBackupCodes(TwoFactorManagerInterface $twoFactorManager): Response
    {
        /** @var User $user*/
        $user = $this->getUser();

        if (!$user->isGoogleAuthenticatorEnabled()) {
            throw new BadRequestHttpException();
        }

        $this->getBackupCodes($twoFactorManager);
        $this->addFlash('success', 'Downloading backup codes...');
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
        FormInterface $disconnectDiscordForm
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        $discordCallbackUrl = $this->generateUrl('discord_callback_user', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $discordAuthUrl = $this->discordOAuthClient->generateAuthUrl('identify', $discordCallbackUrl);

        return $this->render('pages/settings.html.twig', [
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
            'disconnectDiscordForm' => $disconnectDiscordForm->createView(),
        ]);
    }

    private function turnOnAuthenticator(TwoFactorManagerInterface $twoFactorManager): array
    {
        $backupCodes = $this->getBackupCodes($twoFactorManager);
        $this->addFlash('success', $this->translator->trans('2fa.notification.enabled'));
        $this->addFlash('success', $this->translator->trans('2fa.notification.download_backup_code'));
        $this->userActionLogger->info('Enable Two-Factor Authentication');

        return $backupCodes;
    }

    private function turnOffAuthenticator(TwoFactorManagerInterface $twoFactorManager): void
    {
        /** @var User $user*/
        $user = $this->getUser();
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
