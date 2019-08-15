<template>
    <div>
        <div v-show="!editing">
            <div class="d-flex-inline" v-if="currentChannelId">
                <div class="display-text">
                    Youtube:
                    <a
                        :href="youTubeUrl"
                        target="_blank"
                        rel="nofollow">
                        {{ youTubeUrl }}
                    </a>
                    <div ref="ytButtonContainer" class="d-block-inline"></div>
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
                <a class="c-pointer" @click="addChannel" id="channel-link">
                    <span class="token-introduction-profile-icon text-center d-inline-block">
                        <font-awesome-icon :icon="{prefix: 'fab', iconName: 'youtube-square'}" size="lg"/>
                    </span>
                    {{ computedChannel | truncate(35) }}
                </a>
                <b-tooltip v-if="currentChannelId" target="channel-link">
                    {{ computedChannel }}
                </b-tooltip>
                <a v-if="currentChannelId" @click.prevent="deleteChannel">
                    <font-awesome-icon icon="times" class="text-danger c-pointer ml-2" />
                </a>
            </div>
        </div>
    </div>
</template>

<script>
import {library} from '@fortawesome/fontawesome-svg-core';
import {faTimes} from '@fortawesome/free-solid-svg-icons';
import {faYoutubeSquare} from '@fortawesome/free-brands-svg-icons';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import {FiltersMixin} from '../../mixins';
import gapi from 'gapi';
import Guide from '../Guide';

library.add(faYoutubeSquare, faTimes);

const HTTP_ACCEPTED = 202;

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
    mixins: [FiltersMixin],
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
    computed: {
        youTubeUrl: function() {
            return this.buildYoutubeUrl(this.currentChannelId);
        },
        computedChannel: function() {
            return this.currentChannelId || 'Add Youtube channel';
        },
    },
    mounted() {
        if (this.currentChannelId) {
            this.renderYtSubscribeButton(this.currentChannelId);
        }
    },
    methods: {
        buildYoutubeUrl: function(id) {
            return 'https://www.youtube.com/channel/' + id;
        },
        renderYtSubscribeButton: function(channelId) {
            let options = {
                'channelid': channelId,
            };
            gapi.ytsubscribe.render(this.$refs.ytButtonContainer, options);
        },
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
                    this.requestForYoutubeChannel(channelId);
                }), (error) => {
                    this.$toasted.info('Operation canceled');
                });
        },
        deleteChannel: function() {
            this.requestForYoutubeChannel('');
        },
        requestForYoutubeChannel: function(channelId) {
            this.$axios.single.patch(this.updateUrl, {
                    youtubeChannelId: channelId,
                }).then((response) => {
                    if (response.status === HTTP_ACCEPTED) {
                        let state = channelId ? `saved as ${this.buildYoutubeUrl(channelId)}` : 'deleted';
                        this.currentChannelId = channelId;
                        this.$toasted.success(`Youtube channel ${state}`);
                        this.renderYtSubscribeButton(channelId);
                    }
                }, (error) => {
                    if (!error.response) {
                        this.$toasted.error('Network error');
                    } else if (error.response.data.message) {
                        this.$toasted.error(error.response.data.message);
                    } else {
                        this.$toasted.error('An error has occurred, please try again later');
                    }
                });
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
        text-overflow: ellipsis
</style>
