<?php declare(strict_types = 1);

namespace App\Wallet\Model;

use App\Wallet\Model\Exception\StatusException;
use Symfony\Component\Serializer\Annotation\Groups;

/** @codeCoverageIgnore */
class Status
{
    public const PAID = 'paid';
    public const PENDING = 'pending';
    public const DISABLED = 'disabled';
    public const ERROR = 'error';
    public const CONFIRMATION = 'confirmation';

    // new gateway
    public const FAIL = 'fail';
    public const MIN_DEPOSIT_PENDING = 'min-deposit-pending';
    public const PENDING_PROCESSING = 'pending-processing';
    public const PENDING_VERIFICATION = 'pending-verification';
    public const PENDING_MAIN_BALANCE_STATUS = 'pending-main-balance';
    public const PENDING_ENABLING_STATUS = 'pending-enabling';
    public const PAID_UNCONFIRMED_STATUS = 'paid-unconfirmed-status';

    /** @var string[] */
    protected static $available = [
        self::PAID,
        self::PENDING,
        self::MIN_DEPOSIT_PENDING,
        self::CONFIRMATION,
        self::ERROR,
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

        // new gateway support
        if (self::FAIL === $status) {
            return new self(self::ERROR);
        }

        if (in_array($status, [
            self::PENDING_PROCESSING,
            self::PENDING_VERIFICATION,
            self::PAID_UNCONFIRMED_STATUS,
            self::PENDING_MAIN_BALANCE_STATUS,
        ])) {
            return new self(self::PENDING);
        }

        if (self::PENDING_ENABLING_STATUS === $status) {
            return new self(self::DISABLED);
        }

        throw new StatusException(
            'Undefined status code. Expected "' . implode(', ', self::$available) . '". Got "' . $status .'".'
        );
    }

    /** @Groups({"API", "dev"}) */
    public function getStatusCode(): string
    {
        return $this->status;
    }
}
