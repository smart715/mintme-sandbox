<?php declare(strict_types = 1);

namespace App\Controller;

use App\Form\CaptchaLoginType;
use App\Logger\UserActionLogger;
use App\Security\PathRoles;
use App\Security\Request\RefererRequestHandlerInterface;
use FOS\UserBundle\Controller\SecurityController as FOSSecurityController;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class SecurityController extends FOSSecurityController
{
    public const MAIN_REDIRECT_ROUTE = 'trading';

    /** @var ContainerInterface $container */
    protected $container;

    /** @var FormInterface|null */
    private $form;

    /** @var UserActionLogger */
    private $userActionLogger;

    /** @var SessionInterface */
    private $session;

    /** @var bool */
    private $formContentOnly = false;

    private RefererRequestHandlerInterface $refererRequestHandler;

    public function __construct(
        ContainerInterface $container,
        UserActionLogger $userActionLogger,
        SessionInterface $session,
        RefererRequestHandlerInterface $refererRequestHandler,
        ?CsrfTokenManagerInterface $tokenManager = null
    ) {
        $this->container = $container;
        $this->userActionLogger = $userActionLogger;
        $this->session = $session;
        parent::__construct($tokenManager);
        $this->refererRequestHandler = $refererRequestHandler;
    }

    /** @Route("/login", name="login", options={"expose"=true}) */
    public function loginAction(Request $request): Response
    {
        $securityContext = $this->container->get('security.authorization_checker');

        if ($securityContext->isGranted('IS_AUTHENTICATED_REMEMBERED') ||
            $securityContext->isGranted('IS_AUTHENTICATED_FULLY')) {
                return $this->redirectToRoute('login_success');
        }

        $this->form = $this->createForm(CaptchaLoginType::class);
        $this->form->handleRequest($request);

        $refers = $request->headers->get('Referer');

        $this->formContentOnly = $request->get('formContentOnly', false);

        if ($refers && !in_array($refers, $this->refererRequestHandler->refererUrlsToSkip(), true)) {
            $this->session->set('login_referer', $refers);
        }

        if ($this->form->isSubmitted() && $this->form->isValid()) {
            return $this->redirectToRoute('fos_user_security_check', [
                'request' => $request,
            ], 307);
        }

        return parent::loginAction($request);
    }

    /** @Route("/logout_success", name="logout_success") */
    public function postLogoutRedirectAction(PathRoles $pathRoles): Response
    {
        $hasAuthenticated = $this->session->get('has_authenticated');
        $referer = $this->session->get('logout_referer');
        $this->session->clear();

        if ($referer) {
            $roles = $pathRoles->getRoles(Request::create($referer));

            if (null === $roles && $this->refererRequestHandler->noRedirectToMainPage($referer)) {
                return $this->redirect($referer);
            }
        }

        return $hasAuthenticated
            ? $this->redirectToRoute("homepage")
            : $this->redirectToRoute("login");
    }

    /** @Route("/login_success", name="login_success") */
    public function postLoginRedirectAction(): Response
    {
        $this->userActionLogger->info('Log in');

        $referer = $this->session->get('login_referer');
        $refererRoute = $this->refererMappings()[$referer] ?? '';

        if ($refererRoute) {
            $this->session->remove('login_referer');

            return $this->redirectToRoute($refererRoute);
        }

        if ($referer && $this->refererRequestHandler->isRefererValid($referer)) {
            $this->session->remove('login_referer');

            return $this->redirect($referer);
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

        return $this->render($template, array_merge($data, [
            'form' => $this->form->createView(),
        ]));
    }

    private function refererMappings(): array
    {
        return [
            $this->generateUrl('nelmio_api_doc.swagger_ui', [], UrlGeneratorInterface::ABSOLUTE_URL) => 'settings',
        ];
    }

    public function pageNotFoundAction(): void
    {
        throw new NotFoundHttpException();
    }
}
