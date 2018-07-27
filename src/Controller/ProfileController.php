<?php

namespace App\Controller;

use App\Entity\Profile;
use App\Form\EditProfileType;
use App\Repository\ProfileRepository;
use Doctrine\Common\Persistence\ObjectManager;
use FOS\UserBundle\Form\Type\ResettingFormType;
use FOS\UserBundle\Model\UserManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/profile")
 */
class ProfileController extends AbstractController
{
    /**
     * @Route("/edit")
     */
    public function editProfile(UserManagerInterface $userManager): Response
    {
        $em = $this->getEntityManager();

        $profile = $this->getProfileRepository()->getProfileByUser($this->getUser());
        $profileForm = $this->createForm(EditProfileType::class, $profile);

        if ($profileForm->isSubmitted() && $profileForm->isValid()) {
            $em->persist($profile);
            $em->flush();
            $this->addFlash('success', 'Profile was updated successfully');
        }

        $user = $this->getUser();
        $passwordForm = $this->createForm(ResettingFormType::class, $user);

        if ($passwordForm->isSubmitted() && $passwordForm->isValid()) {
            $userManager->updatePassword($user);
            $this->addFlash('success', 'Password was updated successfully');
        }

        $emailForm = $this->createFormBuilder($user)
            ->add('tempEmail', EmailType::class, [ 'label' => 'Email:' ])
            ->getForm();

        if ($emailForm->isSubmitted() && $emailForm->isValid()) {
            //TODO: send confirmation email
            $this->addFlash('success', 'Confirmation email was sent to your new address');
        }

        return $this->render('pages/settings.html.twig', [
            'profileForm' => $profileForm->createView(),
            'emailForm' => $emailForm->createView(),
            'passwordForm' => $passwordForm->createView(),
        ]);
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
