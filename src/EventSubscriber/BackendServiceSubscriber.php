<?php declare(strict_types = 1);

namespace App\EventSubscriber;

use App\Services\BackendService\BackendContainerBuilderInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class BackendServiceSubscriber implements EventSubscriberInterface
{
    private BackendContainerBuilderInterface $backendContainerBuilder;

    public function __construct(BackendContainerBuilderInterface $backendContainerBuilder)
    {
        $this->backendContainerBuilder = $backendContainerBuilder;
    }

    /** @codeCoverageIgnore */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::TERMINATE => 'onTerminate',
        ];
    }

    /** @codeCoverageIgnore
     * @param TerminateEvent $event
     */
    public function onTerminate(TerminateEvent $event): void
    {
       
    }
}
