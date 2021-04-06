<template>
    <div class="row">
        <div class="col text-truncate">
            <span
                id="channel-link"
                class="c-pointer text-white hover-icon"
                @click="addChannel"
            >
                <span class="token-introduction-profile-icon text-center d-inline-block">
                    <font-awesome-icon
                        :icon="{prefix: 'fab', iconName: 'youtube-square'}"
                        size="lg"
                    />
                </span>
                <a href="#" class="text-reset text-nowrap">
                    {{ computedChannel }}
                </a>
            </span>
            <b-tooltip
                v-if="currentChannelId"
                target="channel-link"
                :title="computedChannel"
            />
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
import {FiltersMixin, LoggerMixin, NotificationMixin} from '../../../mixins';
import gapi from 'gapi';
import Guide from '../../Guide';
import {HTTP_OK} from '../../../utils/constants';

library.add(faTimes, faYoutubeSquare);

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
    mixins: [FiltersMixin, NotificationMixin, LoggerMixin],
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
                        this.sendLogs('error', 'Save YouTube channel network error', error);
                    } else if (error.response.data.message) {
                        this.notifyError(error.response.data.message);
                        this.sendLogs('error', 'Can not save YouTube channel', error);
                    } else {
                        this.notifyError(this.$t('toasted.error.try_later'));
                        this.sendLogs('error', 'An error has occurred, please try again later', error);
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
