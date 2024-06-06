<template>
    <div class="container-fluid p-0 m-0">
        <div class="row p-0 m-0">
            <div class="col-12 col-lg-6 mt-3 pr-lg-2">
                <trade-buy-orders
                    @update-data="updateBuyOrders"
                    :full-orders-list="buyOrders"
                    :orders-list="filteredBuyOrders"
                    :orders-loaded="ordersLoaded"
                    :orders-updated="ordersUpdated"
                    :token-name="market.base.symbol"
                    :token-avatar="market.base.image.url"
                    :fields="fields"
                    :total-buy-orders="totalBuyOrders"
                    :service-unavailable="serviceUnavailable"
                    :basePrecision="priceSubunits"
                    :quotePrecision="market.quote.subunit"
                    :logged-in="loggedIn"
                    @modal="removeOrderModal"
                    :currency-mode="currencyMode"
                    :is-token="isToken"
                />
            </div>
            <div class="col-12 col-lg-6 mt-3 pl-lg-2">
                <trade-sell-orders
                    @update-data="updateSellOrders"
                    :full-orders-list="sellOrders"
                    :orders-list="filteredSellOrders"
                    :orders-loaded="ordersLoaded"
                    :orders-updated="ordersUpdated"
                    :market="market"
                    :fields="fields"
                    :total-sell-orders="totalSellOrders"
                    :service-unavailable="serviceUnavailable"
                    :basePrecision="priceSubunits"
                    :quotePrecision="market.quote.subunit"
                    :logged-in="loggedIn"
                    @modal="removeOrderModal"
                    :currency-mode="currencyMode"
                    :is-token="isToken"
                />
            </div>
        </div>
        <confirm-modal
            :visible="confirmModal"
            :show-image="false"
            no-title
            type="warning"
            @close="switchConfirmModal(false)"
            @confirm="removeOrder"
        >
            <div class="text-center text-break mb-3">
                {{ $t('trade.orders.confirm.body') }}
            </div>
            <b-table-simple
                hover
                small
                responsive
                sticky-header
                class="text-left"
                table-class="fix-layout table-min-width"
            >
                <b-thead sticky-header>
                    <b-tr>
                        <b-th class="bg-primary-dark text-white font-weight-normal">
                            {{ $t('trade.orders.price') }}
                        </b-th>
                        <b-th class="bg-primary-dark text-white font-weight-normal">
                            {{ $t('trade.orders.amount') }}
                        </b-th>
                    </b-tr>
                </b-thead>
                <b-tbody>
                    <b-tr v-for="order in removeOrders" :key="order.id">
                        <b-td class="remove-orders-td">
                            <div class="d-flex align-items-center">
                                <span class="font-weight-bold text-nowrap">{{ order.price | numberAbbr }}</span>
                                <span
                                    class="ml-1 d-flex align-items-center overflow-hidden"
                                    v-b-tooltip="getTooltipConfig(order.market.base.symbol)"
                                >
                                    <coin-avatar
                                        class="mr-1"
                                        :is-crypto="undefined === order.market.base.ownerId"
                                        :symbol="order.market.base.symbol"
                                        :is-user-token="undefined !== order.market.base.ownerId"
                                        :image="order.market.base.image"
                                    />
                                    <span class="text-truncate">
                                        {{ order.market.base.symbol | rebranding }}
                                    </span>
                                </span>
                            </div>
                        </b-td>
                        <b-td class="remove-orders-td">
                            <div class="d-flex align-items-center">
                                <span class="font-weight-bold text-nowrap">{{ order.amount | numberAbbr }}</span>
                                <span
                                    class="ml-1 d-flex align-items-center overflow-hidden"
                                    v-b-tooltip="getTooltipConfig(order.market.quote.symbol)"
                                >
                                    <coin-avatar
                                        class="mr-1"
                                        :is-crypto="undefined === order.market.quote.ownerId"
                                        :symbol="order.market.quote.symbol"
                                        :is-user-token="undefined !== order.market.quote.ownerId"
                                        :image="order.market.quote.image"
                                    />
                                    <span class="text-truncate">
                                        {{ order.market.quote.symbol | rebranding }}
                                    </span>
                                </span>
                            </div>
                        </b-td>
                    </b-tr>
                </b-tbody>
            </b-table-simple>
        </confirm-modal>
    </div>
</template>

<script>
import {BTableSimple, BTr, BThead, BTbody, BTd, BTh, VBTooltip} from 'bootstrap-vue';
import TradeBuyOrders from './TradeBuyOrders';
import TradeSellOrders from './TradeSellOrders';
import ConfirmModal from '../modal/ConfirmModal';
import Decimal from 'decimal.js';
import {formatMoney, toMoney, toMoneyWithTrailingZeroes} from '../../utils';
import {tokenDeploymentStatus, WSAPI, HTTP_ACCESS_DENIED} from '../../utils/constants';
import {
    RebrandingFilterMixin,
    NotificationMixin,
    FiltersMixin,
    NumberAbbreviationFilterMixin} from '../../mixins/';
import {mapMutations, mapGetters} from 'vuex';
import CoinAvatar from '../CoinAvatar';

export default {
    name: 'TradeOrders',
    mixins: [
        RebrandingFilterMixin,
        NotificationMixin,
        FiltersMixin,
        NumberAbbreviationFilterMixin,
    ],
    components: {
        CoinAvatar,
        TradeBuyOrders,
        TradeSellOrders,
        ConfirmModal,
        BTableSimple,
        BTr,
        BThead,
        BTd,
        BTh,
        BTbody,
    },
    directives: {
        'b-tooltip': VBTooltip,
    },
    props: {
        isToken: Boolean,
        ordersLoaded: Boolean,
        ordersUpdated: {
            type: Boolean,
            default: false,
        },
        buyOrders: [Array, Object],
        sellOrders: [Array, Object],
        totalSellOrders: [Array, Object, Decimal],
        totalBuyOrders: [Array, Object, Decimal],
        serviceUnavailable: Boolean,
        market: Object,
        userId: Number,
        loggedIn: Boolean,
        currencyMode: String,
    },
    data() {
        return {
            maxLengthToTruncate: 10,
            removeOrders: [],
            removedOrders: [],
            confirmModal: false,
            isSellSide: false,
            tokenDeploymentStatus,
            orderFields: [
                {
                    key: 'price',
                    label: this.$t('trade.orders.price'),
                },
                {
                    key: 'amount',
                    label: this.$t('trade.orders.amount'),
                },
            ],
        };
    },
    computed: {
        ...mapGetters('orders', {
            filteredBuyOrders: 'getBuyOrders',
            filteredSellOrders: 'getSellOrders',
        }),
        typeOrder() {
            return this.isSellSide ? this.$t('sell') : this.$t('buy');
        },
        fields() {
            const commonFields = [
                {
                    key: 'price',
                    label: this.$t('trade.orders.price'),
                    formatter: formatMoney,
                    tdClass: this.textDirection,
                    thClass: this.textDirection,
                },
                {
                    key: 'amount',
                    label: this.$t('trade.orders.amount'),
                    formatter: formatMoney,
                    tdClass: this.textDirection,
                    thClass: this.textDirection,
                },
                {
                    key: 'sum',
                    label: this.$t('trade.orders.sum'),
                    formatter: formatMoney,
                    tdClass: this.textDirection,
                    thClass: this.textDirection,
                },
            ];
            const tokensField = [
                {
                    key: 'trader',
                    label: this.$t('trade.orders.trader'),
                },
            ];

            return this.isToken ? [...tokensField, ...commonFields] : commonFields;
        },
        textDirection() {
            return this.isToken ? 'text-right' : 'text-left';
        },
        priceSubunits() {
            return this.isToken && this.market.quote.priceDecimals
                ? this.market.quote.priceDecimals
                : this.market.base.subunit;
        },
    },
    methods: {
        ...mapMutations('orders', ['setSellOrders', 'setBuyOrders']),
        getSymbol: function(data) {
            if (undefined === data.ownerId) {
                return data.symbol;
            }

            return data.cryptoSymbol;
        },
        getTooltipConfig: function(data) {
            return this.rebrandingFunc(data).length > this.maxLengthToTruncate
                ? {title: this.rebrandingFunc(data), boundary: 'window', customClass: 'tooltip-custom'}
                : null;
        },
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
                    trader: order.maker.profile.nickname,
                    price: toMoneyWithTrailingZeroes(order.price, this.priceSubunits),
                    amount: toMoney(order.amount, this.market.quote.subunit),
                    sum: toMoney(new Decimal(order.price).mul(order.amount).toString(), this.priceSubunits),
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
            const filtered = [];
            const accounted = [];

            const grouped = this.clone(orders).reduce((a, e) => {
                const price = parseFloat(e.price);

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
                const obj = e.reduce((a, e) => {
                    a.owner = a.owner || e.maker.id === this.userId;
                    a.orders.push(e);
                    a.sum = new Decimal(a.sum).add(e.amount);

                    const amount = a.orders.filter((order) => order.maker.id === e.maker.id)
                        .reduce((a, e) => new Decimal(a).add(e.amount), 0);

                    a.main.amount = amount;
                    a.main.order = e;

                    return a;
                }, {owner: false, orders: [], main: {order: null, amount: 0}, sum: 0});

                const order = obj.main.order;
                if (order) {
                    order.amount = obj.sum;
                    order.owner = obj.owner;
                    filtered.push(order);
                }
            });
            return filtered;
        },
        removeOrderModal: function(row) {
            this.isSellSide = WSAPI.order.type.SELL === row.side;
            const orders = this.isSellSide ? this.sellOrders : this.buyOrders;
            this.removeOrders = [];
            const accounted = [];

            this.clone(orders).forEach((order) => {
                if (
                    toMoney(order.price, this.priceSubunits) === toMoney(row.price, this.priceSubunits) &&
                    order.maker.id === this.userId
                ) {
                    order.price = toMoney(order.price, this.priceSubunits);
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
            const removeNow = this.removeOrders.map((order) => order.id);
            const deleteOrdersUrl = this.$routing.generate('orders_сancel', {
                base: this.market.base.symbol,
                quote: this.market.quote.symbol,
            });
            this.$axios.single.post(deleteOrdersUrl, {'orderData': removeNow})
                .then((response) => {
                    const data = response.data;

                    if (data.hasOwnProperty('error')) {
                        this.notifyError(data.error);
                    } else {
                        this.removedOrders = [...this.removedOrders, ...removeNow];
                    }
                })
                .catch((err) => {
                    if (HTTP_ACCESS_DENIED === err.response.status && err.response.data.message) {
                        this.notifyError(err.response.data.message);
                    } else {
                        this.notifyError(this.$t('toasted.error.service_unavailable'));
                    }
                    this.$logger.error('Remove order service unavailable', err);
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
    watch: {
        buyOrders: {
            handler: function(newOrders) {
                const orders = newOrders
                    ? this.sortOrders(this.ordersList(this.groupByPrice(newOrders)), false)
                    : [];

                this.setBuyOrders(orders);
            },
            deep: true,
            immediate: true,
        },
        sellOrders: {
            handler: function(newOrders, oldOrders) {
                const orders = newOrders
                    ? this.sortOrders(this.ordersList(this.groupByPrice(newOrders)), true)
                    : [];

                this.setSellOrders(orders);
            },
            deep: true,
            immediate: true,
        },
    },
};
</script>
