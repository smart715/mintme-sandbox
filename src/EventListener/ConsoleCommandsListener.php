<?php declare(strict_types = 1);

namespace App\EventListener;

use App\Exchange\Factory\MarketFactoryInterface;
use App\Manager\MarketStatusManagerInterface;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;

class ConsoleCommandsListener
{
    private const DOCTRINE_MIGRATION = 'doctrine:migrations:migrate';

    /** @var MarketFactoryInterface */
    protected $marketFactory;

    /** @var MarketStatusManagerInterface */
    protected $statusManager;

    public function __construct(MarketFactoryInterface $marketFactory, MarketStatusManagerInterface $statusManager)
    {
        $this->marketFactory = $marketFactory;
        $this->statusManager = $statusManager;
    }

    public function onConsoleTerminate(ConsoleTerminateEvent $event): void
    {
        if (self::DOCTRINE_MIGRATION === $event->getCommand()->getName()) {
            $this->statusManager->createMarketStatus($this->marketFactory->createAll());
            $event->getOutput()->writeln('Predefined markets was migrated to market stats');
        }
    }
}
