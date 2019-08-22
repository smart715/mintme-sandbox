<template>
    <div>
        <div v-show="!editing">
            <div class="d-flex-inline" v-if="currentAddress">
                <div class="display-text">
                    Facebook:
                    <a :href="currentAddress" target="_blank" rel="nofollow">
                        {{ currentAddress }}
                    </a>
                    <div
                        class="fb-share-button"
                        :data-href="currentAddress"
                        data-layout="button_count"
                        data-size="small"
                        data-mobile-iframe="true">
                        <a
                            target="_blank"
                            :href="'https://www.facebook.com/sharer/sharer.php?u='
                            +currentAddressEncoded+'&amp;src=sdkpreparse'"
                            class="fb-xfbml-parse-ignore">
                        </a>
                    </div>
                    <guide>
                        <template slot="header">
                            Facebook
                        </template>
                        <template slot="body">
                            Link to token creator’s Facebook.
                            Before adding it, we confirmed ownership.
                        </template>
                    </guide>
                </div>
            </div>
        </div>
        <div v-show="editing">
            <div class=" d-block mx-0 my-1 p-0">
                <a class="c-pointer" @click="addPage" id="address-link">
                    <span class="token-introduction-profile-icon text-center d-inline-block">
                        <font-awesome-icon :icon="{prefix: 'fab', iconName: 'facebook-square'}" size="lg"/>
                    </span>
                    {{ computedAddress | truncate(35) }}
                </a>
                <b-tooltip v-if="currentAddress" target="address-link">
                    {{ computedAddress }}
                </b-tooltip>
                <a v-if="currentAddress" @click.prevent="deleteAddress">
                    <font-awesome-icon icon="times" class="text-danger c-pointer ml-2" />
                </a>
            </div>
        </div>
        <modal
            :visible="showConfirmModal"
            :no-close="true"
            @close="showConfirmModal = false">
            <template slot="body">
                <div class="row">
                    <div class="col-12">
                        <h3 class="modal-title text-center pb-2">Facebook page Confirmation</h3>
                        <div class="form-group">
                            <label for="select-fb-pages">Select Facebook page to show:</label>
                            <select v-model="selectedUrl" class="form-control" id="select-fb-pages">
                                <option
                                    v-for="(page, index) in pages"
                                    :selected="index === 0 ? 'selected' : ''"
                                    :key="page.id"
                                    :value="page.link">
                                    {{ page.name }}
                                </option>
                            </select>
                        </div>
                    </div>
                    <div class="col-12 text-center pt-2">
                        <button class="btn btn-primary" @click="savePage">Confirm</button>
                        <span class="btn-cancel c-pointer pl-3" @click="showConfirmModal = false">Cancel</span>
                    </div>
                </div>
            </template>
        </modal>
    </div>
</template>

<script>
import Toasted from 'vue-toasted';
import {library} from '@fortawesome/fontawesome-svg-core';
import {faTimes} from '@fortawesome/free-solid-svg-icons';
import {faFacebookSquare} from '@fortawesome/free-brands-svg-icons';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import {FiltersMixin} from '../../mixins';
import Guide from '../Guide';
import Modal from '../modal/Modal';

library.add(faFacebookSquare, faTimes);
Vue.use(Toasted, {
    position: 'top-center',
    duration: 5000,
});

const HTTP_ACCEPTED = 202;

export default {
    name: 'TokenFacebookAddress',
    props: {
        address: String,
        appId: String,
        editing: Boolean,
        tokenName: String,
    },
    components: {
        FontAwesomeIcon,
        Guide,
        Modal,
    },
    mixins: [FiltersMixin],
    created: function() {
        this.loadFacebookSdk();
    },
    data() {
        return {
            pages: [],
            currentAddress: this.address,
            showConfirmModal: false,
            selectedUrl: '',
            submitting: false,
            updateUrl: this.$routing.generate('token_update', {
                name: this.tokenName,
            }),
        };
    },
    computed: {
        currentAddressEncoded: function() {
            return encodeURIComponent(this.currentAddress);
        },
        computedAddress: function() {
            return this.currentAddress || 'Add Facebook address';
        },
    },
    mounted() {
        this.selectedUrl = this.pages.length ? this.pages[0].link : '';
    },
    methods: {
        loadFacebookSdk: function() {
            window.fbAsyncInit = () => {
                FB.init({
                    appId: this.appId,
                    autoLogAppEvents: true,
                    xfbml: true,
                    version: 'v3.1',
                });
            };

            (function(d, s, id) {
                let js; let fjs = d.getElementsByTagName(s)[0];
                if (d.getElementById(id)) {
                    return;
                }
                js = d.createElement(s); js.id = id;
                js.src = 'https://connect.facebook.net/en_US/sdk.js';
                fjs.parentNode.insertBefore(js, fjs);
            }(document, 'script', 'facebook-jssdk'));
        },
        addPage: function() {
            FB.login((response) => {
                if (response.status === 'connected') {
                    FB.api('/me/accounts?type=page&fields=name,link', (accountsData) => {
                        if (accountsData.error) {
                            this.$toasted.error('An error has ocurred, please try again later');
                            return;
                        }
                        this.pages = accountsData.data;
                        this.showConfirmModal = true;
                    });
                }
            }, {scope: 'pages_show_list'});
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
                if (response.status === HTTP_ACCEPTED) {
                    let state = this.selectedUrl ? `saved as ${this.currentAddress}` : 'deleted';
                    this.showConfirmModal = false;
                    this.currentAddress = this.selectedUrl;
                    this.$toasted.success(`Facebook page ${state}`);
                }
            }, (error) => {
                if (!error.response) {
                    this.$toasted.error('Network error');
                } else if (error.response.data.message) {
                    this.$toasted.error(error.response.data.message);
                } else {
                    this.$toasted.error('An error has occurred, please try again later');
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

<style lang="sass" scoped>
    .display-text
        display: inline-block
        width: 100%
        text-overflow: ellipsis
</style>
