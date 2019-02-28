<template>
    <div class="d-flex">
        <confirm-modal
                :visible="confirmModal"
                @close="switchConfirmModal(false)"
                @confirm="removeOrder"
        >
            <ul>
                You want to delete these orders:
                <li v-for="order in this.removeOrders" :key="order.id">
                    Price {{ order.price }} Amount {{ order.amount }}
                </li>
                Are you sure?
            </ul>
        </confirm-modal>
        <div class="col-12 col-md-6 mt-3">
            <token-trade-buy-orders
                    v-if="ordersLoaded"
                    :buy-orders="buyOrders"
                    :filtered="filteredBuyOrders"
                    :token-name="tokenName"
                    :user-id="userId"
                    @modal="removeOrderModal"/>
            <template v-else>
                <font-awesome-icon icon="circle-notch" spin class="loading-spinner d-block text-white mx-auto my-3"
                                   size="5x"/>
            </template>
        </div>
        <div class="col-12 col-md-6 mt-3">
            <token-trade-sell-orders
                    v-if="ordersLoaded"
                    :sell-orders="sellOrders"
                    :filtered="filteredSellOrders"
                    :token-name="tokenName"
                    :user-id="userId"
                    @modal="removeOrderModal"/>
            <template v-else>
                <font-awesome-icon icon="circle-notch" spin class="loading-spinner d-block text-white mx-auto my-3"
                                   size="5x"/>
            </template>
        </div>
    </div>
</template>

<script>
import TokenTradeBuyOrders from './TokenTradeBuyOrders';
import TokenTradeSellOrders from './TokenTradeSellOrders';
import ConfirmModal from '../../modal/ConfirmModal';
import Decimal from 'decimal.js';
import {toMoney} from '../../../js/utils';

export default {
    name: 'TokenTradeOrders',
    components: {
        TokenTradeBuyOrders,
        TokenTradeSellOrders,
        ConfirmModal,
    },
    props: {
        ordersLoaded: Boolean,
        buyOrders: [Array, Object],
        sellOrders: [Array, Object],
        tokenName: String,
        userId: Number,
    },
    data() {
        return {
            removeOrders: [],
            confirmModal: false,
        };
    },
    computed: {
        filteredBuyOrders: function() {
            return this.ordersList(this.groupByPrice(this.buyOrders));
        },
        filteredSellOrders: function() {
            return this.ordersList(this.groupByPrice(this.sellOrders));
        },
    },
    methods: {
        groupByPrice: function(orders) {
            let filtered = [];
            let grouped = {};
            this.clone(orders).forEach( (item) => {
                if (grouped[item.price] === undefined) {
                    grouped[item.price] = [];
                }
                grouped[item.price].push(item);
            });
            for (let orders in grouped) {
                if (grouped.hasOwnProperty(orders)) {
                    let sum = grouped[orders].reduce((sum, order)=> parseFloat(order.amount) + sum, 0);
                    grouped[orders].sort((first, second) => first.maker.id - second.maker.id);
                    grouped[orders].forEach((order, i, arr) => {
                        if (arr[i-1] !== undefined && arr[i-1].maker.id === order.maker.id) {
                            order.amount = new Decimal(order.amount).add(arr[i-1].amount);
                        }
                    });
                    grouped[orders].sort((first, second) => parseFloat(second.amount) - parseFloat(first.amount));
                    grouped[orders][0].amount = sum;
                    filtered.push(grouped[orders][0]);
                }
            }
            return filtered;
        },
        ordersList: function(orders) {
            return orders.map((order) => {
                return {
                    price: toMoney(order.price),
                    amount: toMoney(order.amount),
                    sum_web: toMoney(new Decimal(order.price).mul(order.amount).toString()),
                    trader: this.truncateFullName(order),
                    trader_url: this.$routing.generate('token_show', {
                        name: order.maker.profile.token.name,
                    }),
                    trader_id: order.maker.id === this.userId ? this.userId : null,
                };
            });
        },
        truncateFullName: function(order) {
            let first = order.maker.profile.firstName;
            let second = order.maker.profile.lastName;
            if ((first + second).length > 23) {
                return first.slice(0, 5) + '. ' + second.slice(0, 10) + '.';
            } else {
                return first + ' ' + second;
            }
        },
        removeOrderModal: function(data) {
            this.removeOrders = [];
            this.clone(data.orders).forEach( (order) => {
                if (toMoney(order.price) === data.row.price && order.maker.id === data.row.trader_id) {
                    order.price = toMoney(order.price);
                    order.amount = toMoney(order.amount);
                    this.removeOrders.push(order);
                }
            });
            this.switchConfirmModal(true);
        },
        switchConfirmModal: function(val) {
            this.confirmModal = val;
        },
        removeOrder: function() {
            let market = this.removeOrders[0].market.hiddenName;
            this.$axios.single.delete(
                this.$routing.generate('orders_cancel', {
                    'market': market,
                    'ids': JSON.stringify(this.removeOrders.map((order) => order.id)),
                })
            ).catch(() => {
                this.$toasted.show('Service unavailable, try again later');
            });
        },
        clone: function(orders) {
            return JSON.parse(JSON.stringify(orders));
        },
    },
};
</script>
