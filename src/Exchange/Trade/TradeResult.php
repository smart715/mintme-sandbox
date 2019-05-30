<?php declare(strict_types = 1);

namespace App\Exchange\Trade;

class TradeResult
{
    public const SUCCESS = 1;
    public const FAILED = 2;
    public const INSUFFICIENT_BALANCE = 3;
    public const ORDER_NOT_FOUND = 4;
    public const USER_NOT_MATCH = 5;
    public const SMALL_AMOUNT = 11;

    private const MESSAGES = [
        self::SUCCESS =>
            'ORDER CREATED',

        self::FAILED =>
            'Order has failed. Try again later.',

        self::INSUFFICIENT_BALANCE =>
            'Insufficient Balance',

        self::ORDER_NOT_FOUND =>
            'Order has not been found',

        self::USER_NOT_MATCH =>
            'You don\'t match with this order',

        self::SMALL_AMOUNT =>
            'Amount is too small',
    ];

    /** @var int */
    private $result;

    public function __construct(int $result)
    {
        assert(in_array($result, array_keys(self::MESSAGES)));
        $this->result = $result;
    }

    public function getMessage(): string
    {
        return self::MESSAGES[$this->result];
    }

    public function getResult(): int
    {
        return $this->result;
    }
}
