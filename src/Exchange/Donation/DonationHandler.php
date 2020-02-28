<?php declare(strict_types = 1);

namespace App\Exchange\Donation;

use App\Communications\Exception\FetchException;
use App\Communications\JsonRpcInterface;
use App\Exchange\Market;
use App\Utils\Converter\MarketNameConverterInterface;
use Money\Currency;
use Money\Money;

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
        $amountObj = new Money($amount, new Currency($market->getBase()->getSymbol()));

        $response = $this->jsonRpc->send(self::CHECK_DONATION_METHOD, [
            $this->marketNameConverter->convert($market),
            $amountObj->getAmount(),
            $fee,
        ]);

        if ($response->hasError()) {
            throw new FetchException($response->getError()['message'] ?? '');
        }

        return $response->getResult();
    }

    public function makeDonation(Market $market, string $amount, string $fee, string $expectedAmount): void
    {
        $amountObj = new Money($amount, new Currency($market->getBase()->getSymbol()));

        $response = $this->jsonRpc->send(self::MAKE_DONATION_METHOD, [
            $this->marketNameConverter->convert($market),
            $amountObj->getAmount(),
            $fee,
            $expectedAmount,
        ]);

        if ($response->hasError()) {
            throw new FetchException($response->getError()['message'] ?? '');
        }
    }
}
