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
import WebSocket from '../../js/websocket';
import axios from 'axios';

Vue.use(WebSocket);

export default {
    name: 'ActiveOrders',
    components: {
        ConfirmModal,
    },
    props: {
        hash: String,
        token: String,
    },
    methods: {
        removeOrderModal: function(row) {
            this.url = row.action;
            this.tokenName = row.name;
            this.amount = row.amount;
            this.price = row.price;
            this.confirmModal = !this.confirmModal;
        },
        switchConfirmModal: function() {
            this.confirmModal = !this.confirmModal;
        },
        removeOrder: function() {
            axios.get('..' + this.url);
        },
        getOrders: function() {
            this.request = JSON.stringify({
                'method': 'order.query',
                'params': ['TOK000000000001WEB', 0, 100],
                'id': 2,
            });
            this.wsClient.send(this.request);
        },
        subscribe: function() {
            this.request = JSON.stringify({
                'method': 'order.subscribe',
                'params': ['TOK000000000001WEB'],
                'id': 3,
            });
            this.wsClient.send(this.request);
        },
        parseOrders: function(orders) {
            console.log(orders);
            for (let key in orders) {
                if (orders.hasOwnProperty(key)) {
                    this.history.push({
                        date: new Date(orders[key].ctime).toDateString(),
                        type: (1 === orders[key].type) ? 'Deposit' : 'Withdraw',
                        name: orders[key].market,
                        amount: orders[key].amount,
                        price: orders[key].price,
                        total: (orders[key].price * orders[key].amount + orders[key].maker_fee),
                        free: orders[key].maker_fee,
                        action: '/api/user/cancel-order/' + this.userId + '/'
                                + orders[key].market.slice(3, -3) + '/' + orders[key].id,
                        id: orders[key].id,
                    });
                }
            }
        },
        deleteHistoryOrder: function(id) {
            for (let i = 0; i < this.history.length; i++) {
                if (this.history[i].id === id) {
                    delete this.history[i];
                }
            }
        },
    },
    mounted() {
        this.wsClient = this.$socket('ws://mintme.abchosting.org:8364');
        this.wsClient.onmessage = (result) => {
            this.orders = JSON.parse(result.data);
            if (this.orders.hasOwnProperty('method') && this.orders.method === 'order.update') {
                console.log(this.orders.params[1].id);
                this.deleteHistoryOrder(this.orders.params[1].id);
            }
            if (this.orders.id === 1) {
                this.getOrders();
            }
            if (this.orders.id === 2) {
                this.parseOrders(this.orders.result.records);
                this.subscribe();
            }
        };
        this.wsClient.onopen = () => {
            this.request = JSON.stringify({
                method: 'server.auth',
                params: ['NWJjZGU5YWMxNTcyNjMuNDc3MzIyMTQ=', 'web'],
                id: 1,
            });
            this.wsClient.send(this.request);
        };
    },
    data() {
        return {
            url: '',
            userId: 1,
            request: {},
            orders: null,
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
};
</script>

