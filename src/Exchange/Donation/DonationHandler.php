<?php declare(strict_types = 1);

namespace App\Exchange\Donation;

use App\Communications\Exception\FetchException;
use App\Communications\JsonRpcInterface;

class DonationHandler implements DonationHandlerInterface
{
    private const CHECK_DONATION_METHOD = 'order.check_donation';
    private const MAKE_DONATION_METHOD = 'order.make_donation';

    /** @var JsonRpcInterface */
    private $jsonRpc;

    public function __construct(JsonRpcInterface $jsonRpc)
    {
        $this->jsonRpc = $jsonRpc;
    }

    public function checkDonation(string $market, string $amount, string $fee): string
    {
        $response = $this->jsonRpc->send(self::CHECK_DONATION_METHOD, [
            $market,
            $amount,
            $fee,
        ]);

        if ($response->hasError()) {
            throw new FetchException($response->getError()['message'] ?? '');
        }

        return $response->getResult();
    }

    public function makeDonation(string $market, string $amount, string $fee, string $expectedAmount): void
    {
        $response = $this->jsonRpc->send(self::MAKE_DONATION_METHOD, [
            $market,
            $amount,
            $fee,
            $expectedAmount,
        ]);

        if ($response->hasError()) {
            throw new FetchException($response->getError()['message'] ?? '');
        }
    }
}
