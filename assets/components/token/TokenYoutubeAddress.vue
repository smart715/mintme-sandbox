<template>
    <div>
        <div v-show="!editing">
            <div class="d-flex">
                <div class="display-text">
                    Youtube:
                    <a
                        v-if="currentChannelId"
                        :href="'https://www.youtube.com/channel/'+this.currentChannelId"
                        target="_blank"
                        rel="nofollow">
                        https://www.youtube.com/channel/{{ this.currentChannelId }}
                    </a>
                    <div
                        v-if="currentChannelId"
                        class="g-ytsubscribe"
                        :data-channelid="currentChannelId"
                        data-layout="default"
                        data-count="default">
                    </div>
                    <guide>
                        <template slot="header">
                            Youtube
                        </template>
                        <template slot="body">
                            Link to token creator’s YouTube. Before adding it, we confirmed ownership.
                        </template>
                    </guide>
                </div>
            </div>
        </div>
        <div v-show="editing">
            <div class="d-block mx-0 my-1 p-0">
                <a class="c-pointer" @click="addChannel">
                    <font-awesome-icon
                        :icon="{prefix: 'fab', iconName: 'youtube-square'}"
                        size="lg"/>
                    Add Youtube channel
                </a>
            </div>
        </div>
    </div>
</template>

<script>
import {library} from '@fortawesome/fontawesome-svg-core';
import {faYoutubeSquare} from '@fortawesome/free-brands-svg-icons';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import gapi from 'gapi';
import Guide from '../Guide';

library.add(faYoutubeSquare);

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
    },
    components: {
        FontAwesomeIcon,
        Guide,
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
                .then(() => this.getChannelId().then((channelId) => {
                    this.$axios.single.patch(this.updateUrl, {
                        youtubeChannelId: channelId,
                    }).then((response) => {
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
                }));
        },
        signInYoutube: function() {
            let options = new gapi.auth2.SigninOptionsBuilder();

            options.setPrompt('select_account');

            return gapi.auth2.getAuthInstance().signIn(options);
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
        text-overflow: ellipsis
</style>
