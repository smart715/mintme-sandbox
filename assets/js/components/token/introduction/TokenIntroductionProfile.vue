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
                                </template>
                                <token-facebook-address
                                    :address="facebookUrl"
                                    :app-id="facebookAppId"
                                    :editing="editingUrls"
                                    :tokenName="tokenName"
                                />
                                <token-youtube-address
                                    :editable="editable"
                                    :editing="editingUrls"
                                    :channel-id="youtubeChannelId"
                                    :client-id="youtubeClientId"
                                    :tokenName="tokenName"
                                />
                                <template v-if="editingUrls">
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
                                    <div v-if="currentWebsite">
                                        Web:
                                        <a :href="currentWebsite" target="_blank" rel="nofollow">
                                            {{ currentWebsite }}
                                        </a>
                                        <guide>
                                            <template  slot="header">
                                                Web
                                            </template>
                                            <template slot="body">
                                                Link to token creator’s website.
                                                Before adding it, we confirmed ownership.
                                            </template>
                                        </guide>
                                    </div>
                                    <div class="col-12 my-3 text-left d-flex align-items-center">
                                        <b-dropdown id="share" text="Share" variant="primary" class="mt-3">
                                            <social-sharing
                                                    url=""
                                                    title="MintMe"
                                                    :description="description"
                                                    inline-template>
                                                <div class="px-2">
                                                    <network class="d-block c-pointer" network="email">
                                                        <font-awesome-icon icon="envelope"></font-awesome-icon> Email
                                                    </network>
                                                </div>
                                            </social-sharing>
                                            <social-sharing
                                                    :title="twitterDescription"
                                                    :description="description"
                                                    :quote="description"
                                                    hashtags="Mintme,MutualSupport,Monetization,Crowdfunding,Business,Exchange,Creators,
                                                        Technology,Blockchain,Trading,Token,CryptoTrading,Crypto,Voluntary"
                                                    inline-template>
                                                <div class="px-2">
                                                    <network class="d-block c-pointer" network="facebook">
                                                        <font-awesome-icon :icon="['fab', 'facebook']"></font-awesome-icon> Facebook
                                                    </network>
                                                    <network class="d-block c-pointer" network="linkedin">
                                                        <font-awesome-icon :icon="['fab', 'linkedin']"></font-awesome-icon> LinkedIn
                                                    </network>
                                                    <network class="d-block c-pointer" network="reddit">
                                                        <font-awesome-icon :icon="['fab', 'reddit']"></font-awesome-icon> Reddit
                                                    </network>
                                                    <network class="d-block c-pointer" network="telegram">
                                                        <font-awesome-icon :icon="['fab', 'telegram']"></font-awesome-icon> Telegram
                                                    </network>
                                                    <network class="d-block c-pointer" network="twitter">
                                                        <font-awesome-icon :icon="['fab', 'twitter']"></font-awesome-icon> Twitter
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
                                                class="d-inline-block mx-2 mb-1"
                                                v-if="currentTelegram || currentDiscord"
                                            >
                                                Join us on:
                                            </span>
                                            <div class="row justify-content-start pl-4">
                                                <a  :href="currentDiscord"
                                                    v-if="currentDiscord"
                                                    class="col-auto d-flex text-white rounded-circle justify-content-center socialmedia p-0 mx-1"
                                                    target="_blank"
                                                >
                                                    <img src="../../../../img/icon-discord.png"
                                                        class="align-self-center text-center"
                                                        width="45"
                                                        alt="discord icon"
                                                    />
                                                </a>
                                                <a  :href="currentTelegram"
                                                    v-if="currentTelegram"
                                                    class="col-auto d-flex text-white rounded-circle justify-content-center socialmedia p-0 mx-1"
                                                    target="_blank"
                                                >
                                                    <img src="../../../../img/icon-telegram-group.png"
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
import TokenFacebookAddress from '../TokenFacebookAddress';
import TokenTelegramChannel from '../TokenTelegramChannel';
import TokenWebsiteAddress from '../TokenWebsiteAddress';
import TokenYoutubeAddress from '../TokenYoutubeAddress';
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
        facebookAppId: String,
        youtubeClientId: String,
        profileName: String,
        websiteUrl: String,
        facebookUrl: String,
        youtubeChannelId: String,
        editable: Boolean,
        profileUrl: String,
        telegramUrl: String,
        discordUrl: String,
        tokenName: String,
    },
    components: {
        bDropdown,
        bDropdownItem,
        FontAwesomeIcon,
        TokenDiscordChannel,
        TokenFacebookAddress,
        TokenTelegramChannel,
        TokenYoutubeAddress,
        TokenWebsiteAddress,
        Guide,
        Modal,
    },
    data() {
        return {
            submitting: false,
            editingUrls: false,
            editingWebsite: false,
            currentWebsite: this.websiteUrl,
            editingTelegram: false,
            currentTelegram: this.telegramUrl,
            editingDiscord: false,
            currentDiscord: this.discordUrl,
            showWebsiteError: false,
            twitterDescription: 'A great way for mutual support. Check this token and see how the idea evolves: ',
            tokenUrl: this.$routing.generate('token_show', {
                name: this.tokenName,
                tab: 'intro',
            }),
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
        toggleEdit: function(url = null) {
            this.editingDiscord = 'discord' === url;
            this.editingTelegram = 'telegram' === url;
            this.editingWebsite = 'website' === url;
        },
        saveWebsite: function(newWebsite) {
            this.currentWebsite = newWebsite;
        },
        saveTelegram: function(newTelegram) {
            this.currentTelegram = newTelegram;
        },
        saveDiscord: function(newDiscord) {
            this.currentDiscord = newDiscord;
        },
    },
};
</script>
