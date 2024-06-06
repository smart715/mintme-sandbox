<?php declare(strict_types = 1);

namespace App\Communications;

use App\Events\CryptoRatesRefreshedEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class ObservableCryptoRatesFetcher implements CryptoRatesFetcherInterface
{
    private CryptoRatesFetcherInterface $cryptoRatesFetcher;
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        CryptoRatesFetcherInterface $cryptoRatesFetcher,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->cryptoRatesFetcher = $cryptoRatesFetcher;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function fetch(): array
    {
        $result = $this->cryptoRatesFetcher->fetch();

        /** @psalm-suppress TooManyArguments */
        $this->eventDispatcher->dispatch(new CryptoRatesRefreshedEvent($result), CryptoRatesRefreshedEvent::NAME);

        return $result;
    }
}
