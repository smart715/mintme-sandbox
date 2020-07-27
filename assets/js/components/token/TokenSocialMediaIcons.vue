<template>
    <div class="d-flex align-items-center">
        <a
            v-if="websiteUrl"
            :href="websiteUrl"
            class="col-auto d-flex text-white rounded-circle justify-content-center socialMediaIcon p-0 mx-1"
            target="_blank"
        >
            <font-awesome-icon icon="globe" size="lg" class="icon-default" />
        </a>
        <a
            v-if="youtubeChannelId"
            :href="youtubeUrl"
            class="col-auto d-flex text-white rounded-circle justify-content-center socialMediaIcon p-0 mx-1"
            target="_blank"
        >
            <font-awesome-icon :icon="['fab', 'youtube']" size="lg" class="icon-default" />
        </a>
        <a
            v-if="facebookUrl"
            :href="facebookUrl"
            class="col-auto d-flex text-white rounded-circle justify-content-center socialMediaIcon p-0 mx-1"
            target="_blank"
        >
            <font-awesome-icon :icon="['fab', 'facebook']" size="lg" class="icon-default" />
        </a>
        <a
            v-if="discordUrl"
            :href="discordUrl"
            class="col-auto d-flex text-white rounded-circle justify-content-center socialMediaIcon p-0 mx-1"
            target="_blank"
        >
            <font-awesome-icon :icon="['fab', 'discord']" size="lg" class="icon-default" />
        </a>
        <a
            v-if="telegramUrl"
            :href="telegramUrl"
            class="col-auto d-flex text-white rounded-circle justify-content-center socialMediaIcon p-0 mx-1"
            target="_blank"
        >
            <font-awesome-icon :icon="['fab', 'telegram']" size="lg" class="icon-default" />
        </a>
        <div class="dropdown" :class="{ 'show': showSocialMediaMenu }">
            <a
                class="c-pointer col-auto d-flex text-white rounded-circle justify-content-center socialMediaIcon p-0 mx-1"
                aria-haspopup="true"
                :aria-expanded="showSocialMediaMenu"
                @click="toggleSocialMediaMenu"
            >
                <font-awesome-icon :icon="['fas', 'share']" size="lg" class="icon-default" />
            </a>
           <div
               class="dropdown-menu dropdown-menu-right dropdown-menu-social-media align-self-end align-self-lg-center profile-menu"
               :class="{ 'show': showSocialMediaMenu }"
           >
               <social-sharing
                   url=""
                   title="MintMe"
                   :description="description"
                   inline-template
               >
                   <div class="px-2">
                       <network
                           class="d-block c-pointer social-link"
                           network="email"
                       >
                           <font-awesome-icon icon="envelope" />
                           <a href="#" class="text-white hover-icon text-decoration-none">
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
                       <network class="d-block c-pointer social-link" network="facebook">
                           <font-awesome-icon :icon="['fab', 'facebook']"/>
                           <a href="#" class="text-white hover-icon text-decoration-none">
                               Facebook
                           </a>
                       </network>
                       <network class="d-block c-pointer social-link" network="linkedin">
                           <font-awesome-icon :icon="['fab', 'linkedin']"/>
                           <a href="#" class="text-white hover-icon text-decoration-none">
                               LinkedIn
                           </a>
                       </network>
                       <network class="d-block c-pointer social-link" network="reddit">
                           <font-awesome-icon :icon="['fab', 'reddit']"/>
                           <a href="#" class="text-white hover-icon text-decoration-none">
                               Reddit
                           </a>
                       </network>
                       <network class="d-block c-pointer social-link" network="telegram">
                           <font-awesome-icon :icon="['fab', 'telegram']"/>
                           <a href="#" class="text-white hover-icon text-decoration-none">
                               Telegram
                           </a>
                       </network>
                       <network class="d-block c-pointer social-link" network="twitter">
                           <font-awesome-icon :icon="['fab', 'twitter']"/>
                           <a href="#" class="text-white hover-icon text-decoration-none">
                               Twitter
                           </a>
                       </network>
                   </div>
               </social-sharing>
            </div>
        </div>
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
            showSocialMediaMenu: false,
            tokenUrl: this.$routing.generate('token_show', {
                name: this.tokenName,
                tab: 'intro',
            }),
            twitterDescription: 'A great way for mutual support. Check this token and see how the idea evolves: ',
        };
    },
    methods: {
        toggleSocialMediaMenu: function() {
            this.showSocialMediaMenu = !this.showSocialMediaMenu;
        },
        hideSocialMediaMenu: function() {
            this.showSocialMediaMenu = false;
        },
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
