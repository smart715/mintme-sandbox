<template>
    <div>
        <div v-show="!editing">
            <div class="d-flex">
                <div class="display-text">
                    Youtube:
                    <a :href="'https://www.youtube.com/channel/'+this.currentChannelId" target="_blank" rel="nofollow">
                        https://www.youtube.com/channel/{{ this.currentChannelId }}
                    </a>
                </div>
                <div class="g-ytsubscribe" :data-channelid="currentChannelId" data-layout="default" data-count="default"></div>
            </div>
        </div>
        <div v-show="editing">
            <button class="btn btn-primary" @click="addChannel">
                <font-awesome-icon :icon="{prefix: 'fab', iconName: 'youtube-square'}" size="lg"/>
                Add Youtube channel
            </button>
        </div>
    </div>
</template>

<script>
import {library} from '@fortawesome/fontawesome-svg-core';
import {faYoutubeSquare} from '@fortawesome/free-brands-svg-icons';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import axios from 'axios';
import Toasted from 'vue-toasted';
import gapi from 'gapi';

library.add(faYoutubeSquare);
Vue.use(Toasted, {
    position: 'top-center',
    duration: 5000,
});

const HTTP_NO_CONTENT = 204;
const HTTP_BAD_REQUEST = 400;

const DISCOVERY_DOCS = ['https://www.googleapis.com/discovery/v1/apis/youtube/v3/rest'];
const SCOPES = 'https://www.googleapis.com/auth/youtube.readonly';

export default {
    name: 'TokenYoutubeAddress',
    props: {
        clientId: String,
        editable: Boolean,
        editing: Boolean,
        channelId: String,
        updateUrl: String,
        csrfToken: String,
    },
    components: {
        FontAwesomeIcon,
    },
    created: function() {
        if (this.editable) {
this.loadYoutubeClient();
}
    },
    data() {
        return {
            currentChannelId: this.channelId,
        };
    },
    methods: {
        loadYoutubeClient: function() {
            gapi.load('client:auth2', this.initYoutubeClient);
        },
        initYoutubeClient: function() {
            gapi.client.init({
                discoveryDocs: DISCOVERY_DOCS,
                clientId: this.clientId,
                scope: SCOPES,
            });
        },
        addChannel: function() {
            this.signInYoutube()
                .then(() => {
                    return this.getChannelId();
                })
                .then((channelId) => {
                    axios.patch(this.updateUrl, {
                        youtubeChannelId: channelId,
                        _csrf_token: this.csrfToken,
                    })
                    .then((response) => {
                        if (response.status === HTTP_NO_CONTENT) {
                            this.currentChannelId = channelId;
                            this.$toasted.success(`Youtube channel saved as https://youtube.com/channel/${channelId}`);
                        }
                    }, (error) => {
                        if (error.response.status === HTTP_BAD_REQUEST) {
                            this.$toasted.error(error.response.data[0][0].message);
                        } else {
                            this.$toasted.error('An error has ocurred, please try again later');
                        }
                    });
                });
        },
        signInYoutube: function() {
            return gapi.auth2.getAuthInstance().signIn();
        },
        getChannelId: function() {
            return new Promise((resolve, reject) => {
                gapi.client.youtube.channels.list({
                    part: 'id',
                    mine: true,
                }).then((response) => {
                    let channel = response.result.items[0];
                    resolve(channel.id);
                });
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
        text-overflow: ellipsis</style>
