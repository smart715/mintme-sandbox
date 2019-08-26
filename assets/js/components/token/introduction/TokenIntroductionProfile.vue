<template>
    <div>
        <div class="card h-100">
            <div class="card-header">
                {{ profileName }}
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-12">
                        <font-awesome-icon
                            class="icon-edit float-right c-pointer"
                            :icon="editingUrlsIcon"
                            transform="shrink-4 up-1.5"
                            @click="editingUrls = !editingUrls"
                        />
                        <a :href="profileUrl">
                            Visit token's creator profile
                        </a>
                        <div class="pt-4">
                            <div class="pb-1">
                                <template v-if="editingUrls">
                                    <token-website-address
                                        :currentWebsite="currentWebsite"
                                        :editingWebsite="editingWebsite"
                                        :tokenName="tokenName"
                                        @saveWebsite="saveWebsite"
                                        @toggleEdit="toggleEdit"
                                    />
                                    <token-youtube-address
                                        :editable="editable"
                                        :channel-id="currentYoutube"
                                        :client-id="youtubeClientId"
                                        :tokenName="tokenName"
                                        @saveYoutube="saveYoutube"
                                    />
                                    <token-facebook-address
                                        :address="currentFacebook"
                                        :app-id="facebookAppId"
                                        :editing="editingUrls"
                                        :tokenName="tokenName"
                                        @saveFacebook="saveFacebook"
                                    />
                                    <token-telegram-channel
                                        :currentTelegram="currentTelegram"
                                        :editingTelegram="editingTelegram"
                                        :tokenName="tokenName"
                                        @saveTelegram="saveTelegram"
                                        @toggleEdit="toggleEdit"
                                    ></token-telegram-channel>
                                    <token-discord-channel
                                        :currentDiscord="currentDiscord"
                                        :editingDiscord="editingDiscord"
                                        :tokenName="tokenName"
                                        @saveDiscord="saveDiscord"
                                        @toggleEdit="toggleEdit"
                                    ></token-discord-channel>
                                </template>
                                <template v-else>
                                    <token-website-address-view
                                        v-if="currentWebsite"
                                        :currentWebsite="currentWebsite"
                                    />
                                    <token-youtube-address-view
                                        v-if="currentYoutube"
                                        :channel-id="currentYoutube"
                                        :client-id="youtubeClientId"
                                    />
                                    <token-facebook-address-view
                                        v-if="currentFacebook"
                                        :address="currentFacebook"
                                        :app-id="facebookAppId"
                                    />
                                    <div class="col-12 my-3 text-left d-flex align-items-center">
                                        <b-dropdown
                                            id="share"
                                            text="Share"
                                            variant="primary"
                                            class="mt-3"
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
                                                hashtags="Mintme,MutualSupport,Monetization,Crowdfunding,Business,Exchange,Creators,
                                                    Technology,Blockchain,Trading,Token,CryptoTrading,Crypto,Voluntary"
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
                                        <div class="tooltip-static tooltip-static-left">
                                            Do you want to help the token creator? Spread the word!
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-12">
                                            <span
                                                v-if="currentTelegram || currentDiscord"
                                                class="d-inline-block mx-2 mb-1"
                                            >
                                                Join us on:
                                            </span>
                                            <div class="row justify-content-start pl-4">
                                                <a
                                                    v-if="currentDiscord"
                                                    :href="currentDiscord"
                                                    class="col-auto d-flex text-white rounded-circle justify-content-center socialmedia p-0 mx-1"
                                                    target="_blank"
                                                >
                                                    <img
                                                        src="../../../../img/icon-discord.png"
                                                        class="align-self-center text-center"
                                                        width="45"
                                                        alt="discord icon"
                                                    />
                                                </a>
                                                <a
                                                    v-if="currentTelegram"
                                                    :href="currentTelegram"
                                                    class="col-auto d-flex text-white rounded-circle justify-content-center socialmedia p-0 mx-1"
                                                    target="_blank"
                                                >
                                                    <img
                                                        src="../../../../img/icon-telegram-group.png"
                                                        class="align-self-center text-center"
                                                        width="45"
                                                        alt="telegram group"
                                                    />
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import TokenDiscordChannel from '../TokenDiscordChannel';
import TokenFacebookAddress from '../facebook/TokenFacebookAddress';
import TokenFacebookAddressView from '../facebook/TokenFacebookAddressView';
import TokenTelegramChannel from '../TokenTelegramChannel';
import TokenWebsiteAddress from '../website/TokenWebsiteAddress';
import TokenWebsiteAddressView from '../website/TokenWebsiteAddressView';
import TokenYoutubeAddress from '../youtube/TokenYoutubeAddress';
import TokenYoutubeAddressView from '../youtube/TokenYoutubeAddressView';
import bDropdown from 'bootstrap-vue/es/components/dropdown/dropdown';
import bDropdownItem from 'bootstrap-vue/es/components/dropdown/dropdown-item';
import {library} from '@fortawesome/fontawesome-svg-core';
import {faEdit, faTimes} from '@fortawesome/free-solid-svg-icons';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import Toasted from 'vue-toasted';
import Guide from '../../Guide';
import Modal from '../../modal/Modal';

let SocialSharing = require('vue-social-sharing');

Vue.use(SocialSharing);

library.add(faEdit, faTimes);
Vue.use(Toasted, {
    position: 'top-center',
    duration: 5000,
});

export default {
    name: 'TokenIntroductionProfile',
    props: {
        discordUrl: String,
        editable: Boolean,
        facebookUrl: String,
        facebookAppId: String,
        profileName: String,
        profileUrl: String,
        telegramUrl: String,
        tokenName: String,
        websiteUrl: String,
        youtubeClientId: String,
        youtubeChannelId: String,
    },
    components: {
        bDropdown,
        bDropdownItem,
        FontAwesomeIcon,
        Guide,
        Modal,
        TokenDiscordChannel,
        TokenFacebookAddress,
        TokenFacebookAddressView,
        TokenTelegramChannel,
        TokenYoutubeAddress,
        TokenYoutubeAddressView,
        TokenWebsiteAddress,
        TokenWebsiteAddressView,
    },
    data() {
        return {
            currentDiscord: this.discordUrl,
            currentFacebook: this.facebookUrl,
            currentTelegram: this.telegramUrl,
            currentWebsite: this.websiteUrl,
            currentYoutube: this.youtubeChannelId,
            editingDiscord: false,
            editingTelegram: false,
            editingUrls: false,
            editingWebsite: false,
            showWebsiteError: false,
            submitting: false,
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
        editingUrlsIcon: function() {
            return this.editingUrls ? 'times' : 'edit';
        },
        showEditIcon: function() {
            return !this.editingUrls && this.editable;
        },
    },
    watch: {
        editingUrls: function() {
            this.toggleEdit(null);
        },
    },
    methods: {
        saveWebsite: function(newWebsite) {
            this.currentWebsite = newWebsite;
        },
        saveDiscord: function(newDiscord) {
            this.currentDiscord = newDiscord;
        },
        saveFacebook: function(newFacebook) {
            this.currentFacebook = newFacebook;
        },
        saveTelegram: function(newTelegram) {
            this.currentTelegram = newTelegram;
        },
        saveYoutube: function(newChannelId) {
            this.currentYoutube = newChannelId;
        },
        toggleEdit: function(url = null) {
            this.editingDiscord = 'discord' === url;
            this.editingTelegram = 'telegram' === url;
            this.editingWebsite = 'website' === url;
        },
    },
};
</script>
