<template>
    <div id="token-social-media-icons" class="d-flex align-items-center p-1">
        <a
            v-if="websiteUrl"
            :href="websiteUrl"
            rel="noopener nofollow"
            class="col-auto d-flex text-white rounded-circle justify-content-center p-0 mx-1 hover-icon"
            target="_blank"
        >
            <guide>
                <template slot="icon">
                    <font-awesome-icon icon="globe" size="lg" class="icon-default" />
                </template>
                <template slot="body">
                    {{ $t('token.social_media.tooltip_website') }}
                </template>
            </guide>
        </a>
        <a
            v-if="twitterUrl"
            :href="twitterUrl"
            rel="noopener nofollow"
            class="col-auto d-flex text-white rounded-circle justify-content-center p-0 mx-1"
            target="_blank"
        >
            <font-awesome-icon :icon="['fab', 'x-twitter']" size="lg" class="icon-default" />
        </a>
        <a
            v-if="youtubeChannelId"
            :href="youtubeUrl"
            rel="noopener nofollow"
            class="col-auto d-flex text-white rounded-circle justify-content-center p-0 mx-1"
            target="_blank"
        >
            <guide>
                <template slot="icon">
                    <font-awesome-icon :icon="['fab', 'youtube']" size="lg" class="icon-default" />
                </template>
                <template slot="body">
                    {{ $t('token.social_media.tooltip') }}
                </template>
            </guide>
        </a>
        <a
            v-if="facebookUrl"
            :href="facebookUrl"
            rel="noopener nofollow"
            class="col-auto d-flex text-white rounded-circle justify-content-center p-0 mx-1"
            target="_blank"
        >
            <guide>
                <template slot="icon">
                    <font-awesome-icon :icon="['fab', 'facebook']" size="lg" class="icon-default" />
                </template>
                <template slot="body">
                    {{ $t('token.social_media.tooltip') }}
                </template>
            </guide>
        </a>
        <a
            v-if="discordUrl"
            :href="discordUrl"
            rel="noopener nofollow"
            class="col-auto d-flex text-white rounded-circle justify-content-center p-0 mx-1"
            target="_blank"
        >
            <font-awesome-icon
                class="icon-default"
                size="lg"
                :icon="['fab', 'discord']"
                v-b-tooltip="$t('token.social_media.discord')"
            />
        </a>
        <a
            v-if="telegramUrl"
            :href="telegramUrl"
            rel="noopener nofollow"
            class="col-auto d-flex text-white rounded-circle justify-content-center p-0 mx-1"
            target="_blank"
        >
            <font-awesome-icon
                class="icon-default"
                size="lg"
                :icon="['fab', 'telegram']"
                v-b-tooltip="$t('token.social_media.telegram')"
            />
        </a>
        <div class="dropdown" :class="{ 'show': showSocialMediaMenu }">
            <a
                class="c-pointer col-auto d-flex text-white rounded-circle justify-content-center p-0 mx-1"
                aria-haspopup="true"
                :aria-expanded="showSocialMediaMenu"
                @click="toggleSocialMediaMenu"
                v-on-clickaway="hideSocialMediaMenu"
            >
            <font-awesome-icon
                :icon="['fas', 'share']"
                size="lg"
                class="icon-default"
                v-b-tooltip="$parent.$t('token.social_media.share')"
            />
            </a>
            <div
                class="dropdown-menu dropdown-menu-right dropdown-menu-social-media"
                :class="{ 'show': showSocialMediaMenu }"
            >
                <div class="align-self-end align-self-lg-center">
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
                                <a href="#" class="hover-icon text-decoration-none">
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
                                <a href="#" class="hover-icon text-decoration-none">
                                    <font-awesome-icon :icon="['fab', 'facebook']"/>
                                    <span class="social-link" v-html="$parent.$t('token.social_media.facebook')"></span>
                                </a>
                            </network>
                            <network class="d-block c-pointer" network="linkedin">
                                <a href="#" class="hover-icon text-decoration-none">
                                    <font-awesome-icon :icon="['fab', 'linkedin']"/>
                                    <span class="social-link" v-html="$parent.$t('token.social_media.linkedin')"></span>
                                </a>
                            </network>
                            <network class="d-block c-pointer" network="reddit">
                                <a href="#" class="hover-icon text-decoration-none">
                                    <font-awesome-icon :icon="['fab', 'reddit']"/>
                                    <span class="social-link" v-html="$parent.$t('token.social_media.reddit')"></span>
                                </a>
                            </network>
                            <network class="d-block c-pointer" network="telegram">
                                <a href="#" class="hover-icon text-decoration-none">
                                    <font-awesome-icon :icon="['fab', 'telegram']"/>
                                    <span class="social-link" v-html="$parent.$t('token.social_media.telegram')"></span>
                                </a>
                            </network>
                            <network class="d-block c-pointer" network="twitter">
                                <a href="#" class="hover-icon text-decoration-none">
                                    <font-awesome-icon :icon="['fab', 'x-twitter']"/>
                                    <span class="social-link" v-html="$parent.$t('token.social_media.twitter')"></span>
                                </a>
                            </network>
                        </div>
                    </social-sharing>
                    <div class="px-2" @click.stop="">
                        <span class="d-block c-pointer">
                            <copy-link
                                class="text-white hover-icon text-decoration-none"
                                :content-to-copy="tokenUrl"
                            >
                                <font-awesome-icon :icon="['fas', 'link']"/>
                                <a
                                    :href="tokenUrl"
                                    @click.prevent=""
                                    class="text-white"
                                >
                                  {{ $t('token.share_option.copy_link') }}
                                </a>
                            </copy-link>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
import {library} from '@fortawesome/fontawesome-svg-core';
import Guide from '../Guide';
import {
    faGlobe,
    faShare,
    faEnvelope,
    faLink,
} from '@fortawesome/free-solid-svg-icons';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import {VBTooltip} from 'bootstrap-vue';
import {
    faYoutube,
    faFacebook,
    faDiscord,
    faTelegram,
    faXTwitter,
    faLinkedin,
    faReddit,
} from '@fortawesome/free-brands-svg-icons';
import {directive as onClickaway} from 'vue-clickaway';
import {NotificationMixin} from '../../mixins';
library.add(
    faGlobe,
    faShare,
    faEnvelope,
    faYoutube,
    faFacebook,
    faDiscord,
    faTelegram,
    faXTwitter,
    faLinkedin,
    faReddit,
    faLink
);
import CopyLink from '../CopyLink.vue';

const SocialSharing = require('vue-social-sharing');

if ('undefined' !== typeof Vue) {
    SocialSharing.components['font-awesome-icon'] = FontAwesomeIcon;
    Vue.use(SocialSharing);
}

export default {
    name: 'TokenSocialMediaIcons',
    components: {
        Guide,
        FontAwesomeIcon,
        CopyLink,
    },
    directives: {
        onClickaway,
        'b-tooltip': VBTooltip,
    },
    mixins: [
        NotificationMixin,
    ],
    props: {
        discordUrl: String,
        facebookUrl: String,
        telegramUrl: String,
        tokenName: String,
        tokenUrl: String,
        websiteUrl: String,
        twitterUrl: String,
        youtubeChannelId: String,
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
            return `https://www.youtube.com/channel/${this.youtubeChannelId}`;
        },
    },
};
</script>
