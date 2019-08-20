<template>
    <div>
        <div class="form-group my-3" v-if="editingWebsite">
            <label for="website-err">Website address:</label>
            <input id="website-err"
                type="text"
                v-model="newWebsite"
                class="form-control"
                :class="{ 'is-invalid': showWebsiteError }"
                @keyup.enter="checkWebsiteUrl">
            <div class="invalid-feedback" v-if="showWebsiteError">
                Please provide a valid URL.
            </div>
            <div class="col-12 text-left mt-3">
                <button class="btn btn-primary" @click="editWebsite">Save</button>
                <span class="btn-cancel pl-3 c-pointer" @click="$emit('toggleEdit')">
                    Cancel
                </span>
            </div>
        </div>
        <div class="d-block mx-0 my-1 p-0" v-else>
            <a id="website-link" class="c-pointer" @click.prevent="$emit('toggleEdit', 'website')">
                <span class="token-introduction-profile-icon text-center d-inline-block">
                    <font-awesome-icon icon="globe" size="lg"/>
                </span>
                {{ computedWebsiteUrl | truncate(35) }}
            </a>
            <b-tooltip v-if="currentWebsite" target="website-link" :title="computedWebsiteUrl" />
            <a v-if="currentWebsite" @click.prevent="deleteWebsite">
                <font-awesome-icon icon="times" class="text-danger c-pointer ml-2" />
            </a>
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
                            href="https://www.restapitutorial.com/httpstatuscodes.html"
                            v-if="fileErrorHttpUrl"
                            target="_blank"
                            rel="nofollow">
                            More information about HTTP status codes.
                        </a>
                    </p>
                    <div class="pt-2 text-center">
                        <button class="btn btn-primary" @click="closeFileErrorModal">
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
import Toasted from 'vue-toasted';
import {library} from '@fortawesome/fontawesome-svg-core';
import {faGlobe, faTimes} from '@fortawesome/free-solid-svg-icons';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import {FiltersMixin} from '../../mixins/';
import {isValidUrl} from '../../utils';
import Modal from '../modal/Modal';

library.add(faGlobe, faTimes);
Vue.use(Toasted, {
    position: 'top-center',
    duration: 5000,
});

export default {
    name: 'TokenWebsiteAddress',
    props: {
        confirmWebsiteFileUrl: String,
        confirmWebsiteUrl: String,
        currentWebsite: String,
        editingWebsite: Boolean,
        updateUrl: String,
    },
    components: {
        FontAwesomeIcon,
        Modal,
    },
    mixins: [FiltersMixin],
    data() {
        return {
            fileError: {},
            newWebsite: this.currentWebsite || 'https://',
            parsedWebsite: '',
            showWebsiteError: false,
            submitting: false,
            showConfirmWebsiteModal: false,
        };
    },
    watch: {
        editingWebsite: function() {
            this.submitting = false;
        },
        newWebsite: function() {
            this.fileError = {};
        },
    },
    computed: {
        computedWebsiteUrl: function() {
            return this.currentWebsite || 'Add Website';
        },
        fileErrorHttpUrl: function() {
            return !!this.fileError.visibleHttpUrl;
        },
        fileErrorVisible: function() {
            return this.fileError.title && this.fileError.details;
        },
        siteRequestUrl: function() {
            return this.parsedWebsite + '/mintme.html';
        },
    },
    methods: {
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
            this.saveWebsite();
        },
        deleteWebsite: function() {
            this.newWebsite = null;
            this.saveWebsite();
        },
        saveWebsite: function() {
            if (this.submitting) {
                return;
            }

            this.submitting = true;
            this.$axios.single.post(this.confirmWebsiteUrl, {
                url: this.parsedWebsite,
            })
                .then((response) => {
                    if (response.data.verified) {
                        this.$emit('saveWebsite', this.newWebsite);
                        this.newWebsite = this.newWebsite || 'https://';
                        this.$toasted.success(response.data.message);
                        this.showConfirmWebsiteModal = false;
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
