<template>
    <div class="row">
        <div
            v-if="editing"
            class="form-group col-12"
        >
            <m-input
                v-model="newWebsite"
                :invalid="showWebsiteError"
                :label="$t('token.website.label')"
            >
                <template v-slot:assistive-postfix>
                    <div :class="{'text-danger' : showWebsiteLengthError}">
                       {{ newWebsite.length }} / {{ websiteMaxLength }}
                    </div>
                </template>
                <template v-slot:errors>
                    <div v-if="showWebsiteLengthError">
                        {{ $t('token.website.max_length_url', translationContext) }}
                    </div>
                    <div v-if="showWebsiteError">
                        {{ $t('token.website.invalid_url') }}
                    </div>
                </template>
            </m-input>
            <div class="col-12 text-left px-0 d-flex align-items-center">
                <m-button
                    :disabled="disableConfirmButton"
                    type="primary"
                    :loading="submitting"
                    @click="editWebsite"
                >
                    {{ $t('token.website.submit') }}
                </m-button>
                <m-button
                    type="link"
                    class="ml-2"
                    @click="editing = false"
                >
                    {{ $t('token.website.cancel') }}
                </m-button>
            </div>
        </div>
        <div
            v-else
            class="col text-truncate"
            @click.prevent="toggleEdit"
        >
            <span
                class="token-introduction-profile-icon text-center text-white d-inline-block c-pointer mr-1"
            >
                <font-awesome-icon
                    icon="globe"
                    size="lg"
                    class="text-white ml-n1"
                    fixed-width
                />
            </span>
            <span
                id="website-link"
            >
                <p class="ttext-reset text-nowrap d-inline link highlight" tabindex="0">
                    {{ computedWebsiteUrl | truncate(addWebsiteTruncateLength) }}
                </p>
            </span>
        </div>
        <div class="col-auto" v-if="!editing">
            <a
                v-if="currentWebsite && !submitting"
                @click.prevent="deleteWebsite"
            >
                <font-awesome-icon
                    icon="times"
                    class="text-danger c-pointer ml-2"
                />
            </a>
            <div v-if="submitting" class="spinner-border spinner-border-sm" role="status"></div>
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
                            class="link highlight"
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
                                    class="highlight link"
                                >
                                    {{ $t('token.website.download_2') }}
                                </a>
                            </li>
                            <li>
                                {{ $t('token.website.upload') }}
                                <span
                                    id="website-upload"
                                    class="word-break-all"
                                >
                                    {{ parsedWebsite | truncate(websiteTruncateLength) }}
                                </span>
                                <b-tooltip
                                    placement="top"
                                    fallback-placement="clockwise"
                                    boundary="document"
                                    target="website-upload"
                                    custom-class="tooltip-website"
                                    :title="parsedWebsite"
                                    :disabled="disabledTooltip"
                                />
                            </li>
                            <li>
                                {{ $t('token.website.check') }}
                                <a
                                    id="website-check"
                                    :href="siteRequestUrl"
                                    target="_blank"
                                    rel="nofollow"
                                    class="highlight link text-break"
                                >
                                    {{ siteRequestUrl | truncate(websiteTruncateLength) }}
                                </a>
                                <b-tooltip
                                    placement="top"
                                    fallback-placement="clockwise"
                                    boundary="document"
                                    target="website-check"
                                    custom-class="tooltip-website"
                                    :title="siteRequestUrl"
                                    :disabled="disabledTooltip"
                                />
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
import {FiltersMixin, NotificationMixin} from '../../../mixins/';
import {isValidUrl} from '../../../utils';
import Modal from '../../modal/Modal';
import {MInput, MButton} from '../../UI';

library.add(faGlobe, faTimes, faCircleNotch);

const WEBSITE_MAX_LENGTH = 2048;
const WEBSITE_TRUNCATE_LENGTH = 85;
const ADD_WEBSITE_TRUNCATE_LENGTH = 60;

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
        MInput,
        MButton,
    },
    mixins: [FiltersMixin, NotificationMixin],
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
            websiteTruncateLength: WEBSITE_TRUNCATE_LENGTH,
            addWebsiteTruncateLength: ADD_WEBSITE_TRUNCATE_LENGTH,
            websiteMaxLength: WEBSITE_MAX_LENGTH,
        };
    },
    watch: {
        editingWebsite: function() {
            this.submitting = false;
            this.editing = this.editingWebsite;
        },
        newWebsite: function() {
            this.fileError = {};
            this.validateWebsiteUrl();
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
        disabledTooltip: function() {
            return this.parsedWebsite.length < this.websiteTruncateLength;
        },
        translationContext() {
            return {
                maxWebsiteLength: this.websiteMaxLength,
            };
        },
        showWebsiteLengthError: function() {
            return this.newWebsite.length > this.websiteMaxLength;
        },
        disableConfirmButton: function() {
            return this.showWebsiteLengthError || this.showWebsiteError;
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
        validateWebsiteUrl: function() {
            this.showWebsiteError = false;

            if (!isValidUrl(this.newWebsite)) {
                this.showWebsiteError = true;
                return;
            }
        },
        checkWebsiteUrl: function() {
            this.validateWebsiteUrl();

            if (this.showWebsiteError || this.showWebsiteLengthError) {
                return;
            }

            this.parsedWebsite = this.newWebsite.replace(/\/+$/, '');
            this.showConfirmWebsiteModal = true;
        },
        deleteWebsite: function() {
            this.newWebsite = '';
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
                            this.$logger.error('Save website response data error', error);
                        });
                        this.clearFileError();
                    } else {
                        this.clearFileError();
                        return Promise.reject({response: 'error'});
                    }
                })
                .catch(({response}) => {
                    this.notifyError(
                        response
                            ? (response.data.message ? response.data.message : response.statusText)
                            : this.$t('toasted.error.network')
                    );
                    this.$logger.error('Save website network error', response);
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
