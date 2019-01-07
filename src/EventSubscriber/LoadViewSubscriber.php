<?php

namespace App\EventSubscriber;

use App\Communications\Exception\FetchException;
use App\Communications\JsonRpcInterface;
use App\Entity\User;
use App\Exchange\Trade\Config\PrelaunchConfig;
use App\Manager\ProfileManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class LoadViewSubscriber implements EventSubscriberInterface
{
    /** @var Session */
    private $session;
   
    /** @var JsonRpcInterface */
    private $jsonRpc;
    
    /** @var PrelaunchConfig */
    private $prelaunch;
    
    private const SERVICE_UNAVAILABLE = 3;
    private const SERVICE_TIMEOUT = 5;
    private const TEST_METHOD = 'market.list';
    
    public function __construct(Session $session, JsonRpcInterface $jsonRpc, PrelaunchConfig $prelaunch)
    {
        $this->session = $session;
        $this->jsonRpc = $jsonRpc;
        $this->prelaunch = $prelaunch;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE  => 'checkViaBtc',
        ];
    }
    
    public function checkViaBtc(FilterResponseEvent $event): void
    {
        if (!$this->isConnect() && $this->prelaunch->isFinished()) {
            $message = 'It seems that some services are down, trading will be available soon. Please try again later.';
            if (!in_array($message, $this->session->getFlashBag()->get('warning', array()))) {
                $this->session->getFlashBag()->add('warning', $message);
            }
        }
    }
    
    private function isConnect(): bool
    {
        try {
            $response = $this->jsonRpc->send(self::TEST_METHOD, [null]);
        } catch (FetchException $e) {
            return false;
        }

        if ($response->hasError()) {
            if (self::SERVICE_TIMEOUT === intval($response->getError()['code']) ||
               self::SERVICE_UNAVAILABLE === intval($response->getError()['code'])
            ) {
                return false;
            }
        }
        
        return true;
    }
}
