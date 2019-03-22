<?php declare(strict_types = 1);

namespace App\Controller;

use App\Entity\User;
use App\Exchange\Trade\Config\PrelaunchConfig;
use App\Form\EditEmail2FAType;
use App\Form\EditEmailType;
use App\Form\Model\EmailModel;
use App\Form\TwoFactorType;
use App\Manager\ProfileManagerInterface;
use App\Manager\TwoFactorManagerInterface;
use App\Utils\MailerDispatcherInterface;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\UserBundle\Form\Type\ChangePasswordFormType;
use FOS\UserBundle\Form\Type\ResettingFormType;
use FOS\UserBundle\Model\UserManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class UserController extends AbstractController
{
    /** @var MailerDispatcherInterface */
    protected $mailDispatcher;

    /** @var UserManagerInterface */
    protected $userManager;

    /** @var ProfileManagerInterface */
    protected $profileManager;

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
     * @Route("/settings/update", name="fos_user_profile_show")
     */
    public function editUser(Request $request): Response
    {
        $user = $this->getUser();
        $email = new EmailModel($user->getEmail());
        $emailForm = $this->createForm(EditEmailType::class, $email);
        $emailForm->handleRequest($request);
        $passwordForm = $this->getPasswordForm($request);

        if ($user->isGoogleAuthenticatorEnabled()) {
            $emailForm2FA = $this->createForm(EditEmail2FAType::class, $email);
            $emailForm2FA->handleRequest($request);

            return $this->renderSettings2FA($passwordForm, $emailForm, $emailForm2FA);
        }

        if ($emailForm->isSubmitted() && $emailForm->isValid()) {
            $this->submitEmailForm($email);
        }

        return $this->renderSettings($passwordForm, $emailForm);
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
     * @Rest\Route("/invite/{code}", name="register-referral")
     */
    public function registerReferral(string $code, AuthorizationCheckerInterface $authorizationChecker): Response
    {
        $response = $authorizationChecker->isGranted('IS_AUTHENTICATED_REMEMBERED')
            ? $this->redirectToRoute('homepage')
            : $this->redirectToRoute('fos_user_registration_register');

        $response->headers->setCookie(
            new Cookie('referral-code', $code)
        );

        return $response;
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

        if ($isTwoFactor) {
            $this->turnOffAuthenticator($twoFactorManager);

            return $this->redirectToRoute('settings');
        }

        $parameters['backupCodes'] = $this->turnOnAuthenticator($twoFactorManager, $user);

        return $this->render('security/2fa_manager.html.twig', $parameters);
    }

    private function getPasswordForm(Request $request): FormInterface
    {
        $user = $this->getUser();
        $passwordForm = $this->createForm(ChangePasswordFormType::class, $user);
        $passwordForm->handleRequest($request);

        if ($passwordForm->isSubmitted() && $passwordForm->isValid()) {
            $this->userManager->updatePassword($user);
            $this->userManager->updateUser($user);
            $this->addFlash('success', 'Password was updated successfully');
        }

        return $passwordForm;
    }

    private function renderSettings(FormInterface $passwordForm, FormInterface $emailForm): Response
    {
        return $this->render('pages/settings.html.twig', [
            'emailForm' => $emailForm->createView(),
            'passwordForm' => $passwordForm->createView(),
            'twoFactorAuth' => $this->getUser()->isGoogleAuthenticatorEnabled(),
        ]);
    }

    private function renderSettings2FA(
        FormInterface $passwordForm,
        FormInterface $emailForm,
        FormInterface $emailForm2FA
    ): Response {
        if ($emailForm2FA->isSubmitted() && !$emailForm2FA->isValid() || $emailForm->isSubmitted() && $emailForm->isValid()) {
            return $this->render('default/simple_form.html.twig', [
                'form' => $emailForm2FA->createView(),
                'formHeader' => 'Enter two-factor code to confirm Edit Email',
            ]);
        }

        if ($emailForm2FA->isSubmitted()) {
            /** @var EmailModel $email */
            $email = $emailForm2FA->getRoot()->getData();
            $this->submitEmailForm($email);
        }

        return $this->renderSettings($passwordForm, $emailForm);
    }

    private function submitEmailForm(EmailModel $email): void
    {
        $user = $this->getUser();
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
        $this->addFlash('success', 'You have disabled two-factor authentication!');
    }
}
