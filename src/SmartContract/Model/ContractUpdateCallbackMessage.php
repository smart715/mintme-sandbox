<?php declare(strict_types = 1);

namespace App\SmartContract\Model;

/** @codeCoverageIgnore */
class ContractUpdateCallbackMessage
{
    /** @var string */
    private $method;

    /** @var string[] */
    private $message;

    private function __construct(
        string $method,
        array $message
    ) {
        $this->method = $method;
        $this->message = $message;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getMessage(): array
    {
        return $this->message;
    }

    public static function parse(array $data): self
    {
        return new self(
            $data['method'],
            $data['message']
        );
    }

    public function toArray(): array
    {
        return [
            'method' => $this->getMethod(),
            'message' => $this->getMessage(),
        ];
    }
}
