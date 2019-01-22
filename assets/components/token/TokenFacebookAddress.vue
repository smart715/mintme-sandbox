<template>
    <div>
        <div v-show="!editing">
            <div class="d-flex">
                <div class="display-text">
                    Facebook:
                    <a :href="currentAddress" target="_blank" rel="nofollow">
                        {{ currentAddress }}
                    </a>
                    <div
                        class="fb-share-button"
                        data-href="https://developers.facebook.com/docs/plugins/"
                        data-layout="button_count"
                        data-size="small"
                        data-mobile-iframe="true">
                        <a
                            target="_blank"
                            :href="'https://www.facebook.com/sharer/sharer.php?u='
                            +currentAddressEncoded+'&amp;src=sdkpreparse'"
                            class="fb-xfbml-parse-ignore">
                            Share
                    </a>
                </div>
                <guide>
                    <font-awesome-icon
                        icon="question"
                        slot='icon'
                        class="ml-1 bg-primary text-white
                        rounded-circle square blue-question"/>
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
        <div class="col-lg-6 col-md-9 d-block mx-0 my-1 p-0">
            <button class="btn btn-primary btn-block custom-social-btn" @click="addPage">
                <font-awesome-icon :icon="{prefix: 'fab', iconName: 'facebook-square'}" size="lg"/>
                    Add Facebook address
        </button>
    </div>
</div>

<div class="modal" :class="{ show: showConfirmModal }" tabindex="-1" role="dialog">
     <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Facebook Confirmation</h5>
                <button type="button" class="close" aria-label="Close" @click="showConfirmModal = false">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-12">
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
                    <div class="col-12 text-center">
                        <button class="btn btn-primary" @click="savePage">Confirm</button>
                        <button class="btn btn-default" @click="showConfirmModal = false">Cancel</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
</template>

<script>
import axios from 'axios';
import Toasted from 'vue-toasted';
import {library} from '@fortawesome/fontawesome-svg-core';
import {faFacebookSquare} from '@fortawesome/free-brands-svg-icons';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import Guide from '../Guide';

library.add(faFacebookSquare);
Vue.use(Toasted, {
    position: 'top-center',
    duration: 5000,
});

const HTTP_NO_CONTENT = 204;
const HTTP_BAD_REQUEST = 400;

export default {
    name: 'TokenFacebookAddress',
    props: {
        appId: String,
        editing: Boolean,
        address: String,
        updateUrl: String,
        csrfToken: String,
    },
    components: {
        FontAwesomeIcon,
        Guide,
    },
    created: function() {
        this.loadFacebookSdk();
    },
    data() {
        return {
            pages: [],
            currentAddress: this.address,
            showConfirmModal: false,
        };
    },
    computed: {
        selectedUrl: function() {
            if (this.pages.length) {
                return this.pages[0].link;
            } else {
                return '';
            }
        },
        currentAddressEncoded: function() {
            return encodeURIComponent(this.currentAddress);
        },
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
            axios.patch(this.updateUrl, {
                facebookUrl: this.selectedUrl,
                _csrf_token: this.csrfToken,
            })
            .then((response) => {
                if (response.status === HTTP_NO_CONTENT) {
                    this.currentAddress = this.selectedUrl;
                    this.$toasted.success(`Facebook paged saved as ${this.currentAddress}`);
                }
            }, (error) => {
                if (error.response.status === HTTP_BAD_REQUEST) {
                    this.$toasted.error(error.response.data[0][0].message);
                } else {
                    this.$toasted.error('An error has ocurred, please try again later');
                }
            })
            .then(() => {
                this.showConfirmFacebookModal = false;
            });
        },
    },
};
</script>

<style lang="sass" scoped>
    .display-text
        display: inline-block
        width: 100%
        white-space: nowrap
        overflow: hidden
        text-overflow: ellipsis
</style>
