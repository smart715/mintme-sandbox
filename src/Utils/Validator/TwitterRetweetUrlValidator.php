<?php declare(strict_types = 1);

namespace App\Utils\Validator;

class TwitterRetweetUrlValidator implements ValidatorInterface
{
    /** @var string|null */
    private ?string $twitterRetweet;

    /** @var string */
    private string $message;

    public function __construct(?string $twitterRetweet)
    {
        $this->twitterRetweet = $twitterRetweet;
        $this->message = 'airdrop_backend.invalid_twitter_url';
    }

    public function validate(): bool
    {
        if (null === $this->twitterRetweet) {
            return false;
        }

        return boolval(preg_match('/^(?:https?:\/\/)?(?:www\.)?twitter\.com\/[\S]+\/status\/([\d]+)$/', $this->twitterRetweet));
    }

    public function getMessage(): string
    {
        return $this->message;
    }
}
