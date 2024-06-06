<?php declare(strict_types = 1);

namespace App\Utils\Validator;

class FacebookPostUrlValidator implements ValidatorInterface
{
    /** @var string|null */
    private ?string $facebookPost;

    /** @var string */
    private string $message;

    public function __construct(?string $facebookPost)
    {
        $this->facebookPost = $facebookPost;
        $this->message = 'airdrop_backend.invalid_facebook_url';
    }

    public function validate(): bool
    {
        if (null === $this->facebookPost) {
            return false;
        }

        return boolval(preg_match(
            '/^(https?:\/\/)?(www\.)?facebook\.com\/[\S]+\/posts\/[A-Za-z0-9]+$/',
            $this->facebookPost
        ));
    }

    public function getMessage(): string
    {
        return $this->message;
    }
}
