<?php declare(strict_types = 1);

namespace App\Exchange\Trade;

use Exception;
use phpDocumentor\Reflection\Types\Self_;
use Symfony\Contracts\Translation\TranslatorInterface;

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
            'place_order.created',

        self::FAILED =>
            'place_order.failed',

        self::INSUFFICIENT_BALANCE =>
            'place_order.insufficient_balance',

        self::ORDER_NOT_FOUND =>
            'place_order.not_found',

        self::USER_NOT_MATCH =>
            'place_order.not_match',

        self::SMALL_AMOUNT =>[
            'message' =>'place_order.too_small',
            'params' =>[
                '%valueInUsd%' => 0.1,
                '%valueInMintme%' => 34.4,
            ],
        ],
    ];

    /** @var int */
    private $result;

    /** @var TranslatorInterface */
    private $translator;

    public function __construct(int $result, TranslatorInterface $translator)
    {
        if (!in_array($result, array_keys(self::MESSAGES))) {
            throw new Exception('Undefined error message');
        }

        $this->result = $result;
        $this->translator = $translator;
    }

    public function getMessage(): string
    {
        if (self::SMALL_AMOUNT === $this->result) {
            return $this->translator->trans(
                self::MESSAGES[$this->result]['message'],
                self::MESSAGES[$this->result]['params']
            );
        }

        return $this->translator->trans(self::MESSAGES[$this->result]);
    }

    public function getResult(): int
    {
        return $this->result;
    }
}
