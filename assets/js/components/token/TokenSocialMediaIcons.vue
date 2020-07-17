<template>
    <div class="d-flex align-items-center">
        <a
            v-if="websiteUrl"
            :href="websiteUrl"
            class="col-auto d-flex text-white rounded-circle justify-content-center socialmedia p-0 mx-1"
            target="_blank"
        >
            <font-awesome-icon icon="globe" size="lg" class="icon-default" />
        </a>
        <a
            v-if="youtubeChannelId"
            :href="youtubeUrl"
            class="col-auto d-flex text-white rounded-circle justify-content-center socialmedia p-0 mx-1"
            target="_blank"
        >
            <font-awesome-icon :icon="['fab', 'youtube']" size="lg" class="icon-default" />
        </a>
        <a
            v-if="facebookUrl"
            :href="facebookUrl"
            class="col-auto d-flex text-white rounded-circle justify-content-center socialmedia p-0 mx-1"
            target="_blank"
        >
            <font-awesome-icon :icon="['fab', 'facebook']" size="lg" class="icon-default" />
        </a>
        <a
            v-if="discordUrl"
            :href="discordUrl"
            class="col-auto d-flex text-white rounded-circle justify-content-center socialmedia p-0 mx-1"
            target="_blank"
        >
            <font-awesome-icon :icon="['fab', 'discord']" size="lg" class="icon-default" />
        </a>
        <a
            v-if="telegramUrl"
            :href="telegramUrl"
            class="col-auto d-flex text-white rounded-circle justify-content-center socialmedia p-0 mx-1"
            target="_blank"
        >
            <font-awesome-icon :icon="['fab', 'telegram']" size="lg" class="icon-default" />
        </a>
        <b-dropdown
            id="share"
            text="Share"
            variant="primary"
        >
            <social-sharing
                url=""
                title="MintMe"
                :description="description"
                inline-template
            >
                <div class="px-2">
                    <network
                        class="d-block c-pointer"
                        network="email"
                    >
                        <a href="#" class="text-white hover-icon">
                            <font-awesome-icon icon="envelope" />
                            Email
                        </a>
                    </network>
                </div>
            </social-sharing>
            <social-sharing
                :title="twitterDescription"
                :description="description"
                :quote="description"
                hashtags="Mintme,MutualSupport,Monetization,Crowdfunding,Business,Exchange,Creators,Technology,Blockchain,Trading,Token,CryptoTrading,Crypto,Voluntary"
                inline-template
            >
                <div class="px-2">
                    <network class="d-block c-pointer" network="facebook">
                        <a href="#" class="text-white hover-icon">
                            <font-awesome-icon :icon="['fab', 'facebook']"/>
                            Facebook
                        </a>
                    </network>
                    <network class="d-block c-pointer" network="linkedin">
                        <a href="#" class="text-white hover-icon">
                            <font-awesome-icon :icon="['fab', 'linkedin']"/>
                            LinkedIn
                        </a>
                    </network>
                    <network class="d-block c-pointer" network="reddit">
                        <a href="#" class="text-white hover-icon">
                            <font-awesome-icon :icon="['fab', 'reddit']"/>
                            Reddit
                        </a>
                    </network>
                    <network class="d-block c-pointer" network="telegram">
                        <a href="#" class="text-white hover-icon">
                            <font-awesome-icon :icon="['fab', 'telegram']"/>
                            Telegram
                        </a>
                    </network>
                    <network class="d-block c-pointer" network="twitter">
                        <a href="#" class="text-white hover-icon">
                            <font-awesome-icon :icon="['fab', 'twitter']"/>
                            Twitter
                        </a>
                    </network>
                </div>
            </social-sharing>
        </b-dropdown>
    </div>
</template>

<script>
import TokenDiscordChannel from './TokenDiscordChannel';
import TokenFacebookAddressView from './facebook/TokenFacebookAddressView';
import TokenTelegramChannel from './TokenTelegramChannel';
import TokenWebsiteAddressView from './website/TokenWebsiteAddressView';
import TokenYoutubeAddressView from './youtube/TokenYoutubeAddressView';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import Guide from '../Guide';

let SocialSharing = require('vue-social-sharing');

Vue.use(SocialSharing);

export default {
    name: 'TokenSocialMediaIcons',
    props: {
        discordUrl: String,
        facebookUrl: String,
        telegramUrl: String,
        tokenName: String,
        websiteUrl: String,
        youtubeChannelId: String,
    },
    components: {
        FontAwesomeIcon,
        Guide,
        TokenDiscordChannel,
        TokenFacebookAddressView,
        TokenTelegramChannel,
        TokenYoutubeAddressView,
        TokenWebsiteAddressView,
    },
    data() {
        return {
            tokenUrl: this.$routing.generate('token_show', {
                name: this.tokenName,
                tab: 'intro',
            }),
            twitterDescription: 'A great way for mutual support. Check this token and see how the idea evolves: ',
        };
    },
    computed: {
        description() {
           return this.twitterDescription + this.tokenUrl;
        },
        youtubeUrl() {
            return 'https://www.youtube.com/channel/' + this.youtubeChannelId;
        },
    },
};
</script>
