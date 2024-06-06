<template>
    <div>
        <b-table small
                 striped
                 v-if="hasClients"
                 ref="table"
                 :items="clients"
                 :fields="fields">
            <template v-slot:cell(id)="row">
                <div class="text-center">
                    <div class="text-left">
                        <div class="text-left d-inline-block ml-api">
                            {{ $t('api_clients.id.title') }}<br />
                            <span class="text-danger word-break">{{ row.item.id }}</span>
                            <copy-link
                                id="client-copy-btn"
                                class="code-copy c-pointer ml-2"
                                :content-to-copy="row.item.id"
                            >
                                <font-awesome-icon :icon="['far', 'copy']" class="hover-icon"></font-awesome-icon>
                            </copy-link>
                            <button
                                class="btn btn-transparent p-0"
                                @click="setInvalidateModal(true, row.item.id)"
                            >
                                <font-awesome-icon
                                    icon="times"
                                    class="text-danger c-pointer"
                                />
                            </button><br />
                            {{ $t('api_clients.secret.title') }}<br />
                            <div v-if="row.item.secret">
                                <template>
                                    <span class="text-danger word-break">{{ row.item.secret }}</span>
                                    <copy-link
                                        id="secret-copy-btn"
                                        class="code-copy c-pointer ml-2"
                                        :content-to-copy="row.item.secret"
                                    >
                                        <font-awesome-icon
                                            :icon="['far', 'copy']"
                                            class="hover-icon"
                                        />
                                    </copy-link>
                                </template>
                            </div>
                            <div v-else>
                                <template>
                                <span class="text-white-50">{{ $t('api_clients.hidden') }}</span>
                                </template>
                            </div>
                        </div>
                    </div>
                    <span v-show="row.item.secret" class="small">
                        {{ $t('api_clients.copy_secret') }}
                    </span>
                </div>
            </template>
        </b-table>
        <p>{{ $t('api_clients.create_new') }}</p>
        <button
            class="btn btn-primary c-pointer"
            @click="createClient"
        >
            {{ $t('api_clients.create') }}
        </button>
        <set-two-factor-alert-modal
            :visible="setTwoFactorModalVisible"
            :message="setTwoFactorModalMessage"
            :noClose="modalNoClose"
            @close="closeModal"
        />
        <confirm-modal
            :visible="invalidateModal"
            @confirm="deleteClient(clientId)"
            @close="setInvalidateModal(false, clientId)"
        >
            <p class="text-white modal-title text-break pt-2">
                {{ $t('api_clients.confirm_modal') }}
            </p>
        </confirm-modal>
        <two-factor-modal
            :visible="showTwoFactorModal"
            twofa
            :loading="isCreating"
            @verify="createClient"
            @close="closeTwoFactorModal"
        />
    </div>
</template>

<script>
import {library} from '@fortawesome/fontawesome-svg-core';
import {faCopy} from '@fortawesome/free-regular-svg-icons';
import {faTimes} from '@fortawesome/free-solid-svg-icons';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import ConfirmModal from './modal/ConfirmModal';
import SetTwoFactorAlertModal from './modal/SetTwoFactorAlertModal';
import CopyLink from './CopyLink';
import {NotificationMixin, SetTwoFactorAlertMixin} from '../mixins';
import {BTable} from 'bootstrap-vue';
import {HTTP_ACCESS_DENIED, HTTP_UNAUTHORIZED} from '../utils/constants';
import TwoFactorModal from './modal/TwoFactorModal.vue';

library.add(faCopy, faTimes);

export default {
    name: 'ApiClients',
    mixins: [
        NotificationMixin,
        SetTwoFactorAlertMixin,
    ],
    components: {
        BTable,
        ConfirmModal,
        CopyLink,
        FontAwesomeIcon,
        SetTwoFactorAlertModal,
        TwoFactorModal,
    },
    props: {
        apiClients: {type: [Array], required: true},
        isTwoFactor: {type: Boolean, required: true},
    },
    data() {
        return {
            clients: this.apiClients,
            invalidateModal: false,
            clientId: '',
            fields: [{key: 'id', label: ''}],
            setTwoFactorModalVisible: false,
            setTwoFactorModalMessageType: 'oauth',
            modalNoClose: true,
            showTwoFactorModal: false,
            isCreating: false,
        };
    },
    computed: {
        hasClients: function() {
            return 0 < this.clients.length;
        },
    },
    methods: {
        closeModal: function() {
            this.setTwoFactorModalVisible = false;
        },
        createClient: function(code) {
            if (!this.isTwoFactor) {
                this.setTwoFactorModalVisible = true;
                return;
            }

            if (!this.showTwoFactorModal) {
                this.showTwoFactorModal = true;
                return;
            }

            this.isCreating = true;
            return this.$axios.single.post(this.$routing.generate('post_client'), {code: code})
                .then((res) => {
                    this.clients.push(res.data);
                    this.isCreating = false;
                    this.showTwoFactorModal = false;
                })
                .catch((err) => {
                    if (HTTP_UNAUTHORIZED === err.response.status) {
                        this.isCreating = false;
                        this.notifyError(err.response.data.message);
                    } else if (HTTP_ACCESS_DENIED === err.response.status) {
                        this.notifyError(err.response.data.message);
                    } else {
                        this.notifyError(this.$t('toasted.error.try_reload'));
                    }
                    this.$logger.error('Can not create API Client', err);
                });
        },
        deleteClient: function(clientId) {
            return this.$axios.single.delete(this.$routing.generate('delete_client', {id: clientId}))
                .then(() => {
                    this.clients = this.clients.filter(function(item) {
                        return clientId != item.id;
                    });
                    this.setInvalidateModal(false, '');
                })
                .catch((err) => {
                    this.notifyError(this.$t('toasted.error.try_reload'));
                    this.$logger.error('Can not delete API Client', err);
                });
        },
        setInvalidateModal: function(on, clientId) {
            this.invalidateModal = on;
            this.clientId = clientId;
        },
        closeTwoFactorModal: function() {
            this.showTwoFactorModal = false;
        },
    },
};
</script>
