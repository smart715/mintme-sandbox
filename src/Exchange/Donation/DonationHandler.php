<?php declare(strict_types = 1);

namespace App\Exchange\Donation;

use App\Communications\Exception\FetchException;
use App\Communications\JsonRpcInterface;
use App\Entity\Token\Token;
use App\Entity\TradebleInterface;
use App\Exchange\Market;
use App\Utils\Converter\MarketNameConverterInterface;
use App\Wallet\Money\MoneyWrapper;
use App\Wallet\Money\MoneyWrapperInterface;

class DonationHandler implements DonationHandlerInterface
{
    private const CHECK_DONATION_METHOD = 'order.check_donation';
    private const MAKE_DONATION_METHOD = 'order.make_donation';

    /** @var JsonRpcInterface */
    private $jsonRpc;

    /** @var MarketNameConverterInterface */
    private $marketNameConverter;

    /** @var MoneyWrapperInterface */
    private $moneyWrapper;

    public function __construct(
        JsonRpcInterface $jsonRpc,
        MarketNameConverterInterface $marketNameConverter,
        MoneyWrapperInterface $moneyWrapper
    ) {
        $this->jsonRpc = $jsonRpc;
        $this->marketNameConverter = $marketNameConverter;
        $this->moneyWrapper = $moneyWrapper;
    }

    public function checkDonation(Market $market, string $amount, string $fee): string
    {
        $amountObj = $this->moneyWrapper->parse($amount, $this->getSymbol($market->getBase()));
        $feeObj = $this->moneyWrapper->parse($fee, $this->getSymbol($market->getQuote()));

        $response = $this->jsonRpc->send(self::CHECK_DONATION_METHOD, [
            $this->marketNameConverter->convert($market),
            $amountObj->getAmount(),
            $feeObj->getAmount(),
        ]);

        if ($response->hasError()) {
            throw new FetchException($response->getError()['message'] ?? '');
        }

        return $response->getResult();
    }

    public function makeDonation(Market $market, string $amount, string $fee, string $expectedAmount): void
    {
        $amountObj = $this->moneyWrapper->parse($amount, $this->getSymbol($market->getBase()));
        $feeObj = $this->moneyWrapper->parse($fee, $this->getSymbol($market->getQuote()));

        $response = $this->jsonRpc->send(self::MAKE_DONATION_METHOD, [
            $this->marketNameConverter->convert($market),
            $amountObj->getAmount(),
            $feeObj->getAmount(),
            $expectedAmount,
        ]);

        if ($response->hasError()) {
            throw new FetchException($response->getError()['message'] ?? '');
        }
    }

    private function getSymbol(TradebleInterface $tradeble): string
    {
        return $tradeble instanceof Token
            ? MoneyWrapper::TOK_SYMBOL
            : $tradeble->getSymbol();
    }
}
