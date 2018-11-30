<template>
    <div class="pb-3">
        <div class="table-responsive">
            <confirm-modal
                :visible="confirmModal"
                v-on:close="switchConfirmModal"
                v-on:confirm="removeOrder"
            >
                <div>
                    Are you sure that you want to remove {{ this.currentRow.name }}
                    with amount {{ this.currentRow.amount }} and price {{ this.currentRow.price }}
                </div>
            </confirm-modal>
            <b-table ref="table"
                :items="getHistory"
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

const METHOD_AUTH = 12345;
const METHOD_ORDER_QUERY = 54321;
const METHOD_ORDER_SUBSCRIBE = 12878;

export default {
    name: 'ActiveOrders',
    components: {
        ConfirmModal,
        WebSocket,
    },
    props: {
        hash: String,
        markets: Array,
        websocket_url: String,
    },
    data() {
        return {
            currentRow: {},
            actionUrl: '',
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
    methods: {
        getHistory: function() {
          return this.history;
        },
        removeOrderModal: function(row) {
            this.currentRow = row;
            this.actionUrl = row.action;
            this.confirmModal = !this.confirmModal;
        },
        switchConfirmModal: function() {
            this.confirmModal = !this.confirmModal;
        },
        removeOrder: function() {
            this.$axios.get(this.actionUrl)
                .catch(() => {
                    this.$toasted.show('Service unavailable, try again later');
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
            this.wsClient.send(JSON.stringify({
                'method': 'order.subscribe',
                'params': this.markets.filter(Boolean).join(','),
                'id': METHOD_ORDER_SUBSCRIBE,
            }));
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
                    action: this.$routing.generate('order_cancel', {
                        market: order.market, orderid: order.id,
                    }),
                    id: order.id,
                });
            });
            this.$refs.table.refresh();
        },
        deleteHistoryOrder: function(id) {
            delete this.history.filter((item) => item.id === id)[0];
            this.$refs.table.refresh();
        },
    },
    mounted() {
        this.wsClient = this.$socket(this.websocket_url);
        this.wsClient.onmessage = (result) => {
            let response = JSON.parse(result.data);
            switch (response.id) {
                case METHOD_AUTH:
                    if (response.error === null) {
                        this.getOrders();
                    }
                    break;
                case METHOD_ORDER_QUERY:
                    this.parseOrders(response.result.records);
                    this.subscribe();
                    break;
                case null:
                    if (response.method === 'order.update') {
                        this.deleteHistoryOrder(response.params[1].id);
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
    computed: {
        totalRows: function() {
            return this.history.length;
        },
    },
};
</script>
