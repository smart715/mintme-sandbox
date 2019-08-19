<?php declare(strict_types = 1);

namespace App\Manager\Model;

class EmailAuthResultModel
{
    /** @var string|null */
    private $message;

    /** @var bool */
    private $result;

    public function __construct(?string $message)
    {
        $this->setMessage($message);
    }

    public function getMessage(): string
    {
        return $this->message ?? '';
    }

    public function setMessage(?string $message): self
    {
        $this->result = null === $message;
        $this->message = $message;

        return $this;
    }

    public function getResult(): bool
    {
        return $this->result;
    }
}
