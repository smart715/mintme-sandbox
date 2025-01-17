<template>
    <div class="row">
        <div
            class="col text-truncate"
            :class="{'token-facebook-address': isAirdrop}"
        >
            <span
                id="address-link"
                @click="addPage"
            >
                <span
                    class="token-introduction-profile-icon text-white text-center d-inline-block c-pointer mr-2"
                >
                    <font-awesome-icon
                        v-if="isAirdrop"
                        :icon="{prefix: 'fab', iconName: 'facebook-f'}"
                        size="lg"
                        transform="right-3 down-1 shrink-1"
                        class="text-white"
                    />
                    <font-awesome-icon
                        v-else
                        :icon="{prefix: 'fab', iconName: 'facebook-square'}"
                        size="lg"
                        class="text-white"
                    />
                </span>
                <span class="text-reset text-nowrap d-inline link highlight" tabindex="0">
                    {{ computedAddress }}
                </span>
                <font-awesome-icon
                    v-if="submitting"
                    icon="circle-notch"
                    spin
                    class="loading-spinner"
                    fixed-width
                />
            </span>
        </div>
        <div class="col-auto">
            <a
                v-if="address"
                @click.prevent="deleteAddress"
            >
                <font-awesome-icon
                    icon="times"
                    class="text-danger c-pointer ml-2"
                />
            </a>
        </div>
        <modal
            :visible="showConfirmModal"
            :no-close="true"
            @close="showConfirmModal = false">
            <template slot="body">
                <div class="row">
                    <div class="col-12">
                        <h3 class="modal-title text-center pb-2">
                            {{ $t('token.facebook.confirmation_title') }}
                        </h3>
                        <div class="form-group">
                            <label for="select-fb-pages">
                                {{ $t('token.facebook.select_label') }}
                            </label>
                            <select
                                id="select-fb-pages"
                                v-model="selectedUrl"
                                class="form-control"
                            >
                                <option
                                    v-for="(page, index) in pages"
                                    :selected="index === 0 ? 'selected' : ''"
                                    :key="page.id"
                                    :value="page.link"
                                >
                                    {{ page.name }}
                                </option>
                            </select>
                        </div>
                    </div>
                    <div class="col-12 text-center pt-2">
                        <button
                            class="btn btn-primary"
                            @click="savePage"
                        >
                            {{ $t('token.facebook.submit') }}
                        </button>
                        <span
                            class="btn-cancel c-pointer pl-3"
                            @click="showConfirmModal = false"
                        >
                            {{ $t('token.facebook.cancel') }}
                        </span>
                    </div>
                </div>
            </template>
        </modal>
    </div>
</template>

<script>
import {library} from '@fortawesome/fontawesome-svg-core';
import {faTimes} from '@fortawesome/free-solid-svg-icons';
import {faFacebookSquare} from '@fortawesome/free-brands-svg-icons';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import {FiltersMixin, NotificationMixin} from '../../../mixins';
import Modal from '../../modal/Modal';
import {HTTP_OK} from '../../../utils/constants';

library.add(faFacebookSquare, faTimes);

export default {
    name: 'TokenFacebookAddress',
    components: {
        FontAwesomeIcon,
        Modal,
    },
    mixins: [
        FiltersMixin,
        NotificationMixin,
    ],
    props: {
        address: String,
        tokenName: String,
        isAirdrop: Boolean,
    },
    data() {
        return {
            pages: [],
            selectedUrl: '',
            showConfirmModal: false,
            submitting: false,
            updateUrl: this.$routing.generate('token_update', {
                name: this.tokenName,
            }),
        };
    },
    computed: {
        computedAddress: function() {
            return this.address || this.$t('token.facebook.empty_address');
        },
    },
    mounted() {
        this.selectedUrl = this.pages.length ? this.pages[0].link : '';
    },
    methods: {
        addPage: async function() {
            if (this.submitting) {
                return;
            }

            this.submitting = true;
            let {status} = await this.getLoginStatus();
            if ('connected' !== status) {
                ({status} = await this.login());
            }

            if ('connected' !== status) {
                return;
            }

            FB.api('/me/accounts?type=page&fields=name,link', (accountsData) => {
                if (accountsData.error) {
                    this.notifyError(this.$t('toasted.error.try_later'));
                    this.$logger.error('An error has occurred, please try again later', accountsData.error);
                    return;
                }
                this.pages = accountsData.data;
                this.showConfirmModal = true;
                this.submitting = false;
            });
        },
        getLoginStatus: function() {
            return new Promise((resolve) => FB.getLoginStatus((res) => resolve(res)));
        },
        login: function() {
            return new Promise((resolve) => FB.login((res) => resolve(res), {scope: 'pages_show_list'}));
        },
        savePage: function() {
            this.saveFacebookAddress();
        },
        deleteAddress: function() {
            this.selectedUrl = '';
            this.saveFacebookAddress();
        },
        saveFacebookAddress: function() {
            if (this.submitting) {
                return;
            }

            this.submitting = true;
            this.$axios.single.patch(this.updateUrl, {
                facebookUrl: this.selectedUrl,
            })
                .then((response) => {
                    if (response.status === HTTP_OK) {
                        const state = this.selectedUrl ? `added` : 'deleted';
                        this.notifySuccess(this.$t(
                            'toasted.success.facebook.' + state,
                            {address: this.selectedUrl}
                        ));
                        this.$emit('saveFacebook', this.selectedUrl);
                    }
                }, (error) => {
                    if (!error.response) {
                        this.notifyError(this.$t('toasted.error.network'));
                        this.$logger.error('Save facebook address network error', error);
                    } else if (error.response.data.message) {
                        this.notifyError(error.response.data.message);
                        this.$logger.error('Can not save facebook', error);
                    } else {
                        this.notifyError(this.$t('toasted.error.try_later'));
                        this.$logger.error('An error has occurred, please try again later', error);
                    }
                })
                .then(() => {
                    this.showConfirmModal = false;
                    this.submitting = false;
                });
        },
    },
};
</script>
