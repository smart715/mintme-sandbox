<template>
    <div class="container">
        <div class="row">
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
                        :orders-list="filteredBuyOrders"
                        :token-name="tokenName"
                        :fields="fields"
                        @modal="removeOrderModal"/>
                <template v-else>
                    <font-awesome-icon icon="circle-notch" spin class="loading-spinner d-block text-white mx-auto my-3"
                                       size="5x"/>
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
                    <font-awesome-icon icon="circle-notch" spin class="loading-spinner d-block text-white mx-auto my-3"
                                       size="5x"/>
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
                sum_web: {
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
                    sum_web: toMoney(new Decimal(order.price).mul(order.amount).toString()),
                    trader: order.maker != null ? this.truncateFullName(order.maker.profile) : 'Anonymous',
                    trader_url: this.$routing.generate('token_show', {
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
            let grouped = {};
            let owner = false;
            this.clone(orders).forEach( (item) => {
                if (grouped[item.price] === undefined) {
                    grouped[item.price] = [];
                }
                grouped[item.price].push(item);
            });
            for (let orders in grouped) {
                if (grouped.hasOwnProperty(orders)) {
                    let sum = grouped[orders].reduce((sum, order) => parseFloat(order.amount) + sum, 0);
                    grouped[orders].sort((first, second) => first.maker.id - second.maker.id);
                    grouped[orders].forEach((order, i, arr) => {
                        owner = owner === true || order.maker.id === this.userId;
                        if (arr[i-1] !== undefined && arr[i-1].maker.id === order.maker.id) {
                            order.amount = new Decimal(order.amount).add(arr[i-1].amount);
                        }
                    });
                    grouped[orders].sort((first, second) => parseFloat(second.amount) - parseFloat(first.amount));
                    grouped[orders][0].amount = sum;
                    grouped[orders][0].owner = owner;
                    filtered.push(grouped[orders][0]);
                }
            }
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
            let data = {'ids': this.removeOrders.map((order) => order.id)};
            this.$axios.single.post(deleteOrdersUrl, data)
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
