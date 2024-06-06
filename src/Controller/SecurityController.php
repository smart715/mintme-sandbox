<?php declare(strict_types = 1);

namespace App\Controller;

use App\Config\WithdrawalDelaysConfig;
use App\Controller\Traits\ReferralRedirectionTrait;
use App\Entity\User;
use App\Form\CaptchaLoginType;
use App\Logger\UserActionLogger;
use App\Manager\AirdropReferralCodeManagerInterface;
use App\Manager\BlacklistIpManagerInterface;
use App\Manager\TokenManagerInterface;
use App\Manager\UserManagerInterface;
use App\Security\PathRoles;
use App\Security\Request\RefererRequestHandlerInterface;
use App\Utils\LockFactory;
use FOS\UserBundle\Controller\SecurityController as FOSSecurityController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/** @phpstan-ignore-next-line final class */
class SecurityController extends FOSSecurityController
{
    public const MAIN_REDIRECT_ROUTE = 'show_user_feed';

    private UserActionLogger $userActionLogger;

    private SessionInterface $session;

    private bool $formContentOnly = false; // phpcs:ignore

    private LockFactory $lockFactory;

    private RefererRequestHandlerInterface $refererRequestHandler;

    private BlacklistIpManagerInterface $blacklistIpManager;

    private WithdrawalDelaysConfig $withdrawalDelaysConfig;

    private TokenManagerInterface $tokenEntityManager;

    use ReferralRedirectionTrait;

    public function __construct(
        UserActionLogger $userActionLogger,
        SessionInterface $session,
        RefererRequestHandlerInterface $refererRequestHandler,
        LockFactory $lockFactory,
        BlacklistIpManagerInterface $blacklistIpManager,
        UserManagerInterface $userManager,
        AirdropReferralCodeManagerInterface $arcManager,
        WithdrawalDelaysConfig $withdrawalDelaysConfig,
        TokenManagerInterface $tokenEntityManager,
        ?CsrfTokenManagerInterface $tokenManager = null
    ) {
        $this->userActionLogger = $userActionLogger;
        $this->session = $session;
        $this->refererRequestHandler = $refererRequestHandler;
        $this->lockFactory = $lockFactory;
        $this->blacklistIpManager = $blacklistIpManager;
        $this->userManager = $userManager;
        $this->arcManager = $arcManager;
        $this->withdrawalDelaysConfig = $withdrawalDelaysConfig;
        $this->tokenEntityManager = $tokenEntityManager;

        parent::__construct($tokenManager);
    }

    /** @Route("/login", name="login", options={"expose"=true}) */
    public function loginAction(Request $request): Response
    {
        $securityContext = $this->container->get('security.authorization_checker');

        if ($securityContext->isGranted('IS_AUTHENTICATED_REMEMBERED') ||
            $securityContext->isGranted('IS_AUTHENTICATED_FULLY')) {
                return $this->redirectToRoute('login_success');
        }

        $refers = $request->headers->get('Referer');

        $this->formContentOnly = (bool)$request->get('formContentOnly', false);

        if (!$refers && $targetPath = $this->session->get('_security.main.target_path', null)) {
            $request->headers->set('Referer', $targetPath);
            $refers = $targetPath;
        }

        if ($refers && !in_array($refers, $this->refererRequestHandler->refererUrlsToSkip(), true)) {
            $this->session->set('login_referer', $refers);
        }

        return parent::loginAction($request);
    }

    /** @Route("/logout_success", name="logout_success") */
    public function postLogoutRedirectAction(PathRoles $pathRoles): Response
    {
        $hasAuthenticated = $this->session->get('has_authenticated');
        $referer = $this->session->get('logout_referer');
        $this->session->invalidate();
        $this->session->clear();

        if ($referer) {
            $roles = $pathRoles->getRoles(Request::create($referer));

            if (null === $roles) {
                return $this->redirect($referer);
            }
        }

        return $hasAuthenticated
            ? $this->redirectToRoute("homepage")
            : $this->redirectToRoute("login");
    }

    /** @Route("/login_success", name="login_success") */
    public function postLoginRedirectAction(Request $request): Response
    {
        /** @var User|null $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute(self::MAIN_REDIRECT_ROUTE);
        }

        $this->userActionLogger->info('Log in');

        $referer = $this->session->get('login_referer');
        $refererRoute = $this->refererMappings()[$referer] ?? '';

        $withdrawAfterLoginTime = $this->withdrawalDelaysConfig->getWithdrawAfterLoginTime();
        $lockWithdrawLogin = $this->lockFactory->createLock(
            LockFactory::LOCK_WITHDRAW_AFTER_LOGIN.$user->getId(),
            $withdrawAfterLoginTime,
            false
        );
        $lockWithdrawLogin->acquire();

        if ($refererRoute) {
            $this->session->remove('login_referer');

            return $this->redirectToRoute($refererRoute);
        }

        if ($referer && $this->refererRequestHandler->isRefererValid($referer)) {
            $this->session->remove('login_referer');

            return $this->redirect($referer);
        }

        if ($referralRedirection = $this->referralRedirect($request, $this->tokenEntityManager)) {
            return $referralRedirection;
        }

        return $this->redirectToRoute(self::MAIN_REDIRECT_ROUTE);
    }

    /**
     * @param array $data
     * @return Response
     */
    protected function renderLogin(array $data): Response
    {
        $template = $this->formContentOnly
            ? '@FOSUser/Security/login_form_content.html.twig'
            : '@FOSUser/Security/login.html.twig';

        $form = $this->createForm(CaptchaLoginType::class);

        return $this->render($template, array_merge($data, [
            'form' => $form->createView(),
            'embedded' => $this->formContentOnly,
        ]));
    }

    private function refererMappings(): array
    {
        return [
            $this->generateUrl('nelmio_api_doc.swagger_ui', [], UrlGeneratorInterface::ABSOLUTE_URL) => 'settings',
        ];
    }

    /** @Route("/blacklist-ip", name="blacklist_ip") */
    public function blacklistIp(Request $request): Response
    {
        $address = $request->getClientIp();
        $blacklistIp = $this->blacklistIpManager->getBlacklistIpByAddress($address);

        if (!$this->blacklistIpManager->isBlacklistedIp($blacklistIp)) {
            return $this->redirectToRoute('homepage');
        }

        $hours = $this->blacklistIpManager->getMustWaitHours($blacklistIp);

        return $this->render('pages/blacklist_ip.html.twig', ['hours' => $hours]);
    }

    public function pageNotFoundAction(): void
    {
        throw new NotFoundHttpException();
    }

    /** @Route("/auto-logout-redirection", name="auto_logout_redirection", options={"expose"=true}) */
    public function autoLogoutRedirection(Request $request, PathRoles $pathRoles): Response
    {
        $refers = $request->headers->get('Referer');
        $message = $request->get('auto_log_out');

        $this->session->invalidate();
        $this->session->clear();

        if ($message) {
            $this->addFlash('auto_logout', $message);
        }

        if (!$refers) {
            return $this->redirectToRoute('homepage');
        }

        $roles = $pathRoles->getRoles(Request::create($refers));

        if (null === $roles) {
            return $this->redirect($refers);
        }

        return $this->redirectToRoute('homepage');
    }
}
