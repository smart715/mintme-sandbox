<template>
    <div>
        <template v-if="editable">
            <token-edit-modal
                v-if="editable"
                :current-name="currentName"
                :has-release-period-prop="hasReleasePeriodProp"
                :is-owner="editable"
                :is-token-exchanged="isTokenExchanged"
                :no-close="true"
                :precision="precision"
                :min-destination-locked="minDestinationLocked"
                :status-prop="statusProp"
                :twofa="twofa"
                :visible="showTokenEditModal"
                :websocket-url="websocketUrl"
                :withdrawal-address="withdrawalAddress"
                @close="closeTokenEditModal"
                @token-deploy-pending="$emit('token-deploy-pending')"
            />
            <font-awesome-icon
                class="icon-edit c-pointer align-middle"
                icon="edit"
                transform="shrink-4 up-1.5"
                @click="editToken"
            />
        </template>
        <span v-b-tooltip="{title: currentName, boundary:'viewport'}">
            {{ currentName | truncate(7) }}
        </span>
    </div>
</template>

<script>
import {library} from '@fortawesome/fontawesome-svg-core';
import {faEdit} from '@fortawesome/free-solid-svg-icons';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import Toasted from 'vue-toasted';
import {mixin as clickaway} from 'vue-clickaway';
import {WebSocketMixin, FiltersMixin} from '../../mixins';
import TokenEditModal from '../modal/TokenEditModal';

library.add(faEdit);
Vue.use(Toasted, {
    position: 'top-center',
    duration: 5000,
});

export default {
    name: 'TokenName',
    props: {
        editable: Boolean,
        hasReleasePeriodProp: Boolean,
        identifier: String,
        name: String,
        minDestinationLocked: Boolean,
        precision: Number,
        statusProp: String,
        twofa: Boolean,
        websocketUrl: String,
        withdrawalAddress: String,
    },
    components: {
        FontAwesomeIcon,
        TokenEditModal,
    },
    mixins: [WebSocketMixin, FiltersMixin, clickaway],
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
            .catch(() => this.$toasted.error('Can not fetch token data now. Try later'));
        },
        editToken: function() {
            if (!this.editable) {
                return;
            }

            this.showTokenEditModal = true;
        },
    },
};
</script>

