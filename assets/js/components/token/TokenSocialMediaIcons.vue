<template>
    <div id="token-social-media-icons" class="d-flex align-items-center">
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
        <div class="dropdown social-media-menu" :class="{ 'show': showSocialMediaMenu }">
            <a
                class="c-pointer col-auto d-flex text-white rounded-circle justify-content-center socialMediaIcon p-0 mx-1"
                aria-haspopup="true"
                :aria-expanded="showSocialMediaMenu"
                @click="toggleSocialMediaMenu"
                v-on-clickaway="hideSocialMediaMenu"
            >
                <font-awesome-icon :icon="['fas', 'share']" size="lg" class="icon-default" />
            </a>
           <div
               class="dropdown-menu dropdown-menu-right dropdown-menu-social-media align-self-end align-self-lg-center"
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
                           class="d-block c-pointer"
                           network="email"
                       >
                           <a href="#" class="text-white hover-icon text-decoration-none">
                               <font-awesome-icon icon="envelope" />
                               <span class="social-link" v-html="$parent.$t('token.social_media.email')"></span>
                           </a>
                       </network>
                   </div>
               </social-sharing>
               <social-sharing
                   :title="twitterDescription"
                   :description="description"
                   :quote="description"
                   :hashtags="this.$t('token.social_media.hashtags')"
                   inline-template
               >
                   <div class="px-2">
                       <network class="d-block c-pointer" network="facebook">
                           <a href="#" class="text-white hover-icon text-decoration-none">
                               <font-awesome-icon :icon="['fab', 'facebook']"/>
                               <span class="social-link" v-html="$parent.$t('token.social_media.facebook')"></span>
                           </a>
                       </network>
                       <network class="d-block c-pointer" network="linkedin">
                           <a href="#" class="text-white hover-icon text-decoration-none">
                               <font-awesome-icon :icon="['fab', 'linkedin']"/>
                               <span class="social-link" v-html="$parent.$t('token.social_media.linkedin')"></span>
                           </a>
                       </network>
                       <network class="d-block c-pointer" network="reddit">
                           <a href="#" class="text-white hover-icon text-decoration-none">
                               <font-awesome-icon :icon="['fab', 'reddit']"/>
                               <span class="social-link" v-html="$parent.$t('token.social_media.reddit')"></span>
                           </a>
                       </network>
                       <network class="d-block c-pointer" network="telegram">
                           <a href="#" class="text-white hover-icon text-decoration-none">
                               <font-awesome-icon :icon="['fab', 'telegram']"/>
                               <span class="social-link" v-html="$parent.$t('token.social_media.telegram')"></span>
                           </a>
                       </network>
                       <network class="d-block c-pointer" network="twitter">
                           <a href="#" class="text-white hover-icon text-decoration-none">
                               <font-awesome-icon :icon="['fab', 'twitter']"/>
                               <span class="social-link" v-html="$parent.$t('token.social_media.twitter')"></span>
                           </a>
                       </network>
                   </div>
               </social-sharing>
            </div>
        </div>
    </div>
</template>

<script>
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import {directive as onClickaway} from 'vue-clickaway';

let SocialSharing = require('vue-social-sharing');

if (typeof Vue !== 'undefined') {
    Vue.use(SocialSharing);
}

export default {
    name: 'TokenSocialMediaIcons',
    directives: {
        onClickaway,
    },
    props: {
        discordUrl: String,
        facebookUrl: String,
        telegramUrl: String,
        tokenName: String,
        tokenUrl: String,
        websiteUrl: String,
        youtubeChannelId: String,
    },
    components: {
        FontAwesomeIcon,
    },
    data() {
        return {
            showSocialMediaMenu: false,
            twitterDescription: this.$t('token.social_media.twitter_description'),
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
