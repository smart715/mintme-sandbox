<template>
    <div class="d-flex">
        <a
            v-if="currentWebsite"
            :href="currentWebsite"
            class="col-auto d-flex text-white rounded-circle justify-content-center socialmedia p-0 mx-1"
            target="_blank"
        >
            <font-awesome-icon icon="globe" size="lg" />
        </a>
        <a
            v-if="currentYoutube"
            :href="currentYoutube"
            class="col-auto d-flex text-white rounded-circle justify-content-center socialmedia p-0 mx-1"
            target="_blank"
        >
            <font-awesome-icon :icon="['fab', 'youtube']" size="lg" />
        </a>
        <a
            v-if="currentFacebook"
            :href="currentFacebook"
            class="col-auto d-flex text-white rounded-circle justify-content-center socialmedia p-0 mx-1"
            target="_blank"
        >
            <font-awesome-icon :icon="['fab', 'facebook']" size="lg" />
        </a>
        <a
            v-if="currentDiscord"
            :href="currentDiscord"
            class="col-auto d-flex text-white rounded-circle justify-content-center socialmedia p-0 mx-1"
            target="_blank"
        >
            <font-awesome-icon :icon="['fab', 'discord']" size="lg" />
        </a>
        <a
            v-if="currentTelegram"
            :href="currentTelegram"
            class="col-auto d-flex text-white rounded-circle justify-content-center socialmedia p-0 mx-1"
            target="_blank"
        >
            <font-awesome-icon :icon="['fab', 'telegram']" size="lg" />
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
                        <font-awesome-icon icon="envelope" /> Email
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
                        <font-awesome-icon :icon="['fab', 'facebook']" /> Facebook
                    </network>
                    <network class="d-block c-pointer" network="linkedin">
                        <font-awesome-icon :icon="['fab', 'linkedin']" /> LinkedIn
                    </network>
                    <network class="d-block c-pointer" network="reddit">
                        <font-awesome-icon :icon="['fab', 'reddit']" /> Reddit
                    </network>
                    <network class="d-block c-pointer" network="telegram">
                        <font-awesome-icon :icon="['fab', 'telegram']" /> Telegram
                    </network>
                    <network class="d-block c-pointer" network="twitter">
                        <font-awesome-icon :icon="['fab', 'twitter']" /> Twitter
                    </network>
                </div>
            </social-sharing>
        </b-dropdown>
    </div>
</template>

<script>
import TokenDiscordChannel from '../TokenDiscordChannel';
import TokenFacebookAddressView from '../facebook/TokenFacebookAddressView';
import TokenTelegramChannel from '../TokenTelegramChannel';
import TokenWebsiteAddressView from '../website/TokenWebsiteAddressView';
import TokenYoutubeAddressView from '../youtube/TokenYoutubeAddressView';
import {library} from '@fortawesome/fontawesome-svg-core';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import Guide from '../../Guide';

let SocialSharing = require('vue-social-sharing');

Vue.use(SocialSharing);

export default {
    name: 'TokenIntroductionProfile',
    props: {
        discordUrl: String,
        facebookUrl: String,
        facebookAppId: String,
        telegramUrl: String,
        tokenName: String,
        websiteUrl: String,
        youtubeClientId: String,
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
            currentDiscord: this.discordUrl,
            currentFacebook: this.facebookUrl,
            currentTelegram: this.telegramUrl,
            currentWebsite: this.websiteUrl,
            currentYoutube: this.youtubeChannelId,
            tokenUrl: this.$routing.generate('token_show', {
                name: this.tokenName,
                tab: 'intro',
            }),
            twitterDescription: 'A great way for mutual support. Check this token and see how the idea evolves: ',
        };
    },
    computed: {
        description: function() {
           return this.twitterDescription + this.tokenUrl;
        },
    },
};
</script>
