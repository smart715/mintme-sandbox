<template>
    <div>
        <div class="card">
            <div class="card-header">
                {{ this.profileName }}
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
                        <a href="#" target="_blank">
                            linktotokencreatorprofile.com
                        </a>
                        <div class="pt-4">
                            <div class="pb-1">
                                <div v-if="!editingUrls">
                                    Web:
                                    <a :href="this.currentWebsite" target="_blank" rel="nofollow">
                                        {{ this.currentWebsite }}
                                    </a>
                                    <guide>
                                        <font-awesome-icon
                                            icon="question"
                                            slot='icon'
                                            class="ml-1 mb-1 bg-primary text-white
                                            rounded-circle square blue-question"/>
                                        <template  slot="header">
                                            Web Guide
                                        </template>
                                        <template slot="body">
                                            Lorem ipsum dolor sit amet, consectetur adipisicing elit.
                                        </template>
                                    </guide>
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
                            <div class="pb-1">
                                <token-facebook-address
                                    :app-id="facebookAppId"
                                    :editing="editingUrls"
                                    :address="facebookUrl"
                                    :update-url="updateUrl"
                                    :csrfToken="csrfToken"/>
                            </div>
                            <div>
                                <token-youtube-address
                                    :client-id="youtubeClientId"
                                    :editable="editable"
                                    :editing="editingUrls"
                                    :channel-id="youtubeChannelId"
                                    :update-url="updateUrl"
                                    :csrfToken="csrfToken"/>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 pt-3 text-right">
                        <b-dropdown id="share" text="Share" variant="primary">
                            <b-dropdown-item
                                href="#"
                                target="_blank">
                                Facebook
                            </b-dropdown-item>
                            <b-dropdown-item
                                href="#"
                                target="_blank">
                                LinkedIn
                            </b-dropdown-item>
                            <b-dropdown-item
                                href="#"
                                target="_blank">
                                YouTube
                            </b-dropdown-item>
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
                                            :href="parsedWebsite+'/mintme.html'"
                                            target="_blank"
                                            rel="nofollow">
                                            {{ parsedWebsite }}/mintme.html
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
import axios from 'axios';
import Toasted from 'vue-toasted';
import Guide from '../../Guide';

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
        csrfToken: String,
        editable: Boolean,
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
        };
    },
    methods: {
        editUrls: function() {
            this.editingUrls = !this.editingUrls;
            this.icon = this.editingUrls ? 'check' : 'edit';
        },
        checkWebsiteUrl: function() {
            this.showWebsiteError = false;
            if (!isValidUrl(this.newWebsite)) {
                this.showWebsiteError = true;
                return;
            }
            let parsedUrl = parse(this.newWebsite, true);
            this.parsedWebsite = parsedUrl.origin + rtrim(parsedUrl.pathname, '/');
            this.showConfirmWebsiteModal = true;
        },
        confirmWebsite: function() {
            axios.post(this.confirmWebsiteUrl, {url: this.parsedWebsite})
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
