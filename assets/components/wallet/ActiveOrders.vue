<template>
    <div class="pb-3">
        <div class="table-responsive">
            <confirm-modal
                    :visible="confirmModal"
                    :tokenName="tokenName"
                    :amount="amount"
                    :price="price"
                    v-on:close="switchConfirmModal"
                    v-on:confirm="removeOrder"
            ></confirm-modal>
            <b-table
                :items="history"
                :fields="fields"
                :current-page="currentPage"
                :per-page="perPage">
                <template slot="action" slot-scope="row">
                    <a @click="removeOrderModal(row.item)">
                        <font-awesome-icon
                            icon="times"
                            class="text-danger" />
                    </a>
                </template>
            </b-table>
        </div>
        <div class="row justify-content-center">
            <b-pagination
                :total-rows="totalRows"
                :per-page="perPage"
                v-model="currentPage"
                class="my-0" />
        </div>
    </div>
</template>
<script>
import ConfirmModal from '../modal/ConfirmModal';
import {w3cwebsocket as W3CWebSocket} from 'websocket';

export default {
    name: 'ActiveOrders',
    components: {
        ConfirmModal,
    },
    methods: {
        removeOrderModal: function(row) {
            this.tokenName = row.name;
            this.amount = row.amount;
            this.price = row.price;
            this.confirmModal = !this.confirmModal;
        },
        switchConfirmModal: function() {
            this.confirmModal = !this.confirmModal;
        },
        removeOrder: function() {
            alert('this method for remove order');
        },
    },
    mounted() {
        this.wsClient = new W3CWebSocket('ws://mintme.abchosting.org:8364');
        this.wsClient.onmessage = (result) => {
            console.log(JSON.parse(result.data));
        };
        this.wsClient.onopen = () => {
            this.wsClient.send(`{
                "method": "server.auth",
                "params": ["token123", "web"],
                "id": 0
            }`);
        };
    },
    data() {
        return {
            history: [],
            currentPage: 1,
            perPage: 10,
            pageOptions: [10, 20, 30],
            confirmModal: false,
            tokenName: null,
            amount: null,
            price: null,
            fields: {
                date: {
                    label: 'Date',
                    sortable: true,
                },
                type: {
                    label: 'Type',
                    sortable: true,
                },
                name: {
                    label: 'Address',
                    sortable: true,
                },
                amount: {
                    label: 'Amount',
                    sortable: true,
                },
                price: {
                    label: 'Price',
                    sortable: true,
                },
                total: {
                    label: 'Total cost',
                    sortable: true,
                },
                free: {
                    label: 'Free',
                    sortable: true,
                },
                action: {
                    label: 'Action',
                    sortable: false,
                },
            },
        };
    },
    computed: {
        totalRows: function() {
            return this.history.length;
        },
    },
    created: function() {
        // TODO: This is a dummy simulator.
        for (let i = 0; i < 100; i++) {
            this.history.push({
                date: '12-12-1970',
                type: (i % 2 === 0) ? 'Deposit' : 'Withdraw',
                name: (i % 2 === 0) ? '[Token]' : 'Webchain (WEB)',
                amount: Math.floor(Math.random() * 99) + 10,
                price: Math.floor(Math.random() * 99) + 10,
                total: Math.floor(Math.random() * 99) + 10 + 'WEB',
                free: Math.floor(Math.random() * 99) + 10,
                action: '',
            });
        }
    },
};
</script>

