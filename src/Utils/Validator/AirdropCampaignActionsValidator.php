<?php declare(strict_types = 1);

namespace App\Utils\Validator;

use App\Entity\Token\Token;
use App\Utils\AirdropCampaignActions;

class AirdropCampaignActionsValidator implements ValidatorInterface
{
    private ?array $actions;

    private array $actionsData;

    private string $message;

    private Token $token;

    public function __construct(?array $actions, array &$actionsData, Token $token)
    {
        $this->actions = $actions;
        $this->actionsData = $actionsData;
        $this->token = $token;
        $this->message = 'airdrop_backend.invalid_actions';
    }

    public function validate(): bool
    {
        if (null === $this->actions) {
            return false;
        }

        // We use it to check if at least one action is true and also is url is valid
        $isValid = false;

        foreach ($this->actions as $action => $active) {
            if (!AirdropCampaignActions::isValid($action)) {
                return false;
            }

            if (!is_bool($active)) {
                return false;
            }

            if ($active) {
                // At least one action must be true
                $isValid = true;

                // The value of isValid might be changed to false depend
                // of URL validation or present of URL in user profile
                $actionData = $this->checkAction($action, $this->actionsData[$action] ?? null, $isValid);

                if (!$isValid) {
                    return false;
                }

                $this->actionsData[$action] = $actionData;
            }
        }

        return $isValid;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    private function checkAction(string $action, ?string $actionData, bool &$isValid): ?string
    {
        switch ($action) {
            case AirdropCampaignActions::TWITTER_RETWEET:
                $twitterRetweetUrlValidator = new TwitterRetweetUrlValidator($actionData);

                if (!$twitterRetweetUrlValidator->validate()) {
                    $this->message = $twitterRetweetUrlValidator->getMessage();
                    $isValid = false;
                }

                break;
            case AirdropCampaignActions::FACEBOOK_POST:
                $facebookPostUrlValidator = new FacebookPostUrlValidator($actionData);

                if (!$facebookPostUrlValidator->validate()) {
                    $this->message = $facebookPostUrlValidator->getMessage();
                    $isValid = false;
                }

                break;
            case AirdropCampaignActions::FACEBOOK_PAGE:
                $actionData = $this->token->getFacebookUrl();

                if (null === $actionData) {
                    $this->message = 'airdrop_backend.invalid_facebook_page';
                    $isValid = false;
                }

                break;
            case AirdropCampaignActions::YOUTUBE_SUBSCRIBE:
                $actionData = $this->token->getYoutubeChannelId();

                if (null === $actionData) {
                    $this->message = 'airdrop_backend.invalid_youtube_channel';
                    $isValid = false;
                }

                break;
        }

        return $actionData;
    }
}
