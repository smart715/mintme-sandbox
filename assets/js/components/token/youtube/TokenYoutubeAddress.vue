<template>
    <div class="row">
        <div
            class="col text-truncate"
            :class="{'token-youtube-address': isAirdrop}"
        >
            <span
                id="channel-link"
                @click="addChannel"
            >
                <span
                    class="token-introduction-profile-icon text-white text-center d-inline-block c-pointer mr-2"
                >
                    <font-awesome-icon
                        :icon="{prefix: 'fab', iconName: 'youtube'}"
                        class="ml-n2 text-white"
                        transform="right-3 down-1 shrink-1"
                        size="lg"
                        fixed-width
                    />
                </span>
                <p class="text-reset text-nowrap d-inline link highlight" tabindex="0">
                    {{ computedChannel }}
                </p>
            </span>
        </div>
        <div class="col-auto">
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
import {FiltersMixin, NotificationMixin} from '../../../mixins';
import gapi from 'gapi';
import {HTTP_OK, projectName} from '../../../utils/constants';

library.add(faTimes, faYoutubeSquare);

const DISCOVERY_DOCS = ['https://www.googleapis.com/discovery/v1/apis/youtube/v3/rest'];
const SCOPES = 'https://www.googleapis.com/auth/youtube.readonly';

export default {
    name: 'TokenYoutubeAddress',
    components: {
        FontAwesomeIcon,
    },
    mixins: [
        FiltersMixin,
        NotificationMixin,
    ],
    props: {
        channelId: String,
        clientId: String,
        editable: Boolean,
        tokenName: String,
        isAirdrop: Boolean,
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
            return this.currentChannelId
                ? 'https://www.youtube.com/channel/' + this.currentChannelId
                : this.$t('token.youtube.empty_address');
        },
        translationsContext: function() {
            return {
                address: this.buildYoutubeUrl(this.currentChannelId),
            };
        },
    },
    created: function() {
        if (this.editable) {
            this.loadYoutubeClient();
        }
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
                plugin_name: projectName,
            });
        },
        addChannel: function() {
            this.signInYoutube()
                .then(() => this.getChannelId()
                    .then((channelId) => {
                        this.saveYoutubeChannel(channelId);
                    }), (error) => {
                    this.notifyInfo(this.$t('toasted.info.operation_canceled'));
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
                needToCheckCode: false,
            })
                .then((response) => {
                    if (response.status === HTTP_OK) {
                        this.currentChannelId = channelId;
                        this.notifySuccess(
                            this.$t(
                                'toasted.success.youtube.' + (this.currentChannelId ? 'added' : 'deleted'),
                                this.translationsContext
                            )
                        );
                        this.$emit('saveYoutube', channelId);
                    }
                }, (error) => {
                    if (!error.response) {
                        this.notifyError(this.$t('toasted.error.network'));
                        this.$logger.error('Save YouTube channel network error', error);
                    } else if (error.response.data.message) {
                        this.notifyError(error.response.data.message);
                        this.$logger.error('Can not save YouTube channel', error);
                    } else {
                        this.notifyError(this.$t('toasted.error.try_later'));
                        this.$logger.error('An error has occurred, please try again later', error);
                    }
                })
                .then(() => {
                    this.submitting = false;
                });
        },
        signInYoutube: function() {
            const options = new gapi.auth2.SigninOptionsBuilder();

            options.setPrompt('select_account');

            return gapi.auth2.getAuthInstance().signIn(options);
        },
        getChannelId: function() {
            return new Promise((resolve, reject) => {
                gapi.client.youtube.channels.list({
                    part: 'id',
                    mine: true,
                }).then((response) => {
                    const channel = response.result.items[0];
                    resolve(channel.id);
                });
            });
        },
    },
};
</script>
