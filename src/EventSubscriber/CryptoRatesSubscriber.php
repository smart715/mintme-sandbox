<?php declare(strict_types = 1);

namespace App\EventSubscriber;

use App\Events\CryptoRatesRefreshedEvent;
use App\Manager\CryptoManagerInterface;
use App\Utils\Symbols;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CryptoRatesSubscriber implements EventSubscriberInterface
{
    private CryptoManagerInterface $cryptoManager;
    private EntityManagerInterface $entityManager;

    public function __construct(
        CryptoManagerInterface $cryptoManager,
        EntityManagerInterface $entityManager
    ) {
        $this->cryptoManager = $cryptoManager;
        $this->entityManager = $entityManager;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CryptoRatesRefreshedEvent::NAME => 'onCryptoRatesRefreshed',
        ];
    }

    public function onCryptoRatesRefreshed(CryptoRatesRefreshedEvent $event): void
    {
        $rates = $event->getRates();

        $cryptos = $this->cryptoManager->findAll();

        foreach ($cryptos as $crypto) {
            $symbol = $crypto->getSymbol();

            if (isset($rates[$symbol][Symbols::USD])) {
                $rate = $rates[$symbol][Symbols::USD];
                $crypto->setUsdExchangeRate($rate);
            }
        }

        $this->entityManager->flush();
    }
}
