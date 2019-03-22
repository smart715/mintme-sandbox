<?php declare(strict_types = 1);

namespace App\Exchange\Trade;

class TradeResult
{
    public const SUCCESS = 1;
    public const FAILED = 2;
    public const INSUFFICIENT_BALANCE = 3;
    public const ORDER_NOT_FOUND = 4;
    public const USER_NOT_MATCH = 5;

    private const MESSAGES = [
        self::SUCCESS =>
            'Order has been placed.',

        self::FAILED =>
            'Order has failed. Try again later.',

        self::INSUFFICIENT_BALANCE =>
            'Order has failed because of insufficient balance',
        
        self::ORDER_NOT_FOUND =>
            'Order has not been found',

        self::USER_NOT_MATCH =>
            'You don\'t match with this order',
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
