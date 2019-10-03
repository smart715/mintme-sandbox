<?php declare(strict_types = 1);

namespace App\Controller;

use App\Entity\ApiKey;
use App\Entity\User;
use App\Exchange\Trade\Config\PrelaunchConfig;
use App\Form\TwoFactorType;
use App\Logger\UserActionLogger;
use App\Manager\ProfileManagerInterface;
use App\Manager\TwoFactorManagerInterface;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\UserBundle\Event\FilterUserResponseEvent;
use FOS\UserBundle\Form\Type\ChangePasswordFormType;
use FOS\UserBundle\FOSUserEvents;
use FOS\UserBundle\Model\UserManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
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
     * @Route("/settings/update", name="fos_user_profile_show")
     */
    public function editUser(Request $request): Response
    {
        $user = $this->getUser();
        $keys = $user
            ? $user->getApiKey()
            : null;
        $passwordForm = $this->getPasswordForm($request, $keys);

        return $this->renderSettings($passwordForm, $keys);
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

            return $this->render('security/2fa_manager.html.twig', $parameters);
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
            ['backupCodes' => $this->turnOnAuthenticator($twoFactorManager, $user) ]
        );
    }

    private function getPasswordForm(Request $request, ?ApiKey $apiKey): FormInterface
    {
        $user = $this->getUser();
        $passwordForm = $this->createForm(ChangePasswordFormType::class, $user);
        $passwordForm->handleRequest($request);

        if ($passwordForm->isSubmitted() && $passwordForm->isValid()) {
            $this->userManager->updatePassword($user);
            $this->userManager->updateUser($user);
            $this->addFlash('success', 'Password was updated successfully');
            $this->eventDispatcher->dispatch(
                FOSUserEvents::CHANGE_PASSWORD_COMPLETED,
                new FilterUserResponseEvent($user, $request, $this->renderSettings($passwordForm, $apiKey))
            );
        }

        return $passwordForm;
    }

    private function renderSettings(FormInterface $passwordForm, ?ApiKey $apiKey): Response
    {
        return $this->render('pages/settings.html.twig', [
            'keys' => $this->normalizer->normalize($apiKey ?? [], null, [
                "groups" => ["API"],
            ]),
            'passwordForm' => $passwordForm->createView(),
            'twoFactorAuth' => $this->getUser()->isGoogleAuthenticatorEnabled(),
        ]);
    }

    private function turnOnAuthenticator(TwoFactorManagerInterface $twoFactorManager, User $user): array
    {
        $backupCodes = $twoFactorManager->generateBackupCodes();
        $user->setGoogleAuthenticatorBackupCodes($backupCodes);
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($user);
        $entityManager->flush();
        $this->addFlash('success', 'Congratulations! You have enabled two-factor authentication!');
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
        $this->addFlash('success', 'You have disabled two-factor authentication!');
        $this->userActionLogger->info('Disable Two-Factor Authentication');
    }
}
