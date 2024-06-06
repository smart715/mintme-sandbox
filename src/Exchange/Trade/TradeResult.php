<?php declare(strict_types = 1);

namespace App\Exchange\Trade;

use App\Services\TranslatorService\TranslatorInterface;
use Exception;

class TradeResult
{
    public const SUCCESS = 1;
    public const FAILED = 2;
    public const INSUFFICIENT_BALANCE = 3;
    public const ORDER_NOT_FOUND = 4;
    public const USER_NOT_MATCH = 5;
    public const SMALL_AMOUNT = 11;
    public const NO_ENOUGH_TRADER = 12;

    private const MESSAGES = [
        self::SUCCESS =>
            'place_order.created',

        self::FAILED =>
            'place_order.failed',

        self::INSUFFICIENT_BALANCE =>
            'place_order.insufficient_balance',

        self::ORDER_NOT_FOUND =>
            'place_order.not_found',

        self::USER_NOT_MATCH =>
            'place_order.not_match',

        self::SMALL_AMOUNT =>
            'place_order.too_small',

        self::NO_ENOUGH_TRADER =>
            'execute_order.no_enough_trader',
    ];

    /** @var int */
    private $result;

    /** @var TranslatorInterface */
    private $translator;

    private ?string $translatedMessage;

    private ?int $id;

    public function __construct(
        int $result,
        TranslatorInterface $translator,
        ?string $translatedMessage = null,
        ?int $id = null
    ) {
        if (!array_key_exists($result, self::MESSAGES) && !$translatedMessage) {
            throw new Exception('Undefined error message');
        }

        $this->result = $result;
        $this->translator = $translator;
        $this->translatedMessage = $translatedMessage;
        $this->id = $id;
    }

    public function getMessage(): string
    {
        return $this->translatedMessage ?? $this->translator->trans(self::MESSAGES[$this->result]);
    }

    public function getResult(): int
    {
        return $this->result;
    }

    public function getId(): ?int
    {
        return $this->id;
    }
}
