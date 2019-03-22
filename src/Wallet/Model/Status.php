<?php declare(strict_types = 1);

namespace App\Wallet\Model;

use App\Wallet\Model\Exception\StatusException;
use Symfony\Component\Serializer\Annotation\Groups;

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

    /** @Groups({"API"}) */
    public function getStatusCode(): string
    {
        return $this->status;
    }
}
