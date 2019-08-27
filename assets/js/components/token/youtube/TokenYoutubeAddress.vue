<template>
    <div>
        <div class="d-block mx-0 my-1 p-0">
            <a
                id="channel-link"
                class="c-pointer"
                @click="addChannel"
            >
                <span class="token-introduction-profile-icon text-center d-inline-block">
                    <font-awesome-icon
                        :icon="{prefix: 'fab', iconName: 'youtube-square'}"
                        size="lg"
                    />
                </span>
                {{ computedChannel | truncate(35) }}
            </a>
            <b-tooltip
                v-if="currentChannelId"
                target="channel-link"
                :title="computedChannel"
            />
            <a
                v-if="currentChannelId"
                @click.prevent="deleteChannel"
            >
                <font-awesome-icon
                    icon="times"
                    class="text-danger c-pointer ml-2"
                />
            </a>
        </div>
    </div>
</template>

<script>
import {library} from '@fortawesome/fontawesome-svg-core';
import {faTimes} from '@fortawesome/free-solid-svg-icons';
import {faYoutubeSquare} from '@fortawesome/free-brands-svg-icons';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import {FiltersMixin} from '../../../mixins';
import gapi from 'gapi';
import Guide from '../../Guide';

library.add(faTimes, faYoutubeSquare);

const HTTP_ACCEPTED = 202;

const DISCOVERY_DOCS = ['https://www.googleapis.com/discovery/v1/apis/youtube/v3/rest'];
const SCOPES = 'https://www.googleapis.com/auth/youtube.readonly';

export default {
    name: 'TokenYoutubeAddress',
    props: {
        channelId: String,
        clientId: String,
        editable: Boolean,
        tokenName: String,
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
            submitting: false,
            updateUrl: this.$routing.generate('token_update', {
                name: this.tokenName,
            }),
        };
    },
    computed: {
        computedChannel: function() {
            return this.currentChannelId || 'Add Youtube channel';
        },
    },
    methods: {
        buildYoutubeUrl: function(id) {
            return 'https://www.youtube.com/channel/' + id;
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
                .then(() => this.getChannelId()
                    .then((channelId) => {
                        this.saveYoutubeChannel(channelId);
                    }), (error) => {
                        this.$toasted.info('Operation canceled');
                    });
        },
        deleteChannel: function() {
            this.saveYoutubeChannel('');
        },
        saveYoutubeChannel: function(channelId) {
            if (this.submitting) {
                return;
            }

            this.submitting = true;
            this.$axios.single.patch(this.updateUrl, {
                youtubeChannelId: channelId,
            })
                .then((response) => {
                    if (response.status === HTTP_ACCEPTED) {
                        let message = channelId
                            ? `Youtube channel saved as ${this.buildYoutubeUrl(channelId)}`
                            : 'Youtube channel deleted';
                        this.$toasted.success(message);
                        this.currentChannelId = channelId;
                        this.$emit('saveYoutube', channelId);
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
                    this.submitting = false;
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
