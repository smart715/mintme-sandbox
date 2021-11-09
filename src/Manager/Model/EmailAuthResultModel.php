<?php declare(strict_types = 1);

namespace App\Manager\Model;

class EmailAuthResultModel
{
    /** @var string|null */
    private $message;

    /** @var bool */
    private $result;

    public function __construct(bool $result, ?string $message)
    {
        $this->setMessage($message);
        $this->setResult($result);
    }

    public function getMessage(): string
    {
        return $this->message ?? '';
    }

    public function setMessage(?string $message): self
    {
        $this->message = $message;

        return $this;
    }

    public function getResult(): bool
    {
        return $this->result;
    }

    public function setResult(bool $result): self
    {
        $this->result = $result;

        return $this;
    }
}
