<template>
    <div class="overflow-hidden token-name">
        <template v-if="editable">
            <token-edit-modal
                v-if="editable"
                :current-name="currentName"
                :has-release-period-prop="hasReleasePeriodProp"
                :is-owner="editable"
                :is-token-created="isTokenCreated"
                :is-mintme-token="isMintmeToken"
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
                :airdrop-params="airdropParams"
                :disabled-services-config="disabledServicesConfig"
                @close="closeTokenEditModal"
                @token-deploy-pending="$emit('token-deploy-pending')"
                @update-release-address="updateReleaseAddress"
                @updated-website="$emit('updated-website', $event)"
                @updated-facebook="$emit('updated-facebook', $event)"
                @updated-youtube="$emit('updated-youtube', $event)"
                @updated-discord="$emit('updated-discord', $event)"
                @updated-telegram="$emit('updated-telegram', $event)"
            />
            <font-awesome-icon
                class="icon-default c-pointer align-middle token-edit-icon"
                icon="edit"
                transform="shrink-4 up-1.5"
                @click="editToken"
            />
        </template>
        <h1 v-if="shouldTruncate"
              class="h2 current-token-name"
              v-b-tooltip="{title: currentName, boundary:'viewport'}">
            {{ currentName | truncate(maxLengthToTruncate) }}
        </h1>
        <h1 v-else class="h2 current-token-name">
            {{ currentName }}
        </h1>
    </div>
</template>

<script>
import {library} from '@fortawesome/fontawesome-svg-core';
import {faEdit} from '@fortawesome/free-solid-svg-icons';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import {mixin as clickaway} from 'vue-clickaway';
import {WebSocketMixin, FiltersMixin, LoggerMixin} from '../../mixins/';
import TokenEditModal from '../modal/TokenEditModal';
import {AIRDROP_CREATED, AIRDROP_DELETED, TOKEN_NAME_CHANGED} from '../../utils/constants';

library.add(faEdit);

export default {
    name: 'TokenName',
    props: {
        editable: Boolean,
        hasReleasePeriodProp: Boolean,
        isTokenCreated: Boolean,
        isMintmeToken: Boolean,
        identifier: String,
        name: String,
        precision: Number,
        statusProp: String,
        twofa: Boolean,
        websocketUrl: String,
        releaseAddress: String,
        airdropParams: Object,
        discordUrl: String,
        facebookUrl: String,
        facebookAppId: String,
        telegramUrl: String,
        websiteUrl: String,
        youtubeClientId: String,
        youtubeChannelId: String,
        showTokenEditModalProp: Boolean,
        disabledServicesConfig: String,
    },
    components: {
        FontAwesomeIcon,
        TokenEditModal,
    },
    mixins: [WebSocketMixin, FiltersMixin, clickaway, LoggerMixin],
    data() {
        return {
            currentName: this.name,
            isTokenExchanged: true,
            isTokenNotDeployed: false,
            maxLengthToTruncate: 30,
            showTokenEditModal: this.showTokenEditModalProp,
        };
    },
    computed: {
        shouldTruncate: function() {
            return this.currentName.length > this.maxLengthToTruncate;
        },
    },
    mounted: function() {
        if (!this.editable) {
            return;
        }

        window.addEventListener('storage', (event) => {
            // Reload token page in case if token name was changed in another tab
            if (TOKEN_NAME_CHANGED === event.key && this.currentName === event.oldValue
                && this.currentName !== event.newValue
            ) {
                this.currentName = event.newValue;
                window.localStorage.removeItem(event.key);
                location.href = this.$routing.generate('token_show', {
                    name: this.currentName,
                });
            }

            // Reload token page in case if new token created/deleted in another tab
            if ((AIRDROP_CREATED === event.key || AIRDROP_DELETED === event.key)
                && this.currentName === event.newValue
            ) {
                window.localStorage.removeItem(event.key);
                location.reload();
            }
        });

        this.checkIfTokenExchanged();

        this.addMessageHandler((response) => {
            if (
                ('asset.update' === response.method && response.params[0].hasOwnProperty(this.identifier))
                || 'order.update' === response.method
            ) {
                this.checkIfTokenExchanged();
            }
        }, 'token-name-asset-update', 'TokenName');

        if (this.showTokenEditModalProp) {
            window.history.replaceState(
                {}, '', this.$routing.generate('token_show', {
                    name: this.name,
                })
            );
        }
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

