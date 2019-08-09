<?php declare(strict_types = 1);

namespace App\EventSubscriber;

use App\Exception\ApiExceptionInterface;
use App\Exception\NotFoundKnowledgeBaseException;
use App\Exception\NotFoundPairException;
use App\Exception\NotFoundProfileException;
use App\Exception\NotFoundTokenException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Twig\Environment;

/** @codeCoverageIgnore */
class ExceptionSubscriber implements EventSubscriberInterface
{
    /** @var Environment */
    private $template;

    public function __construct(Environment $environment)
    {
        $this->template = $environment;
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
                    'error_message' => 'PAIR NOT FOUND',
                ]),
                404
            ));
        }

        if ($exception instanceof NotFoundTokenException) {
            $event->setResponse(new Response(
                $this->template->render('pages/404.html.twig', [
                    'error_message' => 'TOKEN NOT FOUND',
                ]),
                404
            ));
        }

        if ($exception instanceof NotFoundProfileException) {
            $event->setResponse(new Response(
                $this->template->render('pages/404.html.twig', [
                    'error_message' => 'PROFILE DOES NOT EXIST',
                ]),
                404
            ));
        }

        if ($exception instanceof NotFoundKnowledgeBaseException) {
            $event->setResponse(new Response(
                $this->template->render('pages/404.html.twig', [
                    'error_message' => 'ARTICLE NOT FOUND',
                ]),
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
