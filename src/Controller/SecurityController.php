<?php declare(strict_types = 1);

namespace App\Controller;

use App\Exchange\Trade\Config\PrelaunchConfig;
use App\Form\CaptchaLoginType;
use App\Logger\UserActionLogger;
use FOS\UserBundle\Controller\SecurityController as FOSSecurityController;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class SecurityController extends FOSSecurityController
{
    public const ALLOWED_REFERER_ROUTES = [
        'token_show',
    ];

    /** @var ContainerInterface $container */
    protected $container;

    /** @var FormInterface|null */
    private $form;

    /** @var UserActionLogger */
    private $userActionLogger;
    
    /** @var SessionInterface */
    private $session;

    public function __construct(
        ContainerInterface $container,
        UserActionLogger $userActionLogger,
        SessionInterface $session,
        ?CsrfTokenManagerInterface $tokenManager = null
    ) {
        $this->container = $container;
        $this->userActionLogger = $userActionLogger;
        $this->session = $session;
        parent::__construct($tokenManager);
    }

    /** @Route("/login", name="login") */
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

        if (!empty($refers) && $refers !== $this->generateUrl('login', [], UrlGeneratorInterface::ABSOLUTE_URL)) {
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
    public function postLogoutRedirectAction(): Response
    {
        $hasAuthenticated = $this->session->get('has_authenticated');
        $this->session->clear();

        return $hasAuthenticated
            ? $this->redirectToRoute("homepage")
            : $this->redirectToRoute("login");
    }

    /** @Route("/login_success", name="login_success") */
    public function postLoginRedirectAction(PrelaunchConfig $prelaunchConfig): Response
    {
        $this->userActionLogger->info('Log in');

        $referer = $this->session->get('login_referer');
        $refererRoute = $this->refererMappings()[$referer] ?? '';

        if ($refererRoute) {
            $this->session->remove('login_referer');

            return $this->redirectToRoute($refererRoute);
        }

        if ($referer && $this->isRefererValid($referer)) {
            return $this->redirect($referer);
        }

        return $prelaunchConfig->isFinished()
            ? $this->redirectToRoute("trading")
            : $this->redirectToRoute("referral-program");
    }

    /**
     * @param array $data
     * @return Response
     */
    protected function renderLogin(array $data): Response
    {
        return $this->render('@FOSUser/Security/login.html.twig', array_merge($data, [
            'form' => $this->form->createView(),
        ]));
    }

    private function refererMappings(): array
    {
        return [
            $this->generateUrl('nelmio_api_doc.swagger_ui', [], UrlGeneratorInterface::ABSOLUTE_URL) => 'settings',
        ];
    }

    private function isRefererValid(string $pathInfo): bool
    {
        $refererRequest = Request::create($pathInfo);
        $router = $this->get('router');
        $pathData = $router->match($refererRequest->getPathInfo());
        $routeName = $pathData['_route'] ?? null;

        return in_array($routeName, self::ALLOWED_REFERER_ROUTES);
    }
}
