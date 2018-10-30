<template>
    <div class="pb-3">
        <div class="table-responsive">
            <confirm-modal
                    :visible="confirmModal"
                    v-on:close="switchConfirmModal"
                    v-on:confirm="removeOrder"
            >
                {{ this.modalMessage }}
            </confirm-modal>
            <b-table ref="table"
                :items="myProvider"
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

const METHOD_AUTH = 12345;
const METHOD_ORDER_QUERY = 54321;
const METHOD_ORDER_SUBSCRIBE = 12878;

Vue.use(WebSocket);

export default {
    name: 'ActiveOrders',
    components: {
        ConfirmModal,
    },
    props: {
        hash: String,
        markets: Array,
        user_id: Number,
        websocket_url: String,
    },
    methods: {
        myProvider: function() {
          return this.history;
        },
        removeOrderModal: function(row) {
            this.modalMessage = 'Are you sure that you want to remove ' + row.name +
                'with amount ' + row.amount + 'and price ' + row.price;
            this.url = row.action;
            this.confirmModal = !this.confirmModal;
        },
        switchConfirmModal: function() {
            this.confirmModal = !this.confirmModal;
        },
        removeOrder: function() {
            axios.get(this.url)
                .catch(function(error) {
                    console.log(error);
                });
        },
        getOrders: function() {
            this.markets.forEach((token) => {
                if (token !== null) {
                    this.wsClient.send(JSON.stringify({
                    'method': 'order.query',
                    'params': [token, 0, 100],
                    'id': METHOD_ORDER_QUERY,
                    }));
                }
            });
        },
        subscribe: function() {
            this.markets.forEach((token) => {
                if (token !== null) {
                    this.wsClient.send(JSON.stringify({
                        'method': 'order.subscribe',
                        'params': [token],
                        'id': METHOD_ORDER_SUBSCRIBE,
                    }));
                }
            });
        },
        parseOrders: function(orders) {
            orders.forEach((order) => {
                this.history.push({
                    date: new Date(order.ctime).toDateString(),
                    type: (1 === order.type) ? 'Deposit' : 'Withdraw',
                    name: order.market,
                    amount: order.amount,
                    price: order.price,
                    total: (order.price * order.amount + order.maker_fee),
                    free: order.maker_fee,
                    action: '../api/user/cancel-order/' + this.user_id + '/'
                    + order.market + '/' + order.id,
                    id: order.id,
                });
                this.$refs.table.refresh();
            });
        },
        deleteHistoryOrder: function(id) {
            this.history.forEach((item, key) => {
                if (item.id === id) {
                    delete this.history[key];
                }
            });
            this.$refs.table.refresh();
        },
    },
    mounted() {
        this.wsClient = this.$socket(this.websocket_url);
        this.wsClient.onmessage = (result) => {
            let orders = JSON.parse(result.data);
            console.log(orders);
            switch (orders.id) {
                case METHOD_AUTH:
                    if (orders.error === null) {
                        this.getOrders();
                    }
                    break;
                case METHOD_ORDER_QUERY:
                    this.parseOrders(orders.result.records);
                    this.subscribe();
                    break;
                case null:
                    if (orders.method === 'order.update') {
                        this.deleteHistoryOrder(orders.params[1].id);
                    }
                    break;
            }
        };
        this.wsClient.onopen = () => {
            this.wsClient.send(JSON.stringify({
                method: 'server.auth',
                params: [this.hash, 'auth_api'],
                id: METHOD_AUTH,
            }));
        };
    },
    data() {
        return {
            modalMessage: '',
            url: '',
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
