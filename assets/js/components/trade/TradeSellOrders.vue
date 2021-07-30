<template>
    <div class="h-100">
        <div class="card h-100">
            <div class="card-header">
                {{ $t('trade.sell_orders.header') }}
                <span class="card-header-icon">
                    Total: {{ total | formatMoney }}
                    <span v-if="shouldTruncate"
                          v-b-tooltip="{title: rebrandingFunc(market.quote), boundary:'window', customClass:'tooltip-custom'}">
                        {{ market.quote | rebranding | truncate(12) }}
                    </span>
                    <span v-else>
                        {{ market.quote | rebranding }}
                    </span>
                    <guide>
                        <template slot="header">
                            {{ $t('trade.sell_orders.guide_header') }}
                        </template>
                        <template slot="body">
                            {{ $t('trade.sell_orders.guide_body', translationsContext) }}
                        </template>
                    </guide>
                </span>
            </div>
            <div class="card-body p-0">
                <template v-if="ordersLoaded">
                    <div class="table-responsive fixed-head-table mb-0" ref="table">
                        <b-table v-if="hasOrders"
                            @row-clicked="orderClicked"
                            :items="tableData"
                            :fields="fields"
                            :tbody-tr-class="rowClass"
                            :tbody-class="'table-orders'"
                        >
                            <template v-slot:cell(price)="row">
                                <div class="d-flex flex-row flex-nowrap justify-content-between w-100">
                                    <div class="col-11 pl-0 ml-0">
                                        <span class="d-inline-block truncate-name flex-grow-1">
                                            <span
                                                v-b-tooltip="{title: currencyConvert(row.value, rate, 2), boundary:'viewport'}">
                                                {{ row.value }}
                                            </span>
                                        </span>
                                    </div>
                                </div>
                            </template>
                            <template v-slot:cell(sum)="row">
                                <div class="d-flex flex-row flex-nowrap justify-content-between w-100">
                                    <div class="col-11 pl-0 ml-0">
                                        <span
                                            class="d-inline-block truncate-name flex-grow-1"
                                            v-text="sum(row.value, rate)">
                                        </span>
                                    </div>
                                </div>
                            </template>
                            <template v-slot:cell(trader)="row">
                                <div class="d-flex flex-row flex-nowrap justify-content-between w-100">
                                    <div class="col-11 pl-0 ml-0">
                                        <a
                                            :href="row.item.traderUrl"
                                            class="d-flex flex-row flex-nowrap justify-content-between w-100 text-white"
                                        >
                                            <img
                                                :src="row.item.traderAvatar"
                                                class="rounded-circle d-block flex-grow-0 pointer-events-none mr-1"
                                                alt="avatar">
                                            <span class="d-inline-block truncate-name flex-grow-1">
                                                <span
                                                    v-b-tooltip="popoverConfig"
                                                    v-on:mouseover="mouseoverHandler(fullOrdersList, basePrecision, row.item.price)"
                                                >
                                                    {{ row.value }}
                                                </span>
                                            </span>
                                        </a>
                                    </div>
                                    <div class="col-1 pull-right pl-0 ml-0">
                                        <a
                                            v-if="row.item.owner"
                                            class="d-inline-block flex-grow-0"
                                            @click="removeOrderModal(row.item)"
                                        >
                                            <font-awesome-icon icon="times" class="text-danger c-pointer" />
                                        </a>
                                    </div>
                                </div>
                            </template>
                        </b-table>
                        <div v-else>
                            <p class="text-center p-5">{{ $t('trade.sell_orders.no_orders') }}</p>
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
import {toMoney, removeSpaces, currencyConversion} from '../../utils';
import {USD, usdSign, currencyModes} from '../../utils/constants.js';
import {
    LazyScrollTableMixin,
    FiltersMixin,
    MoneyFilterMixin,
    OrderClickedMixin,
    RebrandingFilterMixin,
    TraderHoveredMixin,
    OrderHighlights,
} from '../../mixins/';

library.add(faCircleNotch, faTimes);

export default {
    name: 'TradeSellOrders',
    components: {
        Guide,
        BTable,
        FontAwesomeIcon,
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
    ],
    props: {
        fullOrdersList: [Array],
        ordersList: [Array],
        market: Object,
        fields: Array,
        totalSellOrders: [Array, Object],
        basePrecision: Number,
        loggedIn: Boolean,
        ordersLoaded: Boolean,
        ordersUpdated: {
            type: Boolean,
            default: false,
        },
        currencyMode: String,
    },
    data() {
        return {
            tableData: this.ordersList,
            currencyModes,
        };
    },
    mounted: function() {
        this.startScrollListeningOnce(this.ordersList);
    },
    computed: {
        ...mapGetters('rates', [
            'getRates',
        ]),
        shouldTruncate: function() {
            return this.market.quote.symbol.length > 12;
        },
        total: function() {
            if (this.totalSellOrders) {
                return toMoney(this.totalSellOrders, this.quotePrecision);
            } else {
                return toMoney(this.tableData.reduce((sum, order) =>
                    new Decimal(order.amount).add(sum), 0), this.quotePrecision
                );
            }
        },
        hasOrders: function() {
            return this.tableData.length > 0;
        },
        translationsContext: function() {
            return {
                name: this.rebrandingFunc(this.tokenName),
            };
        },
        rate: function() {
            return (this.getRates[this.market.base.symbol] || [])[USD.symbol] || 1;
        },
    },
    methods: {
        sum: function(value, rate) {
            return this.currencyMode === this.currencyModes.usd.value ?
                this.currencyConvert(value, rate, 2) :
                value;
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
                ? item.highlightClass
                : '';
        },
        currencyConvert: function(val, rate, subunit) {
            return currencyConversion(removeSpaces(val), rate, usdSign, subunit);
        },
    },
    watch: {
        ordersList: function(newOrders) {
            let delayOrdersUpdating = this.ordersUpdated
                ? this.handleOrderHighlights(this.tableData, newOrders)
                : false;

            if (delayOrdersUpdating) {
                setTimeout(()=> this.tableData = newOrders, 1000);
            } else {
                this.tableData = newOrders;
            }

            setTimeout(()=> this.tableData.forEach((order) => order.highlightClass = ''), 1000);
        },
    },
    filters: {
        currencyConvert: function(val, rate, subunit) {
            return currencyConversion(removeSpaces(val), rate, usdSign, subunit);
        },
    },
};
</script>

