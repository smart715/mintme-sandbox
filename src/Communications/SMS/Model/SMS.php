<?php declare(strict_types = 1);

namespace App\Communications\SMS\Model;

class SMS
{
    private string $from;
    private string $to;
    private string $content;

    public function __construct(string $from, string $to, string $content)
    {
        $this->from = $from;
        $this->to = $to;
        $this->content = $content;
    }

    public function getFrom(): string
    {
        return $this->from;
    }

    public function getTo(): string
    {
        return $this->to;
    }

    public function getContent(): string
    {
        return $this->content;
    }
}
