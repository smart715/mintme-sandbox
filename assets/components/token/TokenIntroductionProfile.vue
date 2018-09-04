<template>
    <div>
        <div class="card">
            <div class="card-header">
                {{ this.profileName }}
                <span class="card-header-icon">
                    <font-awesome-icon
                        v-if="editable"
                        class="icon float-right"
                        size="2x"
                        :icon="icon"
                        transform="shrink-4 up-1.5"
                        @click="editUrls"
                    />
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
                                </div>
                                <div class="form-group" v-else>
                                    <label for="">Website address:</label>
                                    <input type="text" v-model="newWebsite" class="form-control" :class="{ 'is-invalid': showWebsiteError }" @keyup.enter="checkWebsiteUrl">
                                    <div class="invalid-feedback" v-if="showWebsiteError">
                                        Please provide a valid URL.
                                    </div>
                                </div>
                            </div>
                            <div class="pb-1">
                                <div v-if="!editingUrls">
                                    Facebook:
                                    <a :href="currentFacebook" target="_blank" rel="nofollow">
                                        {{ currentFacebook }}
                                    </a>
                                </div>
                                <div v-else>
                                    <button class="btn btn-primary" @click="addFacebookPage">
                                        <font-awesome-icon :icon="{prefix: 'fab', iconName: 'facebook-square'}" size="lg"/> 
                                        Add Facebook address
                                    </button>
                                </div>
                            </div>
                            <div>
                                <div v-if="!editingUrls">
                                    Youtube:
                                    <a :href="this.currentYoutube" target="_blank" rel="nofollow">
                                        {{ this.currentYoutube }}
                                    </a>
                                </div>
                                <div v-else>
                                    <button class="btn btn-primary" @click="addYoutubeChannel">
                                        <font-awesome-icon :icon="{prefix: 'fab', iconName: 'youtube-square'}" size="lg"/> 
                                        Add Youtube channel
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 pt-3 text-right">
                        <b-dropdown id="share" text="Share" variant="primary">
                            <b-dropdown-item
                                href="#"
                                target="_blank"
                            >
                                Facebook
                            </b-dropdown-item>
                            <b-dropdown-item
                                href="#"
                                target="_blank"
                            >
                                LinkedIn
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
                                    <li>Download <a :href="confirmWebsiteFileUrl" target="_blank">this html verification file</a></li>
                                    <li>Upload the file to {{ parsedWebsite }}</li>
                                    <li>Check if file was uploaded successfully by visiting <a :href="parsedWebsite+'/mintme.html'" target="_blank" rel="nofollow">{{ parsedWebsite }}/mintme.html</a></li>
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

        <div class="modal" :class="{ show: showConfirmFacebookModal }" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Facebook Confirmation</h5>
                        <button type="button" class="close" aria-label="Close" @click="showConfirmFacebookModal = false">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-12">
                                <div class="form-group">
                                    <label for="select-fb-pages">Select Facebook page to show:</label>
                                    <select v-model="selectedFacebookUrl" class="form-control" id="select-fb-pages">
                                        <option v-for="(page, index) in facebookPages" :selected="index === 0 ? 'selected' : ''" :key="page.id" :value="page.link">
                                            {{ page.name }}
                                        </option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-12 text-center">
                                <button class="btn btn-primary" @click="saveFacebookPage">Confirm</button>
                                <button class="btn btn-default" @click="showConfirmFacebookModal = false">Cancel</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import bDropdown from 'bootstrap-vue/es/components/dropdown/dropdown';
import bDropdownItem from 'bootstrap-vue/es/components/dropdown/dropdown-item';
import {library} from '@fortawesome/fontawesome-svg-core';
import {faEdit, faCheck} from '@fortawesome/free-solid-svg-icons';
import {faFacebookSquare, faYoutubeSquare} from '@fortawesome/free-brands-svg-icons';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import axios from 'axios';
import parse from 'url-parse';
import {isValidUrl} from '../../js/utils';
import Toasted from 'vue-toasted';
import rtrim from 'locutus/php/strings/rtrim';
import gapi from 'gapi';

library.add(faEdit, faCheck, faFacebookSquare, faYoutubeSquare);
Vue.use(Toasted, {
    position: 'top-center',
    duration: 5000,
});

const HTTP_NO_CONTENT = 204;
const HTTP_BAD_REQUEST = 400;

const CLIENT_ID = '534504280780-ar9kkjo2tvuse9nd949b3nl5o3aitrpv.apps.googleusercontent.com';
const DISCOVERY_DOCS = ["https://www.googleapis.com/discovery/v1/apis/youtube/v3/rest"];
const SCOPES = 'https://www.googleapis.com/auth/youtube.readonly';

function loadFacebookSdk() {
    window.fbAsyncInit = function() {
        FB.init({
            appId            : '306428486604341',
            autoLogAppEvents : true,
            xfbml            : true,
            version          : 'v3.1'
        });
    };

    (function(d, s, id){
        var js, fjs = d.getElementsByTagName(s)[0];
        if (d.getElementById(id)) {return;}
        js = d.createElement(s); js.id = id;
        js.src = "https://connect.facebook.net/en_US/sdk.js";
        fjs.parentNode.insertBefore(js, fjs);
    }(document, 'script', 'facebook-jssdk'));
}

function loadYoutubeClient() {
    gapi.load('client:auth2', initYoutubeClient);
}

function initYoutubeClient() {
    gapi.client.init({
        discoveryDocs: DISCOVERY_DOCS,
        clientId: CLIENT_ID,
        scope: SCOPES,
    });
}

export default {
    name: 'TokenIntroductionProfile',
    props: {
        confirmWebsiteFileUrl: String,
        confirmWebsiteUrl: String,
        profileName: String,
        websiteUrl: String,
        facebookUrl: String,
        youtubeUrl: String,
        updateUrl: String,
        csrfToken: String,
        editable: Boolean,
    },
    components: {
        bDropdown,
        bDropdownItem,
        FontAwesomeIcon,
    },
    created: function() {
        if (this.editable) {
            loadFacebookSdk();
            loadYoutubeClient();
        }
    },
    data() {
        return {
            editingUrls: false,
            currentWebsite: this.websiteUrl,
            newWebsite: this.websiteUrl,
            facebookPages: [],
            currentFacebook: this.facebookUrl,
            currentYoutube: this.youtubeUrl,
            icon: 'edit',
            showConfirmWebsiteModal: false,
            showConfirmFacebookModal: false,
            showWebsiteError: false,
            parsedWebsite: '',
        };
    },
    computed: {
        selectedFacebookUrl: function() {
            if (this.facebookPages[0])
                return this.facebookPages[0].link;
            else
                return '';
        }
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
            axios.post(this.confirmWebsiteUrl, { url: this.parsedWebsite })
                .then((response) => {
                    if (response.data.verified) {
                        this.currentWebsite = this.parsedWebsite;
                        this.$toasted.success('Website confirmed successfully');
                    }
                    else if (response.data.errors.length) {
                        response.data.errors.forEach(error => {
                            this.$toasted.error(error);
                        });
                        this.newWebsite = this.currentWebsite;
                    }
                    else {
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
        addFacebookPage: function() {
            console.log('addFacebookPage');
            FB.login((response) => {
                console.log(response);
                if (response.status === 'connected') {
                    FB.api('/me/accounts?type=page&fields=name,link', (accountsData) => {
                        console.log(accountsData);
                        this.facebookPages = accountsData.data;
                        this.showConfirmFacebookModal = true;
                    });
                }
            }, { scope: 'pages_show_list' });
        },
        saveFacebookPage: function() {
            axios.patch(this.updateUrl, {
                facebookUrl: this.selectedFacebookUrl,
                _csrf_token: this.csrfToken,
            })
            .then((response) => {
                if (response.status === HTTP_NO_CONTENT) {
                    this.currentFacebook = this.selectedFacebookUrl;
                    this.$toasted.success(`Facebook paged saved as ${this.currentFacebook}`);
                }
            }, (error) => {
                if (error.response.status === HTTP_BAD_REQUEST) {
                    this.$toasted.error(error.response.data[0][0].message);
                }
                else {
                    this.$toasted.error('An error has ocurred, please try again later');
                }
            })
            .then(() => {
                this.showConfirmFacebookModal = false;
            });
        },
        addYoutubeChannel: function() {
            console.log('addYoutubeChannel');
            this.signInYoutube()
                .then(() => {
                    return this.getYoutubeAddress();
                })
                .then((channelId) => {
                    let youtubeChannel = 'https://www.youtube.com/channel/'+channelId;
                    axios.patch(this.updateUrl, {
                        youtubeUrl: youtubeChannel,
                        _csrf_token: this.csrfToken,
                    })
                    .then((response) => {
                        if (response.status === HTTP_NO_CONTENT) {
                            this.currentYoutube = youtubeChannel;
                            this.$toasted.success(`Youtube channel saved as ${youtubeChannel}`);
                        }
                    }, (error) => {
                        if (error.response.status === HTTP_BAD_REQUEST) {
                            this.$toasted.error(error.response.data[0][0].message);
                        }
                        else {
                            this.$toasted.error('An error has ocurred, please try again later');
                        }
                    });
                });

        },
        signInYoutube: function() {
            return gapi.auth2.getAuthInstance().signIn();
        },
        getYoutubeAddress: function() {
            return new Promise((resolve, reject) => {
                gapi.client.youtube.channels.list({
                    part: 'id',
                    mine: true,
                }).then((response) => {
                    let channel = response.result.items[0];
                    resolve(channel.id);
                });
            });
        }
    },
};
</script>
