<?php declare(strict_types = 1);

namespace App\Exchange\Trade;

use App\Services\TranslatorService\TranslatorInterface;

/** @codeCoverageIgnore */
class PlaceOrderResult extends TradeResult
{
    private ?int $id;
    private ?string $left;
    private ?string $amount;

    public function __construct(
        int $result,
        ?int $id,
        ?string $left,
        ?string $amount,
        TranslatorInterface $translator,
        ?string $translatedMessage = null
    ) {
        $this->id = $id;
        $this->left = $left;
        $this->amount = $amount;

        parent::__construct($result, $translator, $translatedMessage);
    }

    public function getLeft(): ?string
    {
        return $this->left;
    }

    public function getAmount(): ?string
    {
        return $this->amount;
    }

    public function getId(): ?int
    {
        return $this->id;
    }
}
