<?php declare(strict_types = 1);

namespace App\Controller;

use App\Entity\User;
use App\Manager\UserManagerInterface;
use FOS\UserBundle\Mailer\MailerInterface;
use FOS\UserBundle\Util\TokenGeneratorInterface;
use Sonata\AdminBundle\Controller\CRUDController as Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class UserAdminCRUDController extends Controller
{
    /**
     * @param mixed $id
     * @return RedirectResponse
     * @throws NotFoundHttpException
     */
    public function resetPasswordAction(
        $id,
        Request $request,
        UserManagerInterface $userManager,
        TokenGeneratorInterface $tokenGenerator,
        MailerInterface $mailer
    ): RedirectResponse {
        $id = $request->get($this->admin->getIdParameter());
        $user = $this->admin->getObject($id);

        if (!$user) {
            throw $this->createNotFoundException(sprintf('unable to find the object with id: %s', $id));
        }

        $this->sendResettingEmailMessage($user, $userManager, $tokenGenerator, $mailer);

        $this->addFlash(
            'sonata_flash_success',
            'An email has been sent. It contains a link which user has to follow to reset their password.'
        );

        return new RedirectResponse($this->admin->generateUrl('list'));
    }

    private function sendResettingEmailMessage(
        User $user,
        UserManagerInterface $userManager,
        TokenGeneratorInterface $tokenGenerator,
        MailerInterface $mailer
    ): void {
        if (null === $user->getConfirmationToken()) {
            $user->setConfirmationToken($tokenGenerator->generateToken());
        }

        $mailer->sendResettingEmailMessage($user);
        $user->setPasswordRequestedAt(new \DateTime());
        $userManager->updateUser($user);
    }
}
