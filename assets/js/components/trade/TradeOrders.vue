<template>
    <div class="container p-0 m-0">
        <div class="row p-0 m-0">
            <div class="col-12 col-xl-6 pr-xl-2 mt-3">
                <trade-buy-orders
                    @update-data="updateBuyOrders"
                    :full-orders-list="buyOrders"
                    :orders-list="filteredBuyOrders"
                    :orders-loaded="ordersLoaded"
                    :orders-updated="ordersUpdated"
                    :token-name="market.base.symbol"
                    :fields="fields"
                    :total-buy-orders="totalBuyOrders"
                    :basePrecision="market.base.subunit"
                    :quotePrecision="market.quote.subunit"
                    :logged-in="loggedIn"
                    @modal="removeOrderModal"
                    :currency-mode="currencyMode"/>
            </div>
            <div class="col-12 col-xl-6 pl-xl-2 mt-3">
                <trade-sell-orders
                    @update-data="updateSellOrders"
                    :full-orders-list="sellOrders"
                    :orders-list="filteredSellOrders"
                    :orders-loaded="ordersLoaded"
                    :orders-updated="ordersUpdated"
                    :market="market"
                    :fields="fields"
                    :total-sell-orders="totalSellOrders"
                    :basePrecision="market.base.subunit"
                    :quotePrecision="market.quote.subunit"
                    :logged-in="loggedIn"
                    @modal="removeOrderModal"
                    :currency-mode="currencyMode"/>
            </div>
        </div>
        <confirm-modal
                :visible="confirmModal"
                :no-close="false"
                @close="switchConfirmModal(false)"
                @confirm="removeOrder"
        >
                <span class="text-white">
                    {{ $t('trade.orders.confirm.body_1') }}<br>
                    <span v-for="order in this.removeOrders" :key="order.id">
                    {{ $t('trade.orders.price') }} {{ order.price }}
                    {{ $t('trade.orders.amount') }} {{ order.amount }}<br>
                </span>
                {{ $t('trade.orders.confirm.body_2') }}
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
import {WSAPI} from '../../utils/constants';
import {RebrandingFilterMixin, NotificationMixin, LoggerMixin} from '../../mixins/';
import {mapMutations} from 'vuex';

export default {
    name: 'TokenTradeOrders',
    mixins: [
        RebrandingFilterMixin,
        NotificationMixin,
        LoggerMixin,
    ],
    components: {
        TradeBuyOrders,
        TradeSellOrders,
        ConfirmModal,
    },
    props: {
        ordersLoaded: Boolean,
        ordersUpdated: {
            type: Boolean,
            default: false,
        },
        buyOrders: [Array, Object],
        sellOrders: [Array, Object],
        totalSellOrders: [Array, Object],
        totalBuyOrders: [Array, Object],
        market: Object,
        userId: Number,
        loggedIn: Boolean,
        currencyMode: String,
    },
    data() {
        return {
            removeOrders: [],
            removedOrders: [],
            confirmModal: false,
            fields: [
                {
                    key: 'price',
                    label: this.$t('trade.orders.price'),
                    formatter: formatMoney,
                },
                {
                    key: 'amount',
                    label: this.$t('trade.orders.amount'),
                    formatter: formatMoney,
                },
                {
                    key: 'sum',
                    label: this.$t('trade.orders.sum'),
                    formatter: formatMoney,
                },
                {
                    key: 'trader',
                    label: this.$t('trade.orders.trader'),
                },
            ],
        };
    },
    computed: {
        filteredBuyOrders: function() {
            let orders = this.buyOrders ? this.sortOrders(this.ordersList(this.groupByPrice(this.buyOrders)), false) : [];
            this.setBuyOrders(orders);

            return orders;
        },
        filteredSellOrders: function() {
            let orders = this.sellOrders ? this.sortOrders(this.ordersList(this.groupByPrice(this.sellOrders)), true) : [];
            this.setSellOrders(orders);

            return orders;
        },
    },
    methods: {
        ...mapMutations('orders', ['setSellOrders', 'setBuyOrders']),
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
                    trader: order.maker.profile.nickname,
                    traderUrl: this.$routing.generate('profile-view', {nickname: order.maker.profile.nickname}),
                    side: order.side,
                    owner: order.owner,
                    orderId: order.id,
                    ownerId: order.maker.id,
                    highlightClass: '',
                    traderAvatar: order.maker.profile.image.avatar_small,
                };
            });
        },
        groupByPrice: function(orders) {
            let filtered = [];
            let accounted = [];

            let grouped = this.clone(orders).reduce((a, e) => {
                let price = parseFloat(e.price);

                if (a[price] === undefined) {
                    a[price] = [];
                }

                if (-1 === accounted.indexOf(e.id) && -1 === this.removedOrders.indexOf(e.id)) {
                    accounted.push(e.id);
                    a[price].push(e);
                }

                return a;
            }, {});

            Object.values(grouped).forEach((e) => {
                e.sort((a, b) => b.createdTimestamp - a.createdTimestamp);
                let obj = e.reduce((a, e) => {
                    a.owner = a.owner || e.maker.id === this.userId;
                    a.orders.push(e);
                    a.sum = new Decimal(a.sum).add(e.amount);

                    let amount = a.orders.filter((order) => order.maker.id === e.maker.id)
                        .reduce((a, e) => new Decimal(a).add(e.amount), 0);

                    a.main.amount = amount;
                    a.main.order = e;

                    return a;
                }, {owner: false, orders: [], main: {order: null, amount: 0}, sum: 0});

                let order = obj.main.order;
                order ? order.amount = obj.sum : '';
                order ? order.owner = obj.owner : '';
                order ? filtered.push(order) : '';
            });
            return filtered;
        },
        removeOrderModal: function(row) {
            let isSellSide = WSAPI.order.type.SELL === row.side;
            let orders = isSellSide ? this.sellOrders : this.buyOrders;
            this.removeOrders = [];
            let accounted = [];

            this.clone(orders).forEach((order) => {
                if (toMoney(order.price, this.market.base.subunit) === row.price && order.maker.id === this.userId) {
                    order.price = toMoney(order.price, this.market.base.subunit);
                    order.amount = toMoney(order.amount, this.market.quote.subunit);
                    if (-1 === accounted.indexOf(order.id) && -1 === this.removedOrders.indexOf(order.id)) {
                        accounted.push(order.id);
                        this.removeOrders.push(order);
                    }
                }
            });
            this.switchConfirmModal(true);
        },
        removeOrder: function() {
            let removeNow = this.removeOrders.map((order) => order.id);
            this.removedOrders = [...this.removedOrders, ...removeNow];
            let deleteOrdersUrl = this.$routing.generate('orders_сancel', {
                base: this.market.base.symbol,
                quote: this.market.quote.symbol,
            });
            this.$axios.single.post(deleteOrdersUrl, {'orderData': removeNow})
                .catch((err) => {
                    this.notifyError(this.$t('toasted.error.service_unavailable'));
                    this.sendLogs('error', 'Remove order service unavailable', err);
                });
        },
        switchConfirmModal: function(val) {
            this.confirmModal = val;
        },
        clone: function(orders) {
            return JSON.parse(JSON.stringify(orders));
        },
        sortOrders: function(orders, isSell) {
            return orders.sort((a, b) => {
                return isSell ?
                    parseFloat(a.price) - parseFloat(b.price) :
                    parseFloat(b.price) - parseFloat(a.price);
            });
        },
    },
};
</script>
