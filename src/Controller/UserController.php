<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\EditEmailType;
use App\Form\Model\EmailModel;
use App\Form\TwoFactorType;
use App\Manager\ProfileManagerInterface;
use App\Manager\TwoFactorManagerInterface;
use App\Manager\UserManagerInterface;
use App\Utils\MailerDispatcherInterface;
use DateInterval;
use DateTime;
use FOS\UserBundle\Form\Type\ResettingFormType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class UserController extends AbstractController
{
    /** @var MailerDispatcherInterface */
    protected $mailDispatcher;

    /** @var UserManagerInterface */
    protected $userManager;

    /** @var ProfileManagerInterface */
    protected $profileManager;
    
    private const REFERRAL_COOKIE_EXPIRE_DAYS = 7;

    public function __construct(
        MailerDispatcherInterface $mailDispatcher,
        UserManagerInterface $userManager,
        ProfileManagerInterface $profileManager
    ) {
        $this->mailDispatcher = $mailDispatcher;
        $this->userManager = $userManager;
        $this->profileManager = $profileManager;
    }

    /**
     * @Route("/settings", name="settings")
     */
    public function editUser(Request $request): Response
    {
        return $this->render('pages/settings.html.twig', [
            'emailForm' => $this->getEmailForm($request)->createView(),
            'passwordForm' => $this->getPasswordForm($request)->createView(),
            'twoFactorAuth' => $this->getUser()->isGoogleAuthenticatorEnabled(),
        ]);
    }

    private function getPasswordForm(Request $request): FormInterface
    {
        $user = $this->getUser();
        $passwordForm = $this->createForm(ResettingFormType::class, $user);
        $passwordForm->handleRequest($request);

        if ($passwordForm->isSubmitted() && $passwordForm->isValid()) {
            $this->userManager->updatePassword($user);
            $this->userManager->updateUser($user);
            $this->addFlash('success', 'Password was updated successfully');
        }

        return $passwordForm;
    }

    private function getEmailForm(Request $request): FormInterface
    {
        $user = $this->getUser();
        $email = new EmailModel($user->getEmail());
        $emailForm = $this->createForm(EditEmailType::class, $email);
        $emailForm->handleRequest($request);

        if ($emailForm->isSubmitted() && $emailForm->isValid() && $user->getEmail() !== $email->getEmail()) {
            // Create temporary user with new email and use him in email sender.
            // Set new email as temporary for user
            $tmpUser = clone $user;
            $tmpUser->setEmail($email->getEmail());
            $user->setTempEmail($email->getEmail());
            $this->mailDispatcher->sendEmailConfirmation($tmpUser);
            $user->setConfirmationToken($tmpUser->getConfirmationToken());
            $this->userManager->updateUser($user);

            $this->addFlash('success', 'Confirmation email was sent to your new address');
        }

        return $emailForm;
    }

    /** @Route("/settings/2fa", name="two_factor_auth")*/
    public function twoFactorAuthAction(
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

        if (!$form->isSubmitted() || !$form->isValid()) {
            return $this->render('security/2fa_manager.html.twig', $parameters);
        }

        if ($twoFactorManager->checkCode($user, $form)) {
            if ($isTwoFactor) {
                $this->turnOffAuthenticator($twoFactorManager);
                return $this->redirectToRoute('settings');
            }
            $parameters['backupCodes'] = $this->turnOnAuthenticator($twoFactorManager, $user);
            return $this->render('security/2fa_manager.html.twig', $parameters);
        }

        $this->addFlash('danger', 'Invalid two-factor authentication code.');
        return $this->render('security/2fa_manager.html.twig', $parameters);
    }

    private function turnOnAuthenticator(TwoFactorManagerInterface $twoFactorManager, User $user): array
    {
        $backupCodes = $twoFactorManager->generateBackupCodes();
        $user->setGoogleAuthenticatorBackupCodes($backupCodes);
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($user);
        $entityManager->flush();
        $this->addFlash('success', 'Congratulations! You have enabled two-factor authentication!');
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
        $this->addFlash('notice', 'You have disabled two-factor authentication!');
    }
    
    /**
     * @Route("/referral", name="referral")
     */
    public function referral(UserManagerInterface $userManager): Response
    {echo($this->getUser()->getReferralCode()); exit;
        if (null  === $this->getUser()) {
            return $this->redirect('/login');
        }
        return $this->render('pages/referral.html.twig', [
            'referralCode' => $this->getUser()->getReferralCode(),
            'referralsTotal' => $userManager->getReferencesTotal(intval($this->getUser()->getId())),
        ]);
    }

    /**
     * @Route("/invite/{referralCode}", name="register_referral", requirements={"referralCode" = "^[0-9a-f]{8}(-[0-9a-f]{4}){3}-[0-9a-f]{12}$"})
     */
    public function registerReferral(Request $request, string $referralCode): Response
    {
        $response = $this->redirectToRoute('fos_user_registration_register');
        $response->headers->setCookie($this->createReferralCookie($referralCode));
        $request->getSession()->set('referral', $referralCode);
        return $response;
    }
    
    private function createReferralCookie(string $referralCode): Cookie
    {
        $cookieExpireTime = new DateTime();
        $cookieExpireTime->add(new DateInterval(
            'P'.self::REFERRAL_COOKIE_EXPIRE_DAYS.'D'
        ));
        return new Cookie('referral', $referralCode, $cookieExpireTime);
    }
}
