<?php declare(strict_types = 1);

namespace App\Utils\Validator;

class AirdropReferralCodeHashValidator implements ValidatorInterface
{
    private string $hash;
    private string $message = ''; // phpcs:ignore

    public function __construct(string $hash)
    {
        $this->hash = $hash;
    }

    public function validate(): bool
    {
        $length = strlen($this->hash);

        if ($length < 1 || $length > 11) {
            $this->message = 'Hash length must be more than 0 and less than or equal to 11';

            return false;
        }

        if (!boolval(preg_match('/^[a-zA-Z0-9-_]*$/', $this->hash))) {
            $this->message = 'Hash must contain only characters in the url safe base64 alphabet';

            return false;
        }

        return true;
    }

    public function getMessage(): string
    {
        return $this->message;
    }
}
