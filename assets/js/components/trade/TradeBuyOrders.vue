<template>
    <div class="h-100">
        <div class="card h-100">
            <div class="card-header my-2 d-flex flex-wrap align-items-center justify-content-between">
                <div
                    class="font-size-3 font-weight-semibold header-highlighting"
                    v-html="$t('trade.buy_orders.header')"
                ></div>
                <span class="card-header-icon d-flex flex-wrap aling-items-center font-weight-semibold">
                    {{ $t('trade.buy_orders.header_total') }}
                    <span class="ml-1 text-primary">
                        {{ totalSum | formatMoney }}
                        <coin-avatar
                            :symbol="tokenName"
                            :is-crypto="true"
                        />
                        {{ tokenName | rebranding }}
                    </span>
                    <guide
                        :key="tooltipKey"
                        class="font-size-2 d-block ml-1 mtn-2"
                    >
                        <template slot="header">
                            {{ $t('trade.buy_orders.guide_header') }}
                        </template>
                        <template slot="body">
                            {{ $t('trade.buy_orders.guide_body') }}
                            <coin-avatar
                                :symbol="tokenName"
                                :is-crypto="true"
                            />
                            {{ tokenName | rebranding }}.
                        </template>
                    </guide>
                </span>
            </div>
            <div class="card-body p-0">
                <div v-if="serviceUnavailable" class="p-5 text-center text-white">
                    {{ this.$t('toasted.error.service_unavailable_short') }}
                </div>
                <template v-else-if="ordersLoaded">
                    <div
                        class="table-orders table-sticky-head overflow-auto custom-scrollbar mx-3 mb-3"
                        ref="table"
                    >
                        <b-table
                            v-if="hasOrders"
                            :items="ordersToShow"
                            :fields="fields"
                            class="table-orders"
                            :tbody-tr-class="rowClass"
                            :tbody-tr-attr="rowAttribute"
                            @row-clicked="orderClicked"
                        >
                            <template v-slot:cell(price)="row">
                                <span
                                    class="buy-order-price"
                                    v-b-tooltip="{
                                        title: currencyConvert(row.value, rate, usdPriceSubunits),
                                        boundary:'viewport'
                                    }"
                                >
                                    {{ row.value }}
                                </span>
                            </template>
                            <template v-slot:cell(sum)="row">
                                <div
                                    v-if="row.item.owner && !isToken"
                                    class="d-flex justify-content-between"
                                >
                                    <span v-text="sum(row.value, rate)"></span>
                                    <a
                                        class="ml-2"
                                        v-b-tooltip="modalTooltip"
                                        @click="removeOrderModal(row.item)"
                                    >
                                        <font-awesome-icon
                                            icon="times"
                                            class="text-danger c-pointer"
                                        />
                                    </a>
                                </div>
                                <span
                                    v-else
                                    v-text="sum(row.value, rate)"
                                ></span>
                            </template>
                            <template v-slot:cell(trader)="row">
                                <div class="d-flex flex-nowrap">
                                    <div>
                                        <a
                                            :href="row.item.traderUrl"
                                            class="d-flex flex-nowrap align-items-center text-link"
                                        >
                                            <img
                                                :src="row.item.traderAvatar"
                                                class="rounded-circle d-block flex-grow-0 pointer-events-none mr-1"
                                                alt="avatar">
                                            <span
                                                v-b-tooltip="popoverConfig"
                                                v-on:mouseover="mouseoverHandler(
                                                    fullOrdersList,
                                                    basePrecision,
                                                    row.item.price
                                                )"
                                            >
                                                {{ row.value | truncate(12) }}
                                            </span>
                                        </a>
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <a
                                            v-if="row.item.owner"
                                            v-b-tooltip="modalTooltip"
                                            class="ml-2"
                                            @click="removeOrderModal(row.item)"
                                        >
                                            <font-awesome-icon icon="times" class="text-danger c-pointer" />
                                        </a>
                                    </div>
                                </div>
                            </template>
                        </b-table>
                        <div v-else>
                            <p class="text-center p-5">{{ $t('trade.buy_orders.no_orders') }}</p>
                        </div>
                    </div>
                </template>
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
import {library} from '@fortawesome/fontawesome-svg-core';
import {faCircleNotch, faTimes} from '@fortawesome/free-solid-svg-icons';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import Decimal from 'decimal.js';
import {mapGetters} from 'vuex';
import {BTable, VBTooltip} from 'bootstrap-vue';
import Guide from '../Guide';
import {toMoney, removeSpaces} from '../../utils';
import {USD, usdSign} from '../../utils/constants.js';
import {
    FiltersMixin,
    LazyScrollTableMixin,
    MoneyFilterMixin,
    OrderClickedMixin,
    RebrandingFilterMixin,
    TraderHoveredMixin,
    OrderHighlights,
    CurrencyConverter,
    OrdersFillWidthMixin,
} from '../../mixins/';
import CoinAvatar from '../CoinAvatar';

library.add(faCircleNotch, faTimes);

export default {
    name: 'TradeBuyOrders',
    components: {
        BTable,
        Guide,
        FontAwesomeIcon,
        CoinAvatar,
    },
    directives: {
        'b-tooltip': VBTooltip,
    },
    mixins: [
        FiltersMixin,
        LazyScrollTableMixin,
        MoneyFilterMixin,
        OrderClickedMixin,
        RebrandingFilterMixin,
        TraderHoveredMixin,
        OrderHighlights,
        CurrencyConverter,
        OrdersFillWidthMixin,
    ],
    props: {
        fullOrdersList: [Array],
        ordersList: [Array],
        tokenName: String,
        fields: Array,
        totalBuyOrders: [Array, Object, Decimal],
        basePrecision: Number,
        loggedIn: Boolean,
        ordersLoaded: Boolean,
        serviceUnavailable: Boolean,
        ordersUpdated: {
            type: Boolean,
            default: false,
        },
        currencyMode: String,
        isToken: Boolean,
    },
    data() {
        return {
            tableData: this.ordersList,
            tooltipKey: 0,
        };
    },
    mounted: function() {
        this.startScrollListeningOnce(this.ordersList);
    },
    computed: {
        ...mapGetters('rates', [
            'getRates',
        ]),
        ...mapGetters('market', {
            market: 'getCurrentMarket',
        }),
        usdPriceSubunits() {
            return this.isToken && this.market.quote.priceDecimals
                ? this.market.quote.priceDecimals
                : USD.subunit;
        },
        totalSum: function() {
            if (this.totalBuyOrders) {
                return toMoney(this.totalBuyOrders, this.basePrecision);
            } else {
                return toMoney(
                    this.tableData.reduce(
                        (currentSum, order) => new Decimal(currentSum).add(order.sum).toString(),
                        '0',
                    ),
                    this.quotePrecision
                );
            }
        },
        hasOrders: function() {
            return 0 < this.tableData.length;
        },
        rate: function() {
            return (this.getRates[this.tokenName] || [])[USD.symbol] || 1;
        },
        ordersToShow: function() {
            return this.ordersWithFillWidth;
        },
        modalTooltip: function() {
            return {
                title: this.$t('tooltip.remove'),
                boundary: 'viewport',
            };
        },
    },
    methods: {
        sum: function(value, rate) {
            return this.currencyConvert(value, rate, 2);
        },
        removeOrderModal: function(row) {
            this.$emit('modal', row);
        },
        updateTableData: function(attach = false) {
            return new Promise((resolve) => {
                this.$emit('update-data', {attach, resolve});
            });
        },
        rowClass: function(item, type) {
            return 'row' === type && item.highlightClass
                ? `buy-order ${item.highlightClass}`
                : 'buy-order';
        },
        rowAttribute: function(order, type) {
            return 'row' === type
                ? {'style': this.orderFillingStyle(order.fillWidth)}
                : null;
        },
        currencyConvert: function(val, rate, subunit) {
            return this.currencyConversion(removeSpaces(val), rate, usdSign, subunit, this.market.base.subunit);
        },
    },
    watch: {
        ordersList: function(newOrders) {
            const delayOrdersUpdating = this.ordersUpdated
                ? this.handleOrderHighlights(this.tableData, newOrders)
                : false;

            if (delayOrdersUpdating) {
                setTimeout(()=> this.tableData = newOrders, 1000);
            } else {
                this.tableData = newOrders;
            }

            setTimeout(()=> this.tableData.forEach((order) => order.highlightClass = ''), 1000);
        },
        tokenName: function() {
            this.tooltipKey += 1;
        },
    },
    filters: {
        currencyConvert: function(val, rate, subunit) {
            return this.currencyConversion(removeSpaces(val), rate, usdSign, subunit, this.market.base.subunit);
        },
    },
};
</script>
