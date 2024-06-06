<template>
    <a
        class="btn btn-secondary btn-social font-size-3 my-2 mx-1 c-pointer"
        aria-haspopup="true"
        :aria-expanded="showSocialMediaMenu"
        v-b-tooltip="$t('token.social_media.share')"
        @click="toggleSocialMediaMenu"
        v-on-clickaway="hideSocialMediaMenu"
    >
        <font-awesome-icon
            :icon="['fas', 'share']"
        />
        <div class="dropdown" :class="{ 'show': showSocialMediaMenu }">
            <div
                class="dropdown-menu dropdown-menu-md-right dropdown-menu-social-media mt-4"
                :class="classSocialMediaMenu"
            >
                <div class="align-self-end align-self-lg-center">
                    <social-sharing
                        title="MintMe"
                        :description="description"
                        inline-template
                    >
                        <network
                            class="d-block c-pointer"
                            network="email"
                        >
                            <a class="dropdown-item pl-3">
                                <font-awesome-icon
                                    icon="envelope"
                                    class="dropdown-link-icon float-left mt-1 mr-2 d-lg-inline"
                                />
                                {{ $t('token.social_media.email') }}
                            </a>
                        </network>
                    </social-sharing>
                    <social-sharing
                        :title="twitterDescription"
                        :description="description"
                        :quote="description"
                        :hashtags="this.$t('token.social_media.hashtags')"
                        inline-template
                    >
                        <div>
                            <network class="d-block c-pointer" network="facebook">
                                <a class="dropdown-item pl-3">
                                    <font-awesome-icon
                                        :icon="['fab', 'facebook']"
                                        class="dropdown-link-icon float-left mt-1 mr-2 d-lg-inline"
                                    />
                                    {{ $t('token.social_media.facebook') }}
                                </a>
                            </network>
                            <network class="d-block c-pointer" network="linkedin">
                                <a class="dropdown-item pl-3">
                                    <font-awesome-icon
                                        :icon="['fab', 'linkedin']"
                                        class="dropdown-link-icon float-left mt-1 mr-2 d-lg-inline"
                                    />
                                    {{ $t('token.social_media.linkedin') }}
                                </a>
                            </network>
                            <network class="d-block c-pointer" network="reddit">
                                <a class="dropdown-item pl-3">
                                    <font-awesome-icon
                                        :icon="['fab', 'reddit']"
                                        class="dropdown-link-icon float-left mt-1 mr-2 d-lg-inline"
                                    />
                                    {{ $t('token.social_media.reddit') }}
                                </a>
                            </network>
                            <network class="d-block c-pointer" network="telegram">
                                <a class="dropdown-item pl-3">
                                    <font-awesome-icon
                                        :icon="['fab', 'telegram']"
                                        class="dropdown-link-icon float-left mt-1 mr-2 d-lg-inline"
                                    />
                                    {{ $t('token.social_media.telegram') }}
                                </a>
                            </network>
                            <network class="d-block c-pointer" network="twitter">
                                <a class="dropdown-item pl-3">
                                    <font-awesome-icon
                                        :icon="['fab', 'x-twitter']"
                                        class="dropdown-link-icon float-left mt-1 mr-2 d-lg-inline"
                                    />
                                    {{ $t('token.social_media.twitter') }}
                                </a>
                            </network>
                        </div>
                    </social-sharing>
                    <span class="d-block c-pointer" @click.stop="">
                        <copy-link :content-to-copy="tokenUrl">
                            <a
                                :href="tokenUrl"
                                class="dropdown-item pl-3"
                                @click.prevent=""
                            >
                                <font-awesome-icon
                                    :icon="['fas', 'link']"
                                    class="dropdown-link-icon float-left mt-1 mr-2 d-lg-inline"
                                />
                                {{ $t('token.share_option.copy_link') }}
                            </a>
                        </copy-link>
                    </span>
                </div>
            </div>
        </div>
    </a>
</template>
<script>
import {library} from '@fortawesome/fontawesome-svg-core';
import {
    faGlobe,
    faShare,
    faEnvelope,
    faLink,
} from '@fortawesome/free-solid-svg-icons';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import {VBTooltip} from 'bootstrap-vue';
import {
    faFacebook,
    faDiscord,
    faTelegram,
    faXTwitter,
    faLinkedin,
    faReddit,
} from '@fortawesome/free-brands-svg-icons';
import {directive as onClickaway} from 'vue-clickaway';
import {NotificationMixin} from '../../mixins';
import SocialSharing from 'vue-social-sharing';
library.add(
    faGlobe,
    faShare,
    faEnvelope,
    faFacebook,
    faDiscord,
    faTelegram,
    faXTwitter,
    faLinkedin,
    faReddit,
    faLink
);
import CopyLink from '../CopyLink.vue';

export default {
    name: 'TokenShare',
    components: {
        FontAwesomeIcon,
        CopyLink,
        SocialSharing,
    },
    directives: {
        onClickaway,
        'b-tooltip': VBTooltip,
    },
    mixins: [
        NotificationMixin,
    ],
    props: {
        tokenName: String,
        tokenUrl: String,
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
        classSocialMediaMenu() {
            return {
                'show': this.showSocialMediaMenu,
            };
        },
    },
};
</script>
