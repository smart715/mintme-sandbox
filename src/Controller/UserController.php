<?php declare(strict_types = 1);

namespace App\Controller;

use App\Entity\ApiKey;
use App\Entity\User;
use App\Exchange\Trade\Config\PrelaunchConfig;
use App\Form\ChangePasswordType;
use App\Form\TwoFactorType;
use App\Logger\UserActionLogger;
use App\Manager\ProfileManagerInterface;
use App\Manager\TwoFactorManagerInterface;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\UserBundle\Event\FilterUserResponseEvent;
use FOS\UserBundle\FOSUserEvents;
use FOS\UserBundle\Model\UserManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class UserController extends AbstractController
{
    /** @var UserManagerInterface */
    protected $userManager;

    /** @var ProfileManagerInterface */
    protected $profileManager;

    /** @var UserActionLogger */
    private $userActionLogger;

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    /** @var NormalizerInterface */
    private $normalizer;

    public function __construct(
        UserManagerInterface $userManager,
        ProfileManagerInterface $profileManager,
        UserActionLogger $userActionLogger,
        EventDispatcherInterface $eventDispatcher,
        NormalizerInterface $normalizer
    ) {
        $this->userManager = $userManager;
        $this->profileManager = $profileManager;
        $this->userActionLogger = $userActionLogger;
        $this->eventDispatcher = $eventDispatcher;
        $this->normalizer = $normalizer;
    }

    /**
     * @Route("/settings", name="settings")
     * @Route("/settings/update", name="fos_user_profile_show",)
     */
    public function editUser(Request $request): ?Response
    {
        $user = $this->getUser();
        $keys = $user
            ? $user->getApiKey()
            : null;
        $clients = $user
            ? $user->getApiClients()
            : null;

       /*  if ($request->isMethod('patch')) {
            return $this->changePassOnTwofaActive($request);
        } */

        $passwordForm = $this->getPasswordForm($request, $keys);
        
        return $this->addDownloadCodesToResponse($this->renderSettings($passwordForm, $keys, $clients));
    }
    
    /**
     * @Route("/referral-program", name="referral-program")
     */
    public function referralProgram(PrelaunchConfig $prelaunchConfig): Response
    {
        return $this->render('pages/referral.html.twig', [
            'referralCode' => $this->getUser()->getReferralCode(),
            'referralPercentage' => $prelaunchConfig->getReferralFee() * 100,
            'referralsCount' => count($this->getUser()->getReferrals()),
        ]);
    }

    /**
     * @Rest\Route("/invite/{code}", name="register-referral", schemes={"https"})
     */
    public function registerReferral(string $code, AuthorizationCheckerInterface $authorizationChecker): Response
    {
        $response = $authorizationChecker->isGranted('IS_AUTHENTICATED_REMEMBERED')
            ? $this->redirectToRoute('homepage', [], 301)
            : $this->redirectToRoute('fos_user_registration_register', [], 301);

        $response->headers->setCookie(
            new Cookie('referral-code', $code)
        );

        return $response;
    }

    /** @Route("/settings/2fa", name="two_factor_auth")*/
    public function twoFactorAuth(
        Request $request,
        TwoFactorManagerInterface $twoFactorManager
    ): Response {
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

        return $this->redirectToRoute(
            'two_factor_auth',
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

        if (!$this->container->get('session')->getBag('attributes')->remove('download_backup_codes')) {
            return $this->redirectToRoute('settings');
        }

        /** @var User */
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
        if ($this->container->get('session')->getBag('attributes')->has('download_backup_codes')) {
            $response->headers->set('Refresh', "5;{$this->generateUrl('download_backup_codes', [], UrlGeneratorInterface::ABSOLUTE_URL)}");
        }

        return $response;
    }

    public function getBackupCodes(TwoFactorManagerInterface $twoFactorManager): array
    {
        $backupCodes = $twoFactorManager->generateBackupCodes();
        $user = $this->getUser();
        $user->setGoogleAuthenticatorBackupCodes($backupCodes);
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($user);
        $entityManager->flush();
        $this->container->get('session')->getBag('attributes')->set('download_backup_codes', 'download');

        return $backupCodes;
    }

    /** @Route("/settings/2fa/backupcodes/generate",
        name="generate_backup_codes",
        options={"2fa"="required"},
        defaults={"needToCheckCode" = false}
    )*/
    public function generateBackupCodes(TwoFactorManagerInterface $twoFactorManager): Response
    {
        $this->getBackupCodes($twoFactorManager);
        $this->addFlash('success', 'Downloading backup codes...');
        $this->userActionLogger->info('Downloaded Two-Factor backup codes');

        return $this->redirectToRoute('settings');
    }

    /**
     * @Rest\Patch(
     * "/settings/update-password",
     * name="update-password",
     * options={"2fa"="optional", "expose"=true})
     * @Rest\RequestParam(name="current_password", nullable=false)
     * @Rest\RequestParam(name="plainPassword", nullable=false)
     * @Rest\RequestParam(name="code", nullable=true)
     */
    public function changePassOnTwofaActive(Request $request): Response
    {
        $user = $this->getUser();
        $changePasswordData = $request->request->all();
        $passwordForm = $this->createForm(ChangePasswordType::class, $user, [
            'csrf_protection' => false,
            'allow_extra_fields' => true,
        ]);
        $passwordForm->submit(array_filter($changePasswordData, function ($value) {
            return null !== $value;
        }), false);

        if (!$passwordForm->isValid()) {
            return new JsonResponse(
                [
                    'status' => 'error',
                    'errors' => (string)$passwordForm->getErrors(true),
                ],
                JsonResponse::HTTP_BAD_REQUEST
            );
        }

        $this->userManager->updatePassword($user);
        $this->userManager->updateUser($user);

        return new JsonResponse(['status' => 'OK']);
    }

    private function getPasswordForm(Request $request, ?ApiKey $apiKey): FormInterface
    {
        /** @var User $user */
        $user = $this->getUser();
        $passwordForm = $this->createForm(ChangePasswordType::class, $user);
        $passwordForm->handleRequest($request);

        if ($passwordForm->isSubmitted() && $passwordForm->isValid()) {
            $this->userManager->updatePassword($user);
            $this->userManager->updateUser($user);
            $this->addFlash('success', 'Password was updated successfully');
            $this->eventDispatcher->dispatch(
                FOSUserEvents::CHANGE_PASSWORD_COMPLETED,
                new FilterUserResponseEvent(
                    $user,
                    $request,
                    $this->renderSettings(
                        $passwordForm,
                        $apiKey,
                        $user->GetApiClients()
                    )
                )
            );
        }

        return $passwordForm;
    }

    private function renderSettings(FormInterface $passwordForm, ?ApiKey $apiKey, ?array $clients): Response
    {
        return $this->render('pages/settings.html.twig', [
            'keys' => $this->normalizer->normalize($apiKey ?? [], null, [
                "groups" => ["API"],
            ]),
            'clients' => $this->normalizer->normalize($clients ?? [], null, [
                "groups" => ["API"],
            ]),
            'passwordForm' => $passwordForm->createView(),
            'twoFactorAuth' => $this->getUser()->isGoogleAuthenticatorEnabled(),
        ]);
    }

    private function turnOnAuthenticator(TwoFactorManagerInterface $twoFactorManager): array
    {
        $backupCodes = $this->getBackupCodes($twoFactorManager);
        $this->addFlash('success', 'Congratulations! You have enabled two-factor authentication!');
        $this->addFlash('success', 'Downloading backup codes...');
        $this->userActionLogger->info('Enable Two-Factor Authentication');

        return $backupCodes;
    }

    private function turnOffAuthenticator(TwoFactorManagerInterface $twoFactorManager): void
    {
        /** @var User */
        $user = $this->getUser();
        $googleAuth = $twoFactorManager->getGoogleAuthEntry($user->getId());
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->remove($googleAuth);
        $entityManager->flush();
        $this->container->get('session')->remove('googleSecreteCode');
        $this->addFlash('success', 'You have disabled two-factor authentication!');
        $this->userActionLogger->info('Disable Two-Factor Authentication');
    }

    private function generateBackupCodesFileName(): string
    {
        $name = $this->getUser()->getUsername();
        $time = date("H-i-d-m-Y");

        return "backup-codes-{$name}-{$time}.txt";
    }
}
