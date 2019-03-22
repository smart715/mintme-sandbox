<?php declare(strict_types = 1);

namespace App\EventSubscriber;

use App\Exception\NotFoundPairException;
use App\Exception\NotFoundProfileException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Twig\Environment;

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
                $this->template->render('pages/pair_404.html.twig'),
                404
            ));
        }

        if ($exception instanceof NotFoundProfileException) {
            $event->setResponse(new Response(
                $this->template->render('pages/profile_404.html.twig'),
                404
            ));
        }
    }
}