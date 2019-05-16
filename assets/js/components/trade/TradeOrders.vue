<template>
    <div class="container">
        <div class="row px-0 mx-0">
            <div class="col-12 col-xl-6 col-lg-12 pr-lg-2 pl-lg-0 mt-3">
                <trade-buy-orders
                        v-if="ordersLoaded"
                        @update-data="updateBuyOrders"
                        :orders-list="filteredBuyOrders"
                        :token-name="market.base.symbol"
                        :fields="fields"
                        :sort-by="fields.price.key"
                        :sort-desc="true"
                        :precision="market.base.subunit"
                        @modal="removeOrderModal"/>
                <template v-else>
                    <div class="p-5 text-center">
                        <font-awesome-icon icon="circle-notch" spin class="loading-spinner text-white" fixed-width />
                    </div>
                </template>
            </div>
            <div class="col-12 col-xl-6 col-lg-12 pr-lg-0 pl-lg-2 mt-3">
                <trade-sell-orders
                        v-if="ordersLoaded"
                        @update-data="updateSellOrders"
                        :orders-list="filteredSellOrders"
                        :token-name="market.quote.symbol"
                        :fields="fields"
                        :sort-by="fields.price.key"
                        :sort-desc="false"
                        :precision="market.quote.subunit"
                        @modal="removeOrderModal"/>
                <template v-else>
                    <div class="p-5 text-center">
                        <font-awesome-icon icon="circle-notch" spin class="loading-spinner text-white" fixed-width />
                    </div>
                </template>
            </div>
        </div>
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
    </div>
</template>

<script>
import TradeBuyOrders from './TradeBuyOrders';
import TradeSellOrders from './TradeSellOrders';
import ConfirmModal from '../modal/ConfirmModal';
import Decimal from 'decimal.js';
import {formatMoney, toMoney} from '../../utils';

export default {
    name: 'TokenTradeOrders',
    components: {
        TradeBuyOrders,
        TradeSellOrders,
        ConfirmModal,
    },
    props: {
        ordersLoaded: Boolean,
        buyOrders: [Array, Object],
        sellOrders: [Array, Object],
        market: Object,
        userId: Number,
    },
    data() {
        return {
            removeOrders: [],
            confirmModal: false,
            fields: {
                price: {
                    label: 'Price',
                    key: 'price',
                    formatter: formatMoney,
                },
                amount: {
                    label: 'Amount',
                    formatter: formatMoney,
                },
                sum: {
                    label: 'Sum ' + this.market.base.symbol,
                    formatter: formatMoney,
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
        updateBuyOrders: function({attach, resolve}) {
            return this.updateOrders(attach, 'buy', resolve);
        },
        updateSellOrders: function({attach, resolve}) {
            return this.updateOrders(attach, 'sell', resolve);
        },
        updateOrders: function(isAttached, type, resolve) {
            return this.$emit('update-data', {isAttached, type, resolve});
        },
        ordersList: function(orders) {
            return orders.map((order) => {
                return {
                    price: toMoney(order.price, this.market.base.subunit),
                    amount: toMoney(order.amount, this.market.quote.subunit),
                    sum: toMoney(new Decimal(order.price).mul(order.amount).toString(), this.market.base.subunit),
                    trader: order.maker.profile !== null && !order.maker.profile.anonymous
                        ? this.truncateFullName(order.maker.profile, order.owner)
                        : 'Anonymous',
                    traderFullName: order.maker.profile !== null && !order.maker.profile.anonymous
                        ? order.maker.profile.firstName + ' ' + order.maker.profile.lastName
                        : 'Anonymous',
                    traderUrl: order.maker.profile && !order.maker.profile.anonymous ?
                        this.$routing.generate('profile-view', {pageUrl: order.maker.profile.page_url}) :
                        '#',
                    side: order.side,
                    owner: order.owner,
                };
            });
        },
        truncateFullName: function(profile, owner) {
            let first = profile.firstName;
            let firstLength = first.length;
            let second = profile.lastName;
            if ((first + second).length > 5 && owner) {
                return first.length > 5
                    ? first.slice(0, 5) + '..'
                    : first + ' ' +second.slice(0, 5 - firstLength) + '..';
            } else if (((first + second).length > 7 && !owner)) {
                return first.length > 7
                    ? first.slice(0, 7) + '..'
                    : first + ' ' + second.slice(0, 7 - firstLength) + '..';
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
                if (toMoney(order.price, this.market.quote.subunit) === row.price && order.maker.id === this.userId) {
                    order.price = toMoney(order.price, this.market.quote.subunit);
                    order.amount = toMoney(order.amount, this.market.quote.subunit);
                    this.removeOrders.push(order);
                }
            });
            this.switchConfirmModal(true);
        },
        removeOrder: function() {
            let deleteOrdersUrl = this.$routing.generate('orders_сancel', {
                base: this.removeOrders[0].market.base.symbol,
                quote: this.removeOrders[0].market.quote.symbol,
            });
            this.$axios.single.post(deleteOrdersUrl, {'orderData': this.removeOrders.map((order) => order.id)})
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
