<?php

namespace App\Controller;

use App\Form\EditEmailType;
use App\Form\EditProfileType;
use App\Form\Model\EmailModel;
use App\Manager\ProfileManagerInterface;
use App\Utils\MailerDispatcherInterface;
use Doctrine\Common\Persistence\ObjectManager;
use FOS\UserBundle\Form\Type\ResettingFormType;
use FOS\UserBundle\Model\UserManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @Route("/profile")
 */
class ProfileController extends AbstractController
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
     * @Route(name="profile")
     */
    public function editProfile(Request $request): Response
    {
        return $this->render('pages/profile.html.twig', [
            'profileForm' => $this->getProfileForm($request)->createView(),
            'emailForm' => $this->getEmailForm($request)->createView(),
            'passwordForm' => $this->getPasswordForm($request)->createView(),
        ]);
    }

    private function getProfileForm(Request $request): FormInterface
    {
        $em = $this->getEntityManager();

        $profile = $this->profileManager->getProfile($this->getUser());

        if (null === $profile)
            throw new NotFoundHttpException('Profile doesn\'t exist');

        $profileForm = $this->createForm(EditProfileType::class, $profile);
        $profileForm->handleRequest($request);

        if ($profileForm->isSubmitted() && $profileForm->isValid()) {
            if ($profile->isChangesLocked()) {
                $this->profileManager->lockChangePeriod($profile);
            }
            $em->persist($profile);
            $em->flush();
            $this->addFlash('success', 'Profile was updated successfully');
        }

        return $profileForm;
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

        if ($emailForm->isSubmitted() && $emailForm->isValid()) {
            // Create temporary user with new email and use him in email sender.
            // Set new email as temproary for user
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

    private function getEntityManager(): ObjectManager
    {
        return $this->getDoctrine()->getManager();
    }
}
