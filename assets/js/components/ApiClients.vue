<template>
    <div>
        <div class="card-header">Clients for OAuth access to API:</div>
        <b-table small
                 v-if="hasClients"
                 ref="table"
                 :items="clients"
                 :fields="fields">
            <template v-slot:cell(id)="row">
                <div class="text-nowrap">
                    <span class="text-danger">{{ row.item.id }}</span>
                    <copy-link class="code-copy c-pointer ml-2" id="client-copy-btn" :content-to-copy="row.item.id">
                        <font-awesome-icon :icon="['far', 'copy']"></font-awesome-icon>
                    </copy-link>
                    <a @click="toggleInvalidateModal(true, row.item.id)">
                        <font-awesome-icon icon="times" class="text-danger c-pointer ml-2" />
                    </a>
                </div>
            </template>
            <template v-slot:cell(secret)="row">
                <template v-if="row.item.secret">
                <div class="text-nowrap">
                    <span class="text-danger">{{ row.item.secret }}</span>
                    <copy-link class="code-copy c-pointer ml-2" id="secret-copy-btn" :content-to-copy="row.item.secret">
                        <font-awesome-icon :icon="['far', 'copy']"></font-awesome-icon>
                    </copy-link>
                    <br />
                    (Copy this secret, you will not able to see it again after reload)
                </div>
                </template>
                <template v-else>
                    ** hidden **
                </template>
            </template>
        </b-table>
        <p>Create New Client for OAuth:</p>
        <div class="btn btn-primary c-pointer" @click="createClient">Create</div>
        <confirm-modal :visible="invalidateModal" @confirm="deleteClient(clientId)" @close="toggleInvalidateModal(false, clientId)">
            <p class="text-white modal-title pt-2">
                Are you sure you want to delete your API Client.
                Currently running applications will not work. Continue?
            </p>
        </confirm-modal>
    </div>
</template>

<script>
    import ConfirmModal from './modal/ConfirmModal';
    import CopyLink from './CopyLink';
    import {NotificationMixin} from '../mixins';

    export default {
        name: 'ApiClients',
        mixins: [NotificationMixin],
        components: {ConfirmModal, CopyLink},
        props: {
            apiClients: {type: [Array], required: true},
        },
        data() {
            return {
                clients: this.apiClients,
                invalidateModal: false,
                clientId: '',
                fields: ['id', 'secret'],
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
                    .then((res) => this.clients = res.data)
                    .catch(() => this.notifyError('Something went wrong. Try to reload the page.'));
            },
            deleteClient: function(clientId) {
                return this.$axios.single.delete(this.$routing.generate('delete_client'), {params: {id: clientId}})
                    .then((res) => {
                        this.clients = res.data;
                        this.toggleInvalidateModal(false, '');
                    })
                    .catch(() => this.notifyError('Something went wrong. Try to reload the page.'));
            },
            toggleInvalidateModal: function(on, clientId) {
                this.invalidateModal = on;
                this.clientId = clientId;
            },
        },
    };
</script>

<style scoped>

</style>
