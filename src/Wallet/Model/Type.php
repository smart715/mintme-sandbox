<?php declare(strict_types = 1);

namespace App\Wallet\Model;

use App\Wallet\Model\Exception\TypeException;
use Symfony\Component\Serializer\Annotation\Groups;

/** @codeCoverageIgnore */
class Type
{
    public const DEPOSIT = 'deposit';
    public const WITHDRAW = 'withdraw';

    // new gateway only
    public const WITHDRAWAL = 'withdrawal';

    /** @var string[] */
    protected static $available = [
        self::DEPOSIT, self::WITHDRAW,
    ];

    /** @var string */
    private $type;

    private function __construct(string $type)
    {
        $this->type = $type;
    }

    public static function fromString(string $type): self
    {
        // new gateway support
        if (self::WITHDRAWAL === $type) {
            return new self(self::WITHDRAW);
        }

        if (in_array($type, self::$available)) {
            return new self($type);
        }

        throw new TypeException(
            'Undefined type code. Expected "' . implode(', ', self::$available) . '". Got "' . $type .'".'
        );
    }

    /** @Groups({"API", "dev"}) */
    public function getTypeCode(): string
    {
        return $this->type;
    }
}
