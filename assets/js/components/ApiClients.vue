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
                            <copy-link class="code-copy c-pointer ml-2" id="client-copy-btn" :content-to-copy="row.item.id">
                                <font-awesome-icon :icon="['far', 'copy']" class="hover-icon"></font-awesome-icon>
                            </copy-link>
                            <button
                                class="btn btn-transparent p-0"
                                @click="setInvalidateModal(true, row.item.id)"
                            >
                                <font-awesome-icon
                                    icon="times"
                                    class="text-danger c-pointer ml-2"
                                />
                            </button><br />
                            {{ $t('api_clients.secret.title') }}<br />
                            <div v-if="row.item.secret">
                                <template>
                                    <span class="text-danger word-break">{{ row.item.secret }}</span>
                                    <copy-link class="code-copy c-pointer ml-2" id="secret-copy-btn" :content-to-copy="row.item.secret">
                                        <font-awesome-icon :icon="['far', 'copy']" class="hover-icon"></font-awesome-icon>
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
        <confirm-modal :visible="invalidateModal" @confirm="deleteClient(clientId)" @close="setInvalidateModal(false, clientId)">
            <p class="text-white modal-title pt-2">
                {{ $t('api_clients.confirm_modal') }}
            </p>
        </confirm-modal>
    </div>
</template>

<script>
import {library} from '@fortawesome/fontawesome-svg-core';
import {faCopy} from '@fortawesome/free-regular-svg-icons';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import ConfirmModal from './modal/ConfirmModal';
import CopyLink from './CopyLink';
import {NotificationMixin} from '../mixins';
import {BTable} from 'bootstrap-vue';

library.add(faCopy);

export default {
    name: 'ApiClients',
    mixins: [NotificationMixin],
    components: {
        BTable,
        ConfirmModal,
        CopyLink,
        FontAwesomeIcon,
    },
    props: {
        apiClients: {type: [Array], required: true},
    },
    data() {
        return {
            clients: this.apiClients,
            invalidateModal: false,
            clientId: '',
            fields: [{key: 'id', label: ''}],
        };
    },
    computed: {
        hasClients: function() {
            return this.clients.length > 0;
        },
    },
    methods: {
        createClient: function() {
            return this.$axios.single.post(this.$routing.generate('post_client'))
                .then((res) => this.clients.push(res.data))
                .catch((err) => {
                    this.notifyError(this.$t('toasted.error.try_reload'));
                    this.sendLogs('error', 'Can not create API Client', err);
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
                    this.sendLogs('error', 'Can not delete API Client', err);
                });
        },
        setInvalidateModal: function(on, clientId) {
            this.invalidateModal = on;
            this.clientId = clientId;
        },
    },
};
</script>
