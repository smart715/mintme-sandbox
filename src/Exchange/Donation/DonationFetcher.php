<?php declare(strict_types = 1);

namespace App\Exchange\Donation;

use App\Communications\Exception\FetchException;
use App\Communications\JsonRpcInterface;
use App\Communications\JsonRpcResponse;
use App\Exchange\Config\Config;
use App\Exchange\Donation\Model\CheckDonationRawResult;

class DonationFetcher implements DonationFetcherInterface
{
    private const CHECK_DONATION_METHOD = 'order.check_donation';
    private const MAKE_DONATION_METHOD = 'order.make_donation';

    /** @var JsonRpcInterface */
    private $jsonRpc;

    /** @var Config */
    private $config;

    public function __construct(JsonRpcInterface $jsonRpc, Config $config)
    {
        $this->jsonRpc = $jsonRpc;
        $this->config = $config;
    }

    public function checkDonation(
        string $marketName,
        string $amount,
        string $fee,
        int $tokenCreatorId
    ): CheckDonationRawResult {
        $response = $this->jsonRpc->send(self::CHECK_DONATION_METHOD, [
            $marketName,
            $amount,
            $fee,
            $tokenCreatorId + $this->config->getOffset(),
        ]);

        $this->checkResponseForError($response);
        /** @var array<string> $result */
        $result = $response->getResult();

        return new CheckDonationRawResult(
            $result[0] ?? '0',
            $result[1] ?? '0'
        );
    }

    public function makeDonation(
        int $donorUserId,
        string $marketName,
        string $amount,
        string $fee,
        string $expectedAmount,
        int $tokenCreatorId
    ): void {
        $response = $this->jsonRpc->send(self::MAKE_DONATION_METHOD, [
            $donorUserId + $this->config->getOffset(),
            $marketName,
            $amount,
            $fee,
            $expectedAmount,
            $tokenCreatorId + $this->config->getOffset(),
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
