<?php declare(strict_types = 1);

namespace App\Exchange\Donation;

use App\Communications\Exception\FetchException;
use App\Communications\JsonRpcInterface;
use App\Communications\JsonRpcResponse;

class DonationFetcher implements DonationFetcherInterface
{
    private const CHECK_DONATION_METHOD = 'order.check_donation';
    private const MAKE_DONATION_METHOD = 'order.make_donation';

    /** @var JsonRpcInterface */
    private $jsonRpc;

    public function __construct(JsonRpcInterface $jsonRpc)
    {
        $this->jsonRpc = $jsonRpc;
    }

    public function checkDonation(string $marketName, string $amount, string $fee, int $tokenCreatorId): array
    {
        $response = $this->jsonRpc->send(self::CHECK_DONATION_METHOD, [
            $marketName,
            $amount,
            $fee,
        ]);

        $this->checkResponseForError($response);

        return $response->getResult();
    }

    public function makeDonation(
        string $marketName,
        string $amount,
        string $fee,
        string $expectedAmount,
        int $tokenCreatorId
    ): void {
        $response = $this->jsonRpc->send(self::MAKE_DONATION_METHOD, [
            $marketName,
            $amount,
            $fee,
            $expectedAmount,
        ]);

        $this->checkResponseForError($response);
    }

    private function checkResponseForError(JsonRpcResponse $response): void
    {
        if ($response->hasError()) {
            throw new FetchException($response->getError()['message'] ?? '');
        }
    }
}
