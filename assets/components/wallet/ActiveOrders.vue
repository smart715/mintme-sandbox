<template>
    <div class="pb-3">
        <div class="table-responsive">
            <confirm-modal
                :visible="confirmModal"
                @close="switchConfirmModal(false)"
                @confirm="removeOrder"
            >
                <div>
                    Are you sure that you want to remove {{ this.currentRow.name }}
                    with amount {{ this.currentRow.amount }} and price {{ this.currentRow.price }}
                </div>
            </confirm-modal>
            <font-awesome-icon
                    icon="circle-notch"
                    spin class="loading-spinner"
                    fixed-width
                    v-if="showLoadingIcon"
            />
            <b-table v-if="hasOrders" ref="table"
                :items="getHistory"
                :fields="fields"
                :current-page="currentPage"
                :per-page="perPage">
                <template slot="action" slot-scope="row">
                    <a @click="removeOrderModal(row.item)">
                        <font-awesome-icon icon="times" class="text-danger c-pointer" />
                    </a>
                </template>
            </b-table>
            <div v-if="!hasOrders && !showLoadingIcon">
                <h4 class="text-center p-5">No order was added yet</h4>
            </div>
        </div>
        <div v-if="hasOrders" class="row justify-content-center">
            <b-pagination
                :total-rows="totalRows"
                :per-page="perPage"
                v-model="currentPage"
                class="my-0" />
        </div>
    </div>
</template>
<script>
import WebSocketMixin from '../../js/mixins/websocket';
import ConfirmModal from '../modal/ConfirmModal';
import Decimal from 'decimal.js';
import {WSAPI} from '../../js/utils/constants';
import {toMoney} from '../../js/utils';

export default {
    name: 'ActiveOrders',
    mixins: [WebSocketMixin],
    components: {
        ConfirmModal,
    },
    props: {
        markets: Array,
        orders: {type: [Object, Boolean], default: () => []},
    },
    data() {
        return {
            currentRow: {},
            actionUrl: '',
            currentPage: 1,
            perPage: 10,
            pageOptions: [10, 20, 30],
            confirmModal: false,
            tokenName: null,
            amount: null,
            price: null,
            ordersList: Object.values(this.orders),
            fields: {
                date: {label: 'Date', sortable: true},
                type: {label: 'Type', sortable: true},
                name: {label: 'Name', sortable: true},
                amount: {label: 'Amount', sortable: true},
                price: {label: 'Price', sortable: true},
                total: {label: 'Total cost', sortable: true},
                fee: {label: 'Fee', sortable: true},
                action: {label: 'Action', sortable: false},
            },
        };
    },
    computed: {
        totalRows: function() {
            return this.ordersList.length;
        },
        marketNames: function() {
            return this.markets.map((market) => market.hiddenName);
        },
        hasOrders: function() {
            return this.ordersList.length > 0;
        },
        showLoadingIcon: function() {
            return (this.orders === false && !this.hasOrders);
        },
    },
    mounted: function() {
        this.authorize()
            .then(() => {
                this.sendMessage(JSON.stringify({
                    method: 'order.subscribe',
                    params: this.marketNames,
                    id: parseInt(Math.random()),
                }));

                this.addMessageHandler((response) => {
                    if ('order.update' === response.method) {
                        let data = response.params[1];
                        let order = this.ordersList.find((order) => data.id === order.id);

                        switch (response.params[0]) {
                            case WSAPI.order.status.PUT:
                                this.ordersList.push({
                                    amount: data.amount,
                                    price: data.price,
                                    fee: WSAPI.order.type.SELL === parseInt(data.type)
                                        ? data.maker_fee : data.taker_fee,
                                    id: data.id,
                                    side: data.side,
                                    timestamp: data.mtime,
                                    market: this.getMarketFromName(data.market),
                                });
                                break;
                            case WSAPI.order.status.UPDATE:
                                if (typeof order === 'undefined') {
                                    return;
                                }

                                let index = this.ordersList.indexOf(order);
                                order.amount = data.left;
                                order.price = data.price;
                                order.timestamp = data.mtime;
                                this.ordersList[index] = order;
                                break;
                            case WSAPI.order.status.FINISH:
                                if (typeof order === 'undefined') {
                                    return;
                                }

                                this.ordersList.splice(this.ordersList.indexOf(order), 1);
                                break;
                        }

                        this.ordersList.sort((a, b) => {
                            return a.timestamp < b.timestamp;
                        });
                    }
                });
            })
            .catch(() => {
                this.$toasted.error(
                    'Can not connect to internal services'
                );
            });
    },
    methods: {
        getHistory: function() {
            return this.ordersList.map((order) => {
                return {
                    date: new Date(order.timestamp * 1000).toDateString(),
                    type: WSAPI.order.type.SELL === parseInt(order.side) ? 'Sell' : 'Buy',
                    name: order.market.tokenName + '/' + order.market.currencySymbol,
                    amount: toMoney(order.amount),
                    price: toMoney(order.price),
                    total: toMoney(new Decimal(order.price).mul(order.amount).toString()),
                    fee: order.fee * 100 + '%',
                    action: this.$routing.generate('order_cancel', {
                        market: order.market.hiddenName, orderid: order.id,
                    }),
                    id: order.id,
                };
            });
        },
        removeOrderModal: function(row) {
            this.currentRow = row;
            this.actionUrl = row.action;
            this.switchConfirmModal(true);
        },
        switchConfirmModal: function(val) {
            this.confirmModal = val;
        },
        removeOrder: function() {
            this.$axios.get(this.actionUrl).catch(() => {
                this.$toasted.show('Service unavailable, try again later');
            });
        },
        getMarketFromName: function(name) {
            return this.markets.find((market) => market.hiddenName === name);
        },
    },
};
</script>
