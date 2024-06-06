<?php declare(strict_types = 1);

namespace App\EventSubscriber;

use App\Exception\ApiBadRequestException;
use App\Exception\ApiExceptionInterface;
use App\Exception\ForbiddenException;
use App\Exception\NotFoundAirdropException;
use App\Exception\NotFoundKnowledgeBaseException;
use App\Exception\NotFoundPairException;
use App\Exception\NotFoundPostException;
use App\Exception\NotFoundProfileException;
use App\Exception\NotFoundRewardException;
use App\Exception\NotFoundTokenException;
use App\Exception\NotFoundVotingException;
use App\Exception\RedirectException;
use App\Exception\UnauthorizedPostException;
use App\Services\TranslatorService\TranslatorInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Twig\Environment;

/** @codeCoverageIgnore */
class ExceptionSubscriber implements EventSubscriberInterface
{
    private Environment $template;
    private TranslatorInterface $translator;
    private AuthorizationCheckerInterface $authorizationChecker;
    private UrlGeneratorInterface $route;

    public function __construct(
        Environment $environment,
        TranslatorInterface $translator,
        AuthorizationCheckerInterface $authorizationChecker,
        UrlGeneratorInterface $route
    ) {
        $this->template = $environment;
        $this->translator = $translator;
        $this->authorizationChecker = $authorizationChecker;
        $this->route = $route;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onException',
        ];
    }

    public function onException(GetResponseForExceptionEvent $event): void
    {
        $exception = $event->getException();

        if ($exception instanceof ForbiddenException) {
            $event->setResponse(new Response(
                $this->template->render('pages/403.html.twig', [
                    'error_message' => $exception->getMessage(),
                ]),
                403
            ));
        }

        if ($exception instanceof NotFoundAirdropException) {
            $event->setResponse(new Response(
                $this->template->render('pages/404.html.twig', [
                    'error_message' => $this->translator->trans('404.airdrop'),
                ]),
                404
            ));
        }

        if ($exception instanceof NotFoundPairException) {
            $event->setResponse(new Response(
                $this->template->render('pages/404.html.twig', [
                    'error_message' => $this->translator->trans('404.pair'),
                ]),
                404
            ));
        }

        if ($exception instanceof NotFoundVotingException) {
            $event->setResponse(new Response(
                $this->template->render('pages/404.html.twig', [
                    'error_message' => $this->translator->trans('404.voting'),
                ]),
                404
            ));
        }

        if ($exception instanceof NotFoundTokenException) {
            $event->setResponse(new Response(
                $this->template->render('pages/404.html.twig', [
                    'error_message' => $this->translator->trans('404.token'),
                ]),
                404
            ));
        }

        if ($exception instanceof NotFoundProfileException) {
            $event->setResponse(new Response(
                $this->template->render('pages/404.html.twig', [
                    'error_message' => $this->translator->trans('404.profile'),
                ]),
                404
            ));
        }

        if ($exception instanceof NotFoundKnowledgeBaseException) {
            $event->setResponse(new Response(
                $this->template->render('pages/404.html.twig', [
                    'error_message' => $this->translator->trans('404.article'),
                ]),
                404
            ));
        }

        if ($exception instanceof NotFoundPostException ||
            'Unable to find the post' === $exception->getMessage()
        ) {
            $event->setResponse(new Response(
                $this->template->render('pages/404.html.twig', [
                    'error_message' => $this->translator->trans('404.post'),
                ]),
                404
            ));
        }

        if ($exception instanceof UnauthorizedPostException) {
            $event->setResponse(new Response(
                $this->template->render('pages/401.html.twig', [
                    'error_message' => $this->translator->trans('401.post'),
                ]),
                401
            ));
        }

        if ($exception instanceof NotFoundRewardException) {
            $event->setResponse(new Response(
                $this->template->render('pages/404.html.twig', [
                    'error_message' => $this->translator->trans('404.reward'),
                ]),
                404
            ));
        }

        if ($exception instanceof MethodNotAllowedHttpException) {
            $uriPath = $event->getRequest()->getUri();

            if (preg_match("[/api/]", $uriPath)) {
                throw new ApiBadRequestException('Method Not Allowed');
            }

            $event->setResponse(new Response(
                $this->template->render('bundles/TwigBundle/Exception/error404.html.twig'),
                404
            ));
        }

        if ($exception instanceof ApiExceptionInterface) {
            $response = new JsonResponse(
                $exception->getData(),
                $exception->getStatusCode()
            );
            $response->headers->set('Content-Type', 'application/problem+json');
            $event->setResponse($response);
        }

        if ($exception instanceof RedirectException) {
            $event->setResponse($exception->getResponse());
        }

        $requestUri = $event->getRequest()->getRequestUri();

        // #7307 Redirect to hompepage when visiting /2fa and /admin-r8bn
        if ($exception instanceof AccessDeniedHttpException &&
            $this->authorizationChecker->isGranted('ROLE_USER') &&
            str_contains($requestUri, '/2fa') || str_contains($requestUri, '/admin-r8bn')
        ) {
            $homePage = $this->route->generate('homepage');
            $event->setResponse(new RedirectResponse($homePage));
        }

        if ($exception instanceof UnauthorizedHttpException) {
            $response = new JsonResponse(
                ['message' => $exception->getMessage()],
                $exception->getStatusCode()
            );
            $response->headers->add($exception->getHeaders());
            $event->setResponse($response);
        }
    }
}
