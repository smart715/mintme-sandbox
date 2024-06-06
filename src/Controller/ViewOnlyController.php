<?php declare(strict_types = 1);

namespace App\Controller;

use App\Entity\Profile;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Manager\PhoneNumberManagerInterface;
use App\Manager\ProfileManagerInterface;
use App\Manager\TokenManagerInterface;
use App\Manager\UserManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/admin-r8bn/view")
 * @Security(expression="is_granted('ROLE_ADMIN')")
 * @codeCoverageIgnore
 */
class ViewOnlyController extends Controller
{
    private TokenManagerInterface $tokenManager;
    private UserManagerInterface $userManager;
    private PhoneNumberManagerInterface $phoneNumberManager;
    private ProfileManagerInterface $profileManager;

    public function __construct(
        UserManagerInterface $userManager,
        TokenManagerInterface $tokenManager,
        PhoneNumberManagerInterface $phoneNumberManager,
        ProfileManagerInterface $profileManager
    ) {
        $this->userManager = $userManager;
        $this->tokenManager = $tokenManager;
        $this->phoneNumberManager = $phoneNumberManager;
        $this->profileManager = $profileManager;
    }

    /**
     * @Route("/", name="view_only", options={"expose"=true})
     */
    public function view(): Response
    {
        return $this->render('admin/view_only.html.twig');
    }

    /**
     * @Route("/check/", name="check_email_address", options={"expose"=true})
     */
    public function viewCheck(Request $request): Response
    {
        $email = $request->query->get('email') ?? '';
        $nickname = $request->query->get('nickname') ?? '';
        $tokenString = $request->query->get('token') ?? '';

        return $this->render('admin/check_email_address.html.twig', [
            'email' => $email,
            'nickname' => $nickname,
            'token' => $tokenString,
        ]);
    }

    /**
     * @Route("/email/{email}", name="view_only_email", options={"expose"=true})
     */
    public function viewByEmail(
        string $email,
        Request $request
    ): RedirectResponse {
        /** @var string $referer */
        $referer = $request->headers->get('referer');

        /** @var User|null $user */
        $user = $this->userManager->findUserByEmail($email);

        if (null === $user) {
            return $this->redirect($referer);
        }

        $this->addFlash(
            'success',
            'You have been switched to another user account through the email account: '. $email
        );

        return $this->redirectToRoute('trading', ['_switch_user' => $user->getUsername()]);
    }

    /**
     * @Route("/email1", name="view_only_email1", options={"expose"=true})
     */
    public function viewEmail1(Request $request): RedirectResponse
    {
        return $this->viewByEmail($request->query->get('email'), $request);
    }

    /**
     * @Route("/token/{name}", name="view_only_token", options={"expose"=true})
     */
    public function viewByToken(
        string $name,
        Request $request
    ): RedirectResponse {
        /** @var string $referer */
        $referer = $request->headers->get('referer');

        /** @var Token|null $token */
        $token = $this->tokenManager->findByName($name);

        if (null === $token) {
            return $this->redirect($referer);
        }

        /** @var User|null $user */
        $user = $token->getProfile()->getUser();

        if (null === $user) {
            return $this->redirect($referer);
        }

        $this->addFlash(
            'success',
            'You have been switched to another user account through the token: '.$name
        );

        return $this->redirectToRoute('trading', ['_switch_user' => $user->getUsername()]);
    }

    /**
     * @Route("/token1", name="view_only_token1", options={"expose"=true})
     */
    public function viewToken1(Request $request): RedirectResponse
    {
        return $this->viewByToken($request->query->get('token'), $request);
    }

    /**
     * @Route("/check/token", name="check_email_address_token", options={"expose"=true})
     */
    public function viewCheckToken(Request $request): RedirectResponse
    {
        $tokenString = $request->request->get('token');
        /** @var Token|null $token */
        $token = $this->tokenManager->findByName($tokenString);

        if (null === $token) {
            return $this->renderEmailNotFound();
        }

        /** @var string|null $email */
        $email = $token->getProfile()->getUserEmail();

        if (null === $email) {
            return $this->renderEmailNotFound();
        }

        return $this->redirectToRoute('check_email_address', [
            'email' => $email,
            'nickname' => '',
            'token' => $tokenString,
        ]);
    }

    /**
     * @Route("/check/nickname", name="check_email_address_nickname", options={"expose"=true})
     */
    public function viewCheckNickname(Request $request): RedirectResponse
    {
        $nickname = $request->request->get('nickname');
        /** @var Profile|null $profile */
        $profile = $this->profileManager->getProfileByNickname($nickname);

        if (null === $profile) {
            return $this->renderEmailNotFound();
        }

        /** @var string|null $email */
        $email = $profile->getUserEmail();

        if (null === $email) {
            return $this->renderEmailNotFound();
        }

        return $this->redirectToRoute('check_email_address', [
            'email' => $email,
            'nickname' => $nickname,
            'token' => '',
        ]);
    }

    /**
     * @Route("/phone/{phone}", name="view_only_phone", options={"expose"=true})
     */
    public function viewByPhone(
        string $phone,
        Request $request
    ): RedirectResponse {
        /** @var string $referer */
        $referer = $request->headers->get('referer');

        $phoneUtil = \libphonenumber\PhoneNumberUtil::getInstance();

        try {
            $phone2 = $phoneUtil->parse($phone);
        } catch (\libphonenumber\NumberParseException $e) {
            return $this->redirect($referer);
        }

        $phoneNumber = $this->phoneNumberManager->findByPhoneNumber($phone2);

        if (null === $phoneNumber) {
            return $this->redirect($referer);
        }

        /** @var User|null $user */
        $user = $phoneNumber->getProfile()->getUser();

        if (null === $user) {
            return $this->redirect($referer);
        }

        $this->addFlash(
            'success',
            'You have been switched to another user account through the phone number: '.$phone
        );

        return $this->redirectToRoute('trading', ['_switch_user' => $user->getUsername()]);
    }

    /**
     * @Route("/phone1", name="view_only_phone1", options={"expose"=true})
     */
    public function viewPhone1(Request $request): RedirectResponse
    {
        return $this->viewByPhone($request->query->get('phone'), $request);
    }

    private function renderEmailNotFound(): RedirectResponse
    {
        return $this->redirectToRoute('check_email_address', [
            'email' => '',
            'nickname' => '',
            'token' => '',
        ]);
    }
}
