<template>
    <div class="container">
        <div class="row">
            <confirm-modal
                    :visible="confirmModal"
                    @close="switchConfirmModal(false)"
                    @confirm="removeOrder"
            >
                <span class="text-white">
                    You want to delete these orders:<br>
                    <span v-for="order in this.removeOrders" :key="order.id">
                        Price {{ order.price }} Amount {{ order.amount }}<br>
                    </span>
                    Are you sure?
                </span>
            </confirm-modal>
            <div class="col-12 col-md-6 mt-3">
                <token-trade-buy-orders
                        v-if="ordersLoaded"
                        :orders-list="filteredBuyOrders"
                        :token-name="tokenName"
                        :fields="fields"
                        @modal="removeOrderModal"/>
                <template v-else>
                    <div class="p-5 text-center">
                        <font-awesome-icon icon="circle-notch" spin class="loading-spinner text-white" fixed-width />
                    </div>
                </template>
            </div>
            <div class="col-12 col-md-6 mt-3">
                <token-trade-sell-orders
                        v-if="ordersLoaded"
                        :orders-list="filteredSellOrders"
                        :token-name="tokenName"
                        :fields="fields"
                        @modal="removeOrderModal"/>
                <template v-else>
                    <div class="p-5 text-center">
                        <font-awesome-icon icon="circle-notch" spin class="loading-spinner text-white" fixed-width />
                    </div>
                </template>
            </div>
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
            fields: {
                price: {
                    label: 'Price',
                },
                amount: {
                    label: 'Amount',
                },
                sumWeb: {
                    label: 'Sum WEB',
                },
                trader: {
                    label: 'Trader',
                },
            },
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
        ordersList: function(orders) {
            return orders.map((order) => {
                return {
                    price: toMoney(order.price),
                    amount: toMoney(order.amount),
                    sumWeb: toMoney(new Decimal(order.price).mul(order.amount).toString()),
                    trader: order.maker !== null ? this.truncateFullName(order.maker.profile) : 'Anonymous',
                    traderUrl: this.$routing.generate('token_show', {
                        name: order.maker.profile.token.name,
                    }),
                    side: order.side,
                    owner: order.owner,
                };
            });
        },
        truncateFullName: function(profile) {
            let first = profile.firstName;
            let second = profile.lastName;
            if ((first + second).length > 23) {
                return first.slice(0, 5) + '. ' + second.slice(0, 10) + '.';
            } else {
                return first + ' ' + second;
            }
        },
        groupByPrice: function(orders) {
            let filtered = [];
            let grouped = this.clone(orders).reduce((a, e) => {
                if (a[e.price] === undefined) {
                    a[e.price] = [];
                }
                a[e.price].push(e);
                return a;
            }, {});

            Object.values(grouped).forEach((e) => {
                let obj = e.reduce((a, e) => {
                    a.owner = a.owner || e.maker.id === this.userId;
                    a.orders.push(e);
                    a.sum = new Decimal(a.sum).add(e.amount);

                    let amount = a.orders.filter((order) => order.maker.id === e.maker.id)
                        .reduce((a, e) => new Decimal(a).add(e.amount), 0);

                    if (parseFloat(a.main.amount) < parseFloat(amount)) {
                        a.main.amount = amount;
                        a.main.order = e;
                    }

                    return a;
                }, {owner: false, orders: [], main: {order: null, amount: 0}, sum: 0});

                let order = obj.main.order;
                order.amount = obj.sum;
                order.owner = obj.owner;
                filtered.push(order);
            });
            return filtered;
        },
        removeOrderModal: function(row) {
            let orders = row.side === 1 ? this.sellOrders : this.buyOrders;
            this.removeOrders = [];
            this.clone(orders).forEach((order) => {
                if (toMoney(order.price) === row.price && order.maker.id === this.userId) {
                    order.price = toMoney(order.price);
                    order.amount = toMoney(order.amount);
                    this.removeOrders.push(order);
                }
            });
            this.switchConfirmModal(true);
        },
        removeOrder: function() {
            let deleteOrdersUrl = this.$routing.generate('orders_cancel', {
                'market': this.removeOrders[0].market.hiddenName,
            });
            this.$axios.single.post(deleteOrdersUrl, {'order_data': this.removeOrders.map((order) => order.id)})
                .catch(() => {
                    this.$toasted.show('Service unavailable, try again later');
                });
        },
        switchConfirmModal: function(val) {
            this.confirmModal = val;
        },
        clone: function(orders) {
            return JSON.parse(JSON.stringify(orders));
        },
    },
};
</script>
