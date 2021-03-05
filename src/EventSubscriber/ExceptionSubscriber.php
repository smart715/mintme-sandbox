<?php declare(strict_types = 1);

namespace App\EventSubscriber;

use App\Exception\ApiExceptionInterface;
use App\Exception\NotFoundKnowledgeBaseException;
use App\Exception\NotFoundPairException;
use App\Exception\NotFoundPostException;
use App\Exception\NotFoundProfileException;
use App\Exception\NotFoundTokenException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

/** @codeCoverageIgnore */
class ExceptionSubscriber implements EventSubscriberInterface
{
    private Environment $template;
    private TranslatorInterface $translator;

    public function __construct(Environment $environment, TranslatorInterface $translator)
    {
        $this->template = $environment;
        $this->translator = $translator;
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

        if ($exception instanceof NotFoundPairException) {
            $event->setResponse(new Response(
                $this->template->render('pages/404.html.twig', [
                    'error_message' => $this->translator->trans('404.pair'),
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

        if ($exception instanceof MethodNotAllowedHttpException) {
            $event->setResponse(new Response(
                $this->template->render('bundles/TwigBundle/Exception/error404.html.twig'),
                404
            ));
        }

        if ($event->getException() instanceof ApiExceptionInterface) {
            /** @var ApiExceptionInterface $e */
            $e = $event->getException();
            $response = new JsonResponse(
                $e->getData(),
                $e->getStatusCode()
            );
            $response->headers->set('Content-Type', 'application/problem+json');
            $event->setResponse($response);
        }
    }
}
