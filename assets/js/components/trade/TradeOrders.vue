<template>
    <div class="container p-0 m-0">
        <div class="row p-0 m-0">
            <div class="col-12 col-xl-6 pr-xl-2 mt-3">
                <trade-buy-orders
                    @update-data="updateBuyOrders"
                    :full-orders-list="allBuyOrders"
                    :orders-list="filteredBuyOrders"
                    :orders-loaded="ordersLoaded"
                    :token-name="market.base.symbol"
                    :fields="fields"
                    :basePrecision="market.base.subunit"
                    :quotePrecision="market.quote.subunit"
                    :logged-in="loggedIn"
                    @modal="removeOrderModal"/>
            </div>
            <div class="col-12 col-xl-6 pl-xl-2 mt-3">
                <trade-sell-orders
                    @update-data="updateSellOrders"
                    :full-orders-list="allSellOrders"
                    :orders-list="filteredSellOrders"
                    :orders-loaded="ordersLoaded"
                    :token-name="market.quote.symbol"
                    :fields="fields"
                    :basePrecision="market.base.subunit"
                    :quotePrecision="market.quote.subunit"
                    :logged-in="loggedIn"
                    @modal="removeOrderModal"/>
            </div>
        </div>
        <confirm-modal
                :visible="confirmModal"
                :no-close="false"
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
import {RebrandingFilterMixin, NotificationMixin, LoggerMixin} from '../../mixins/';

export default {
    name: 'TokenTradeOrders',
    mixins: [RebrandingFilterMixin, NotificationMixin, LoggerMixin],
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
        loggedIn: Boolean,
    },
    data() {
        return {
            removeOrders: [],
            confirmModal: false,
            fields: [
                {
                    key: 'price',
                    label: 'Price',
                    formatter: formatMoney,
                },
                {
                    key: 'amount',
                    label: 'Amount',
                    formatter: formatMoney,
                },
                {
                    key: 'sum',
                    label: 'Sum ' + this.rebrandingFunc(this.market.base.symbol),
                    formatter: formatMoney,
                },
                {
                    key: 'trader',
                    label: 'Trader',
                },
            ],
        };
    },
    computed: {
        filteredBuyOrders: function() {
            return this.buyOrders ? this.ordersList(this.groupByPrice(this.buyOrders)) : [];
        },
        filteredSellOrders: function() {
            return this.sellOrders ? this.ordersList(this.groupByPrice(this.sellOrders)) : [];
        },
        allBuyOrders: function() {
            return this.buyOrders;
        },
        allSellOrders: function() {
            return this.sellOrders;
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
                        ? this.traderFullName(order.maker.profile)
                        : 'Anonymous',
                    traderFullName: order.maker.profile !== null && !order.maker.profile.anonymous
                        ? order.maker.profile.firstName + ' ' + order.maker.profile.lastName
                        : 'Anonymous',
                    traderUrl: order.maker.profile && !order.maker.profile.anonymous ?
                        this.$routing.generate('profile-view', {pageUrl: order.maker.profile.page_url}) :
                        '#',
                    side: order.side,
                    owner: order.owner,
                    isAnonymous: !order.maker.profile || order.maker.profile.anonymous,
                    orderId: order.id,
                    ownerId: order.maker.id,
                };
            });
        },
        traderFullName: function(profile) {
            return profile.firstName + ' ' + profile.lastName;
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
            let isSellSide = row.side === 1;
            let orders = isSellSide ? this.sellOrders : this.buyOrders;
            this.removeOrders = [];

            this.clone(orders).forEach((order) => {
                if (toMoney(order.price, this.market.base.subunit) === row.price && order.maker.id === this.userId) {
                    order.price = toMoney(order.price, this.market.base.subunit);
                    order.amount = toMoney(order.amount, this.market.quote.subunit);
                    this.removeOrders.push(order);
                }
            });
            this.switchConfirmModal(true);
        },
        removeOrder: function() {
            let deleteOrdersUrl = this.$routing.generate('orders_сancel', {
                base: this.market.base.symbol,
                quote: this.market.quote.symbol,
            });
            this.$axios.single.post(deleteOrdersUrl, {'orderData': this.removeOrders.map((order) => order.id)})
                .catch((err) => {
                    this.notifyError('Service unavailable, try again later');
                    this.sendLogs('error', 'Remove order service unavailable', err);
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
