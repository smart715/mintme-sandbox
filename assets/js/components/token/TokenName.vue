<template>
    <div class="overflow-wrap-break-word">
        <template v-if="editable">
            <token-edit-modal
                v-if="editable"
                :current-name="currentName"
                :has-release-period-prop="hasReleasePeriodProp"
                :is-owner="editable"
                :is-token-exchanged="isTokenExchanged"
                :no-close="true"
                :precision="precision"
                :status-prop="statusProp"
                :twofa="twofa"
                :visible="showTokenEditModal"
                :websocket-url="websocketUrl"
                :release-address="releaseAddress"
                :discord-url="discordUrl"
                :editable="editable"
                :facebook-url="facebookUrl"
                :facebook-app-id="facebookAppId"
                :telegram-url="telegramUrl"
                :website-url="websiteUrl"
                :youtube-client-id="youtubeClientId"
                :youtube-channel-id="youtubeChannelId"
                @close="closeTokenEditModal"
                @token-deploy-pending="$emit('token-deploy-pending')"
                @update-release-address="updateReleaseAddress"
                @updated-website="$emit('updated-website', $event)"
                @updated-facebook="$emit('updated-facebook', $event)"
                @updated-youtube="$emit('updated-youtube', $event)"
            />
            <font-awesome-icon
                class="icon-edit c-pointer align-middle"
                icon="edit"
                transform="shrink-4 up-1.5"
                @click="editToken"
            />
        </template>
        <span>
            {{ currentName|rebranding }}
        </span>
    </div>
</template>

<script>
import {library} from '@fortawesome/fontawesome-svg-core';
import {faEdit} from '@fortawesome/free-solid-svg-icons';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import {mixin as clickaway} from 'vue-clickaway';
import {WebSocketMixin, FiltersMixin, RebrandingFilterMixin, NotificationMixin, LoggerMixin} from '../../mixins/';
import TokenEditModal from '../modal/TokenEditModal';

library.add(faEdit);

export default {
    name: 'TokenName',
    props: {
        editable: Boolean,
        hasReleasePeriodProp: Boolean,
        identifier: String,
        name: String,
        precision: Number,
        statusProp: String,
        twofa: Boolean,
        websocketUrl: String,
        releaseAddress: String,
        discordUrl: String,
        facebookUrl: String,
        facebookAppId: String,
        telegramUrl: String,
        websiteUrl: String,
        youtubeClientId: String,
        youtubeChannelId: String,
    },
    components: {
        FontAwesomeIcon,
        TokenEditModal,
    },
    mixins: [WebSocketMixin, FiltersMixin, RebrandingFilterMixin, NotificationMixin, clickaway, LoggerMixin],
    data() {
        return {
            currentName: this.name,
            isTokenExchanged: true,
            isTokenNotDeployed: false,
            showTokenEditModal: false,
        };
    },
    mounted: function() {
        if (!this.editable) {
            return;
        }

        this.checkIfTokenExchanged();

        this.addMessageHandler((response) => {
            if (
                ('asset.update' === response.method && response.params[0].hasOwnProperty(this.identifier))
                || 'order.update' === response.method
            ) {
                this.checkIfTokenExchanged();
            }
        }, 'token-name-asset-update');
    },
    methods: {
        closeTokenEditModal: function() {
            this.showTokenEditModal = false;
        },
        checkIfTokenExchanged: function() {
            this.$axios.retry.get(this.$routing.generate('is_token_exchanged', {
                name: this.currentName,
            }))
            .then((res) => this.isTokenExchanged = res.data)
            .catch((err) => {
                this.notifyError('Can not fetch token data now. Try later');
                this.sendLogs('error', 'Can not fetch token data now', err);
            });
        },
        editToken: function() {
            if (!this.editable) {
                return;
            }

            this.showTokenEditModal = true;
        },
        updateReleaseAddress: function() {
            this.releaseAddress = '0x';
        },
    },
};
</script>

