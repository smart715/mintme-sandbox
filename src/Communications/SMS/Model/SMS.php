<?php declare(strict_types = 1);

namespace App\Communications\SMS\Model;

class SMS
{
    public const USA_SENT_FROM = '17076790521';
    public const USA_COUNTRY_CODE = '1';
    private string $from;
    private string $to;
    private string $content;
    private string $countryCode;

    public function __construct(string $from, string $to, string $content, string $countryCode)
    {
        $this->from = $from;
        $this->to = $to;
        $this->content = $content;
        $this->countryCode = $countryCode;
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

    public function getCountryCode(): string
    {
        return $this->countryCode;
    }
}
