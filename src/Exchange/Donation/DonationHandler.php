<?php declare(strict_types = 1);

namespace App\Exchange\Donation;

use App\Communications\Exception\FetchException;
use App\Communications\JsonRpcInterface;
use App\Exchange\Market;
use App\Utils\Converter\MarketNameConverterInterface;

class DonationHandler implements DonationHandlerInterface
{
    private const CHECK_DONATION_METHOD = 'order.check_donation';
    private const MAKE_DONATION_METHOD = 'order.make_donation';

    /** @var JsonRpcInterface */
    private $jsonRpc;

    /** @var MarketNameConverterInterface */
    private $marketNameConverter;

    public function __construct(
        JsonRpcInterface $jsonRpc,
        MarketNameConverterInterface $marketNameConverter
    ) {
        $this->jsonRpc = $jsonRpc;
        $this->marketNameConverter = $marketNameConverter;
    }

    public function checkDonation(Market $market, string $amount, string $fee): string
    {
        $response = $this->jsonRpc->send(self::CHECK_DONATION_METHOD, [
            $this->marketNameConverter->convert($market),
            $amount,
            $fee,
        ]);

        if ($response->hasError()) {
            throw new FetchException($response->getError()['message'] ?? '');
        }

        return $response->getResult();
    }

    public function makeDonation(Market $market, string $amount, string $fee, string $expectedAmount): void
    {
        $response = $this->jsonRpc->send(self::MAKE_DONATION_METHOD, [
            $this->marketNameConverter->convert($market),
            $amount,
            $fee,
            $expectedAmount,
        ]);

        if ($response->hasError()) {
            throw new FetchException($response->getError()['message'] ?? '');
        }
    }
}
