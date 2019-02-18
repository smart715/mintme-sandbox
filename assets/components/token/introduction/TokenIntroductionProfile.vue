<template>
    <div>
        <div class="card">
            <div class="card-header">
                {{ profileName }}
                <span class="card-header-icon">
                    <font-awesome-icon
                        v-if="editable"
                        class="icon float-right c-pointer"
                        size="2x"
                        :icon="icon"
                        transform="shrink-4 up-1.5"
                        @click="editUrls"/>
                </span>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-12">
                        <a :href="profileUrl" target="_blank">
                            {{ profileUrl }}
                        </a>
                        <div class="pt-4">
                            <div class="pb-1">
                                <div v-if="!editingUrls">
                                    <div v-if="currentWebsite">
                                        Web:
                                        <a :href="currentWebsite" target="_blank" rel="nofollow">
                                            {{ currentWebsite }}
                                        </a>
                                        <guide>
                                            <font-awesome-icon
                                                    icon="question"
                                                    slot='icon'
                                                    class="ml-1 mb-1 bg-primary text-white
                                            rounded-circle square blue-question"/>
                                            <template  slot="header">
                                                Web
                                            </template>
                                            <template slot="body">
                                                Link to token creator’s website.
                                                Before adding it, we confirmed ownership.
                                            </template>
                                        </guide>
                                    </div>
                                </div>
                                <div class="form-group" v-else>
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
                                </div>
                            </div>
                            <div class="pb-1" v-if="facebookUrl">
                                <token-facebook-address
                                    :app-id="facebookAppId"
                                    :editing="editingUrls"
                                    :address="facebookUrl"
                                    :update-url="updateUrl"/>
                            </div>
                            <div v-if="youtubeChannelId">
                                <token-youtube-address
                                    :client-id="youtubeClientId"
                                    :editable="editable"
                                    :editing="editingUrls"
                                    :channel-id="youtubeChannelId"
                                    :update-url="updateUrl"/>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 pt-3 text-right">
                        <b-dropdown id="share" text="Share" variant="primary">
                            <social-sharing :url="profileUrl"
                                            title="MINTME"
                                            description="Check my new cryptocurrency."
                                            quote="Check my new token."
                                            hashtags="mintme"
                                            inline-template>
                                <div class="px-2">
                                    <network class="d-block c-pointer" network="email">
                                        <font-awesome-icon icon="envelope"></font-awesome-icon> Email
                                    </network>
                                    <network class="d-block c-pointer" network="facebook">
                                        <font-awesome-icon :icon="['fab', 'facebook']"></font-awesome-icon> Facebook
                                    </network>
                                    <network class="d-block c-pointer" network="linkedin">
                                        <font-awesome-icon :icon="['fab', 'linkedin']"></font-awesome-icon> LinkedIn
                                    </network>
                                    <network class="d-block c-pointer" network="googleplus">
                                        <font-awesome-icon :icon="['fab', 'google-plus']"></font-awesome-icon> Google +
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
                    </div>
                </div>
            </div>
        </div>
        <div class="modal" :class="{ show: showConfirmWebsiteModal }" tabindex="-1" role="dialog">
             <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Website Confirmation</h5>
                        <button type="button" class="close" aria-label="Close" @click="showConfirmWebsiteModal = false">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-12">
                                <ol>
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
                        <div class="col-12 text-center">
                            <button class="btn btn-primary" @click="confirmWebsite">Confirm</button>
                            <button class="btn btn-default" @click="showConfirmWebsiteModal = false">Cancel</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</template>

<script>
import TokenFacebookAddress from '../TokenFacebookAddress';
import TokenYoutubeAddress from '../TokenYoutubeAddress';
import bDropdown from 'bootstrap-vue/es/components/dropdown/dropdown';
import bDropdownItem from 'bootstrap-vue/es/components/dropdown/dropdown-item';
import {library} from '@fortawesome/fontawesome-svg-core';
import {faEdit, faCheck} from '@fortawesome/free-solid-svg-icons';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import {isValidUrl} from '../../../js/utils';
import Toasted from 'vue-toasted';
import Guide from '../../Guide';
let SocialSharing = require('vue-social-sharing');

Vue.use(SocialSharing);

library.add(faEdit, faCheck);
Vue.use(Toasted, {
    position: 'top-center',
    duration: 5000,
});

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
    },
    components: {
        bDropdown,
        bDropdownItem,
        FontAwesomeIcon,
        TokenFacebookAddress,
        TokenYoutubeAddress,
        Guide,
    },
    data() {
        return {
            editingUrls: false,
            currentWebsite: this.websiteUrl,
            newWebsite: this.websiteUrl,
            icon: 'edit',
            showConfirmWebsiteModal: false,
            showWebsiteError: false,
            parsedWebsite: '',
            websitePath: '/mintme.html',
        };
    },
    computed: {
        siteRequestUrl: function() {
              return this.parsedWebsite + '/mintme.html';
        },
    },
    methods: {
        editUrls: function() {
            if (this.editingUrls && this.newWebsite.length && this.newWebsite !== this.websiteUrl) {
                this.checkWebsiteUrl();
            }

            if (this.showWebsiteError && !this.newWebsite.length) {
                this.showWebsiteError = false;
            }

            if (this.showWebsiteError) {
                return;
            }

            this.editingUrls = !this.editingUrls;
            this.icon = this.editingUrls ? 'check' : 'edit';
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
            this.$axios.single.post(this.confirmWebsiteUrl, {url: this.parsedWebsite})
                .then((response) => {
                    if (response.data.verified) {
                        this.currentWebsite = this.parsedWebsite;
                        this.$toasted.success('Website confirmed successfully');
                    } else if (response.data.errors.length) {
                        response.data.errors.forEach((error) => {
                            this.$toasted.error(error);
                        });
                        this.newWebsite = this.currentWebsite;
                    } else {
                        this.$toasted.error('Website couldn\'t be confirmed, try again');
                        this.newWebsite = this.currentWebsite;
                    }
                }, (error) => {
                    this.$toasted.error('Website couldn\'t be confirmed, try again');
                })
                .then(() => {
                    this.showConfirmWebsiteModal = false;
                });
        },
    },
};
</script>
