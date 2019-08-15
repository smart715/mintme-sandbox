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
                            :icon="editingUrls ? 'times' : 'edit'"
                            transform="shrink-4 up-1.5"
                            @click="editingUrls = !editingUrls"/>
                        <a :href="profileUrl">
                            Visit token's creator profile
                        </a>
                        <div class="pt-4">
                            <div class="pb-1">
                                <template v-if="editingUrls">
                                    <div class="form-group my-3" v-if="editingWebsite">
                                        <label for="website-err">Website address:</label>
                                        <input
                                            id="website-err"
                                            type="text"
                                            v-model="newWebsite"
                                            class="form-control"
                                            :class="{ 'is-invalid': showWebsiteError }"
                                            @keyup.enter="checkWebsiteUrl">
                                        <div class="invalid-feedback" v-if="showWebsiteError">
                                            Please provide a valid URL.
                                        </div>
                                        <div class="col-12 text-left mt-3" v-if="editingUrls">
                                            <button class="btn btn-primary" @click="editWebsite">Save</button>
                                            <span class="btn-cancel pl-3 c-pointer" @click="editingWebsite = false">
                                                Cancel
                                            </span>
                                        </div>
                                    </div>
                                    <div class="d-block mx-0 my-1 p-0" v-else>
                                        <a  class="c-pointer"
                                            @click.prevent="openEditUrl('website')"
                                            id="website-link"
                                        >
                                            <span class="token-introduction-profile-icon text-center d-inline-block">
                                                <font-awesome-icon icon="globe" size="lg"/>
                                            </span>
                                            {{ computedWebsiteUrl | truncate(35) }}
                                        </a>
                                        <b-tooltip
                                            v-if="currentWebsite"
                                            target="website-link"
                                        >
                                            {{ computedWeebsiteUrl }}
                                        </b-tooltip>
                                        <a v-if="currentWebsite" @click.prevent="deleteWebsite">
                                            <font-awesome-icon icon="times" class="text-danger c-pointer ml-2" />
                                        </a>
                                    </div>
                                </template>
                                <token-facebook-address
                                    :app-id="facebookAppId"
                                    :editing="editingUrls"
                                    :address="facebookUrl"
                                    :update-url="updateUrl"/>
                                <token-youtube-address
                                    :client-id="youtubeClientId"
                                    :editable="editable"
                                    :editing="editingUrls"
                                    :channel-id="youtubeChannelId"
                                    :update-url="updateUrl"/>
                                <template v-if="editingUrls">
                                    <div class="form-group my-3" v-if="editingTelegram">
                                        <label for="telegram-err">Telegram address:</label>
                                        <input
                                            id="telegram-err"
                                            type="text"
                                            v-model="newTelegram"
                                            class="form-control"
                                            :class="{ 'is-invalid': showTelegramError }"
                                            @keyup.enter="checkTelegramUrl">
                                        <div class="invalid-feedback" v-if="showTelegramError">
                                            Please provide a valid URL.
                                        </div>
                                        <div class="col-12 text-left mt-3" v-if="editingUrls">
                                            <button class="btn btn-primary" @click="editTelegram">Save</button>
                                            <span class="btn-cancel pl-3 c-pointer" @click="editingTelegram = false">
                                                Cancel
                                            </span>
                                        </div>
                                    </div>
                                    <div class="d-block mx-0 my-1 p-0" v-else>
                                        <a  class="c-pointer"
                                            @click.prevent="openEditUrl('telegram')"
                                            id="telegram-link"
                                        >
                                            <span class="token-introduction-profile-icon text-center d-inline-block">
                                                <font-awesome-icon
                                                    :icon="{prefix: 'fab', iconName: 'telegram'}"
                                                    size="lg"
                                                />
                                            </span>
                                            {{ computedTelegramUrl | truncate(35) }}
                                        </a>
                                        <b-tooltip
                                            v-if="currentTelegram"
                                            target="telegram-link"
                                        >
                                            {{ computedTelegramUrl }}
                                        </b-tooltip>
                                        <a v-if="currentTelegram" @click.prevent="deleteTelegram">
                                            <font-awesome-icon icon="times" class="text-danger c-pointer ml-2" />
                                        </a>
                                    </div>
                                    <div class="form-group my-3" v-if="editingDiscord">
                                        <label for="discord-err">Discord address:</label>
                                        <input
                                            id="discord-err"
                                            type="text"
                                            v-model="newDiscord"
                                            class="form-control"
                                            :class="{ 'is-invalid': showDiscordError }"
                                            @keyup.enter="checkDiscordUrl">
                                        <div class="invalid-feedback" v-if="showDiscordError">
                                            Please provide a valid URL.
                                        </div>
                                        <div class="col-12 text-left mt-3" v-if="editingDiscord">
                                            <button class="btn btn-primary" @click="editDiscord">Save</button>
                                            <span class="btn-cancel pl-3 c-pointer" @click="editingDiscord = false">
                                                Cancel
                                            </span>
                                        </div>
                                    </div>
                                    <div class="d-block mx-0 my-1 p-0" v-else>
                                        <a  class="c-pointer"
                                            @click.prevent="openEditUrl('discord')"
                                            id="discord-link"
                                        >
                                            <span class="token-introduction-profile-icon text-center d-inline-block">
                                                <font-awesome-icon
                                                    :icon="{prefix: 'fab', iconName: 'discord'}"
                                                    size="lg"
                                                />
                                            </span>
                                            {{ computedDiscordUrl | truncate(35) }}
                                        </a>
                                        <b-tooltip
                                            v-if="currentDiscord"
                                            target="discord-link"
                                        >
                                            {{ computedDiscordUrl }}
                                        </b-tooltip>
                                        <a v-if="currentDiscord" @click.prevent="deleteDiscord">
                                            <font-awesome-icon icon="times" class="text-danger c-pointer ml-2" />
                                        </a>
                                    </div>
                                </template>
                                <template v-if="!editingUrls">
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
        <modal
            @close="closeFileErrorModal"
            :visible="!!fileErrorVisible">
            <template slot="body">
                <h3 class="modal-title text-center text-danger">{{ fileError.title }}</h3>
                <div class="text-white">
                    <p>
                        {{ fileError.details }}
                        <a
                            v-if="fileErrorHttpUrl"
                            href="https://www.restapitutorial.com/httpstatuscodes.html"
                            target="_blank"
                            rel="nofollow">
                            More information about HTTP status codes.
                        </a>
                    </p>
                    <div class="pt-2 text-center">
                        <button
                            class="btn btn-primary"
                            @click="closeFileErrorModal">
                            OK
                        </button>
                    </div>
                </div>
            </template>
        </modal>
        <modal
            class="text-white"
            :visible="showConfirmWebsiteModal"
            :no-close="false"
            @close="showConfirmWebsiteModal = false">
            <template slot="body">
                <h5 class="modal-title text-center mb-2">Website Confirmation</h5>
                <div class="row">
                    <div class="col-12">
                        <ol class="pl-3">
                            <li>
                                Download
                                <a :href="confirmWebsiteFileUrl" target="_blank">this html verification file</a>
                            </li>
                            <li>Upload the file to {{ parsedWebsite }}</li>
                            <li>
                                Check if file was uploaded successfully by visiting
                                <a
                                    :href="siteRequestUrl"
                                    target="_blank"
                                    rel="nofollow">
                                    {{ siteRequestUrl }}
                                </a>
                            </li>
                            <li>Click confirm below</li>
                        </ol>
                    </div>
                    <div class="col-12 text-left">
                        <button class="btn btn-primary" @click="confirmWebsite">
                            <font-awesome-icon
                                    v-if="submitting"
                                    icon="circle-notch" spin
                                    class="loading-spinner" fixed-width />
                            Confirm
                        </button>
                        <span class="btn-cancel pl-3 c-pointer" @click="showConfirmWebsiteModal = false">Cancel</span>
                    </div>
                </div>
            </template>
        </modal>
    </div>
</template>

<script>
import TokenFacebookAddress from '../TokenFacebookAddress';
import TokenYoutubeAddress from '../TokenYoutubeAddress';
import bDropdown from 'bootstrap-vue/es/components/dropdown/dropdown';
import bDropdownItem from 'bootstrap-vue/es/components/dropdown/dropdown-item';
import {library} from '@fortawesome/fontawesome-svg-core';
import {faEdit, faTimes, faGlobe} from '@fortawesome/free-solid-svg-icons';
import {faTelegram, faDiscord} from '@fortawesome/free-brands-svg-icons';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import {isValidUrl, isValidTelegramUrl, isValidDiscordUrl} from '../../../utils';
import Toasted from 'vue-toasted';
import {FiltersMixin} from '../../../mixins';
import Guide from '../../Guide';
import Modal from '../../modal/Modal';
let SocialSharing = require('vue-social-sharing');

Vue.use(SocialSharing);

library.add(faEdit, faTelegram, faDiscord, faGlobe, faTimes);
Vue.use(Toasted, {
    position: 'top-center',
    duration: 5000,
});

const HTTP_ACCEPTED = 202;

export default {
    name: 'TokenIntroductionProfile',
    props: {
        facebookAppId: String,
        youtubeClientId: String,
        confirmWebsiteFileUrl: String,
        confirmWebsiteUrl: String,
        profileName: String,
        websiteUrl: String,
        facebookUrl: String,
        youtubeChannelId: String,
        updateUrl: String,
        editable: Boolean,
        profileUrl: String,
        tokenUrl: String,
        telegramUrl: String,
        discordUrl: String,
    },
    components: {
        bDropdown,
        bDropdownItem,
        FontAwesomeIcon,
        TokenFacebookAddress,
        TokenYoutubeAddress,
        Guide,
        Modal,
    },
    mixins: [FiltersMixin],
    data() {
        return {
            submitting: false,
            editingUrls: false,
            editingWebsite: false,
            currentWebsite: this.websiteUrl,
            newWebsite: this.websiteUrl || 'http://',
            editingTelegram: false,
            currentTelegram: this.telegramUrl,
            newTelegram: this.telegramUrl || 'https://t.me/joinchat/',
            editingDiscord: false,
            currentDiscord: this.discordUrl,
            newDiscord: this.discordUrl || 'https://discord.gg/',
            showConfirmWebsiteModal: false,
            showWebsiteError: false,
            showDiscordError: false,
            showTelegramError: false,
            parsedWebsite: '',
            websitePath: '/mintme.html',
            fileError: {},
            twitterDescription: 'A great way for mutual support. Check this token and see how the idea evolves: ',
        };
    },
    computed: {
        description: function() {
           return this.twitterDescription + this.tokenUrl;
        },
        siteRequestUrl: function() {
            return this.parsedWebsite + '/mintme.html';
        },
        showEditIcon: function() {
            return !this.editingUrls && this.editable;
        },
        fileErrorVisible: function() {
            return this.fileError.title && this.fileError.details;
        },
        fileErrorHttpUrl: function() {
            return !!this.fileError.visibleHttpUrl;
        },
        computedWebsiteUrl: function() {
            return this.currentWebsite || 'Add Website';
        },
        computedTelegramUrl: function() {
            return this.currentTelegram || 'Add Telegram invitation link';
        },
        computedDiscordUrl: function() {
            return this.currentDiscord || 'Add Discord invitation link';
        },
    },
    watch: {
        newWebsite: function() {
            this.fileError = {};
        },
        editingUrls: function() {
            this.editingDiscord = false;
            this.editingTelegram = false;
            this.editingWebsite = false;
        },
    },
    methods: {
        openEditUrl: function(url) {
            this.editingDiscord = 'discord' === url;
            this.editingTelegram = 'telegram' === url;
            this.editingWebsite = 'website' === url;
        },
        editWebsite: function() {
            if (this.newWebsite.length && this.newWebsite !== this.websiteUrl) {
                this.checkWebsiteUrl();
            }

            if (this.showWebsiteError && !this.newWebsite.length) {
                this.showWebsiteError = false;
            }

            if (this.showWebsiteError) {
                return;
            }

            if (!this.showConfirmWebsiteModal) {
                this.editingUrls = false;
            }
        },
        checkWebsiteUrl: function() {
            this.showWebsiteError = false;
            if (!isValidUrl(this.newWebsite)) {
                this.showWebsiteError = true;
                return;
            }

            this.parsedWebsite = this.newWebsite.replace(/\/+$/, '');
            this.showConfirmWebsiteModal = true;
        },
        confirmWebsite: function() {
            this.requestForWebsiteUrl();
        },
        deleteWebsite: function() {
            this.newWebsite = null;
            this.requestForWebsiteUrl();
        },
        requestForWebsiteUrl: function() {
            if (this.submitting) {
                return;
            }
            this.submitting = true;
            this.$axios.single.post(this.confirmWebsiteUrl, {url: this.parsedWebsite})
                .then((response) => {
                    if (response.data.verified) {
                        this.currentWebsite = this.newWebsite = this.websiteUrl = this.parsedWebsite;
                        this.$toasted.success(response.data.message);
                        this.showConfirmWebsiteModal = false;
                        this.editingWebsite = false;
                        this.editingUrls = false;
                        this.clearFileError();
                    } else if (response.data.errors.fileError) {
                        this.fileError = response.data.errors.fileError;
                    } else if (response.data.errors.length) {
                        response.data.errors.forEach((error) => this.$toasted.error(error));
                        this.clearFileError();
                    } else {
                        this.clearFileError();
                        return Promise.reject({response: 'error'});
                    }
                })
                .catch(({response}) => this.$toasted.error(!response ? 'Network error' : response.statusText))
                .then(() => this.submitting = false);
        },
        editTelegram: function() {
            if (this.newTelegram.length && this.newTelegram !== this.currentTelegram) {
                this.checkTelegramUrl();
            }

            if (this.showTelegramError && !this.newTelegram.length) {
                this.showTelegramError = false;
            }
        },
        checkTelegramUrl: function() {
            this.showTelegramError = false;
            if (!isValidTelegramUrl(this.newTelegram)) {
                this.showTelegramError = true;
                return;
            }
            this.requestForTelegramUrl();
        },
        deleteTelegram: function() {
            this.newTelegram = '';
            this.requestForTelegramUrl();
        },
        requestForTelegramUrl: function() {
            if (this.submitting) {
                return;
            }
            this.submitting = true;
            this.$axios.single.patch(this.updateUrl, {
                telegramUrl: this.newTelegram,
            })
                .then((response) => {
                    if (response.status === HTTP_ACCEPTED) {
                        let state = this.newTelegram ? 'added' : 'removed';
                        this.currentTelegram = this.newTelegram;
                        this.newTelegram = this.newTelegram || 'https://t.me/joinchat/';
                        this.$toasted.success(`Telegram invitation link ${state} successfully`);
                        this.editingTelegram = false;
                    } else {
                        this.$toasted.error(response.data.message || 'Network error');
                    }
                    this.submitting = false;
                });
        },
        editDiscord: function() {
            if (this.newDiscord.length && this.newDiscord !== this.currentDiscord) {
                this.checkDiscordUrl();
            }

            if (this.showDiscordError && !this.newDiscord.length) {
                this.showDiscordError = false;
            }
        },
        checkDiscordUrl: function() {
            this.showDiscordError = false;
            if (!isValidDiscordUrl(this.newDiscord)) {
                this.showDiscordError = true;
                return;
            }
            this.requestForDiscordUrl();
        },
        deleteDiscord: function() {
            this.newDiscord = '';
            this.requestForDiscordUrl();
        },
        requestForDiscordUrl: function() {
            if (this.submitting) {
                return;
            }
            this.submitting = true;
            this.$axios.single.patch(this.updateUrl, {
                discordUrl: this.newDiscord,
            })
                .then((response) => {
                    if (response.status === HTTP_ACCEPTED) {
                        let state = this.newDiscord ? 'added' : 'removed';
                        this.currentDiscord = this.newDiscord;
                        this.newDiscord = this.newDiscord || 'https://discord.gg/';
                        this.$toasted.success(`Discord invitation link ${state} successfully`);
                        this.editingDiscord = false;
                    } else {
                        this.$toasted.error(response.data.message || 'Network error');
                    }
                    this.submitting = false;
                });
        },
        closeFileErrorModal: function() {
            this.fileError = {};
            this.showConfirmWebsiteModal = true;
        },
        clearFileError: function() {
            this.fileError = {};
        },
    },
};
</script>
