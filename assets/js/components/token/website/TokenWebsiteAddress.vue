<template>
    <div class="row">
        <div
            v-if="editing"
            class="form-group col-12"
        >
            <label for="website-err">{{ $t('token.website.label') }}</label>
            <input
                id="website-err"
                v-model="newWebsite"
                type="text"
                class="form-control"
                :class="{ 'is-invalid': showWebsiteError }"
                @keyup.enter="checkWebsiteUrl"
            >
            <div
                v-if="showWebsiteError"
                class="invalid-feedback"
            >
                {{ $t('token.website.invalid_url') }}
            </div>
            <div class="col-12 text-left mt-3 px-0">
                <button
                    class="btn btn-primary"
                    @click="editWebsite"
                >
                    {{ $t('token.website.save') }}
                </button>
                <span
                    class="btn-cancel pl-3 c-pointer"
                    @click="editing = false"
                >
                    {{ $t('token.website.cancel') }}
                </span>
            </div>
        </div>
        <div
            v-else
            class="col text-truncate"
        >
            <span
                id="website-link"
                class="c-pointer text-white hover-icon"
                @click.prevent="toggleEdit"
            >
                <span class="token-introduction-profile-icon text-center d-inline-block">
                    <font-awesome-icon
                        icon="globe"
                        size="lg"
                    />
                </span>
                <a href="#" class="text-reset">
                    {{ computedWebsiteUrl }}
                </a>
            </span>
            <b-tooltip
                v-if="currentWebsite"
                target="website-link"
                :title="computedWebsiteUrl"
            />
        </div>
        <div class="col-auto">
            <a
                v-if="currentWebsite"
                @click.prevent="deleteWebsite"
            >
                <font-awesome-icon
                    icon="times"
                    class="text-danger c-pointer ml-2"
                />
            </a>
        </div>
        <modal
            @close="closeFileErrorModal"
            :visible="!!fileErrorVisible"
        >
            <template slot="body">
                <h3 class="modal-title text-center text-danger">{{ fileError.title }}</h3>
                <div class="text-white">
                    <p>
                        {{ fileError.details }}
                        <a
                            v-if="fileErrorHttpUrl"
                            href="https://www.restapitutorial.com/httpstatuscodes.html"
                            target="_blank"
                            rel="nofollow"
                        >
                            {{ $t('token.website.more_info') }}
                        </a>
                    </p>
                    <div class="pt-2 text-center">
                        <button
                            class="btn btn-primary"
                            @click="closeFileErrorModal"
                        >
                            {{ $t('token.website.ok') }}
                        </button>
                    </div>
                </div>
            </template>
        </modal>
        <modal
            class="text-white"
            :visible="showConfirmWebsiteModal"
            :no-close="false"
            @close="showConfirmWebsiteModal = false"
        >
            <template slot="body">
                <h5 class="modal-title text-center mb-2">
                    {{ $t('token.website.confirmation_title') }}
                </h5>
                <div class="row">
                    <div class="col-12">
                        <ol class="pl-3">
                            <li>
                                {{ $t('token.website.download_1') }}
                                <a
                                    :href="confirmWebsiteFileUrl"
                                    target="_blank"
                                >
                                    {{ $t('token.website.download_2') }}
                                </a>
                            </li>
                            <li>{{ $t('token.website.upload', translationsContext) }}</li>
                            <li>
                                {{ $t('token.website.check') }}
                                <a
                                    :href="siteRequestUrl"
                                    target="_blank"
                                    rel="nofollow"
                                >
                                    {{ siteRequestUrl }}
                                </a>
                            </li>
                            <li>{{ $t('token.website.click') }}</li>
                        </ol>
                    </div>
                    <div class="col-12 text-left">
                        <button
                            class="btn btn-primary"
                            @click="saveWebsite"
                        >
                            <font-awesome-icon
                                v-if="submitting"
                                icon="circle-notch"
                                spin
                                class="loading-spinner"
                                fixed-width
                            />
                            {{ $t('token.website.submit') }}
                        </button>
                        <span
                            class="btn-cancel pl-3 c-pointer"
                            @click="showConfirmWebsiteModal = false"
                        >
                            {{ $t('token.website.cancel') }}
                        </span>
                    </div>
                </div>
            </template>
        </modal>
    </div>
</template>

<script>
import {library} from '@fortawesome/fontawesome-svg-core';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import {faGlobe, faTimes, faCircleNotch} from '@fortawesome/free-solid-svg-icons';
import {BTooltip} from 'bootstrap-vue';
import {FiltersMixin, LoggerMixin, NotificationMixin} from '../../../mixins/';
import {isValidUrl} from '../../../utils';
import Modal from '../../modal/Modal';

library.add(faGlobe, faTimes, faCircleNotch);

export default {
    name: 'TokenWebsiteAddress',
    props: {
        currentWebsite: String,
        editingWebsite: Boolean,
        tokenName: String,
    },
    components: {
        BTooltip,
        FontAwesomeIcon,
        Modal,
    },
    mixins: [FiltersMixin, NotificationMixin, LoggerMixin],
    data() {
        return {
            confirmWebsiteFileUrl: this.$routing.generate('token_website_confirmation', {
                name: this.tokenName,
            }),
            confirmWebsiteUrl: this.$routing.generate('token_website_confirm', {
                name: this.tokenName,
            }),
            editing: this.editingWebsite,
            fileError: {},
            newWebsite: this.currentWebsite || 'https://',
            parsedWebsite: '',
            showConfirmWebsiteModal: false,
            showWebsiteError: false,
            submitting: false,
        };
    },
    watch: {
        editingWebsite: function() {
            this.submitting = false;
            this.editing = this.editingWebsite;
        },
        newWebsite: function() {
            this.fileError = {};
        },
    },
    computed: {
        computedWebsiteUrl: function() {
            return this.currentWebsite || this.$t('token.website.empty_address');
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
        translationsContext: function() {
            return {
                parsedWebsite: this.parsedWebsite,
            };
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
                        this.notifySuccess(response.data.message);
                        this.showConfirmWebsiteModal = false;
                        this.editing = false;
                        this.clearFileError();
                    } else if (response.data.errors.fileError) {
                        this.fileError = response.data.errors.fileError;
                    } else if (response.data.errors.length) {
                        response.data.errors.forEach((error) => {
                            this.notifyError(error);
                            this.sendLogs('error', 'Save website response data error', error);
                        });
                        this.clearFileError();
                    } else {
                        this.clearFileError();
                        return Promise.reject({response: 'error'});
                    }
                })
                .catch(({response}) => {
                    this.notifyError(!response ? this.$t('toasted.error.network') : response.statusText);
                    this.sendLogs('error', 'Save website network error', response);
                })
                .then(() => this.submitting = false);
        },
        closeFileErrorModal: function() {
            this.fileError = {};
            this.showConfirmWebsiteModal = true;
        },
        clearFileError: function() {
            this.fileError = {};
        },
        toggleEdit: function() {
            this.editing = !this.editing;
            if (this.editing) {
                this.$emit('toggleEdit', 'website');
            }
        },
    },
};
</script>
