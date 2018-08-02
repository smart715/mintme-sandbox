<?php

namespace App\Controller;

use App\Entity\Profile;
use App\Form\EditEmailType;
use App\Form\EditPasswordType;
use App\Form\EditProfileType;
use App\Form\Model\EmailModel;
use App\Repository\ProfileRepository;
use App\Utils\MailerDispatcherInterface;
use Doctrine\Common\Persistence\ObjectManager;
use FOS\UserBundle\Model\UserManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/profile")
 */
class ProfileController extends AbstractController
{
    /** @var MailerDispatcherInterface */
    protected $dispatcher;

    /** @var UserManagerInterface */
    protected $userManager;

    public function __construct(
        MailerDispatcherInterface $dispatcher,
        UserManagerInterface $userManager
    ) {
        $this->dispatcher = $dispatcher;
        $this->userManager = $userManager;
    }

    /**
     * @Route("/edit")
     */
    public function editProfile(Request $request): Response
    {
        return $this->render('pages/settings.html.twig', [
            'profileForm' => $this->getProfileForm($request)->createView(),
            'emailForm' => $this->getEmailForm($request)->createView(),
            'passwordForm' => $this->getPasswordForm($request)->createView(),
        ]);
    }

    private function getProfileForm(Request $request): FormInterface
    {
        $em = $this->getEntityManager();

        $profile = $this->getProfileRepository()->getProfileByUser($this->getUser());

        $profileForm = $this->createForm(EditProfileType::class, $profile);
        $profileForm->handleRequest($request);

        if ($profileForm->isSubmitted() && $profileForm->isValid()) {
            $em->persist($profile);
            $em->flush();
            $this->addFlash('success', 'Profile was updated successfully');
        }

        return $profileForm;
    }

    private function getPasswordForm(Request $request): FormInterface
    {
        $user = $this->getUser();
        $passwordForm = $this->createForm(EditPasswordType::class, $user);
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
            // Create temporary user with new email and use him in email sender. Set new email as temproary for user
            $tmpUser = clone $user;
            $tmpUser->setEmail($email->getEmail());
            $user->setTempEmail($email->getEmail());
            $this->dispatcher->sendEmailConfirmation($tmpUser);
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

    private function getProfileRepository(): ProfileRepository
    {
        return $this->getDoctrine()->getRepository(Profile::class);
    }
}
