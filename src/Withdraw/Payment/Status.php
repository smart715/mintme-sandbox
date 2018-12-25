<?php

namespace App\Withdraw\Payment;

use App\Withdraw\Payment\Exception\StatusException;

class Status
{
    public const PAID = 'paid';
    public const PENDING = 'pending';
    public const ERROR = 'error';

    /** @var string[] */
    protected static $available = [
        self::PAID, self::PENDING, self::ERROR,
    ];

    /** @var string */
    private $status;

    private function __construct(string $status)
    {
        $this->status = $status;
    }

    public static function fromString(string $status): self
    {
        if (in_array($status, self::$available)) {
            return new self($status);
        }

        throw new StatusException(
            'Undefined status code. Expected "' . implode(', ', self::$available) . '". Got "' . $status .'".'
        );
    }

    public function getStatusCode(): string
    {
        return $this->status;
    }
}
