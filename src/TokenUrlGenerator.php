<?php declare(strict_types = 1);

namespace App;

use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouterInterface;

class TokenUrlGenerator implements RouterInterface
{
    /** @var RouterInterface */
    private $router;

    /** @var Array */
    private $arr = [
       'add_new_reward' => 'tokenName',
       'claim_airdrop_campaign' => 'tokenName',
       'claim_fb_message_post' => 'tokenName',
       'claim_page_visit' => 'tokenName',
       'claim_post_link' => 'tokenName',
       'claim_retweet' => 'tokenName',
       'claim_share_linkedin' => 'tokenName',
       'claim_share_twitter' => 'tokenName',
       'claim_subscribe_youtube' => 'tokenName',
       'create_airdrop_campaign' => 'tokenName',
       'create_post' => 'tokenName',
       'get_airdrop_campaign' => 'tokenName',
       'get_airdrop_completed_actions' => 'tokenName',
       'get_discord_info' => 'tokenName',
       'list_posts' => 'tokenName',
       'list_voting' => 'tokenName',
       'manage_discord_roles' => 'tokenName',
       'new_dm_message' => 'tokenName',
       'remove_guild' => 'tokenName',
       'store_voting' => 'tokenName',
       'token_follow' => 'tokenName',
       'token_unfollow' => 'tokenName',
       'token_update_deployed_modal' => 'tokenName',
       'update_discord_roles' => 'tokenName',
        'airdrop_referral' => 'tokenName',

       'airdrop_embeded' => 'name',
       'check_token_name_exists' => 'name',
       'is_token_exchanged' => 'name',
       'is_unique_token_name' => 'name',
       'lock_in' => 'name',
       'lock-period' => 'name',
       'token_show_post' => 'name',
       'token_balance' => 'name',
       'token_contract_update' => 'name',
       'token_create_voting' => 'name',
       'token_delete' => 'name',
       'token_deploy' => 'name',
       'token_deployment_status' => 'name',
       'token_deploys' => 'name',
       'token_exchange_amount' => 'name',
       'token_list_voting' => 'name',
       'token_name_blacklist_check' => 'name',
       'token_over_delete_limit' => 'name',
       'token_send_code' => 'name',
       'token_show_intro' => 'name',
       'token_show_trade' => 'name',
       'token_show_voting' => 'name',
       'token_sold_on_market' => 'name',
       'token_update' => 'name',
       'token_wallet_delete' => 'name',
       'token_website_confirm' => 'name',
       'token_website_confirmation' => 'name',
       'token_withdrawn' => 'name',
       'top_holders' => 'name',
       'view_only_token' => 'name',

       'register-referral-by-token' => 'userToken',
    ];

    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    /**
     * @inheritdoc
     */
    public function generate($name, $parameters = [], $referenceType = self::ABSOLUTE_PATH)
    {
        $attr = $this->arr[$name] ?? '';

        if ($attr && $parameters[$attr]) {
            $parameters[$attr] = str_replace(' ', '-', $parameters[$attr]);
        }

        return $this->router->generate($name, $parameters, $referenceType);
    }

    public function setContext(RequestContext $context): void
    {
        $this->router->setContext($context);
    }

    /**
     * @inheritdoc
     */
    public function getContext()
    {
        return $this->router->getContext();
    }

    /**
     * @inheritdoc
     */
    public function getRouteCollection()
    {
        return $this->router->getRouteCollection();
    }

    /**
     * @inheritdoc
     */
    public function match($pathinfo)
    {
        return $this->router->match($pathinfo);
    }
}
