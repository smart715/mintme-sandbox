<template>
    <div class="px-0 pt-2 mt-4">
        <table-header
            :header="$t('page.wallet.trading_history.header')"
        />
        <div v-if="loaded">
            <template v-if="hasHistory">
                <div class="table-responsive fixed-head-table aligned-table text-nowrap px-3 py-4">
                    <b-table
                        thead-class="trading-head"
                        :items="history"
                        :fields="fieldsArray"
                        :sort-compare="$sortCompare(fields)"
                        :sort-by="fields.date.key"
                        :sort-desc="true"
                        sort-direction="desc"
                        sort-icon-left
                    >
                        <template v-slot:[`head(${fields.amount.key})`]="data">
                            <span class="sorting-arrows"></span>
                            {{ data.label }}
                        </template>
                        <template v-slot:[`head(${fields.price.key})`]="data">
                            <span class="sorting-arrows"></span>
                            {{ data.label }}
                        </template>
                        <template v-slot:[`head(${fields.total.key})`]="data">
                            <span class="sorting-arrows"></span>
                            {{ data.label }}
                        </template>
                        <template v-slot:[`head(${fields.fee.key})`]="data">
                            <span class="sorting-arrows"></span>
                            {{ data.label }}
                        </template>
                        <template v-slot:cell(crypto)="row">
                            <div>
                                <span v-if="isWebSymbol(row.value.base.symbol)">
                                    <coin-avatar
                                        :is-crypto="!row.value.quote.isToken"
                                        :is-user-token="row.value.quote.isToken"
                                        :symbol="row.value.quote.symbol"
                                        :image="row.value.quote.image"
                                    />
                                    <span
                                        v-if="row.item.blocked"
                                        class="text-muted"
                                    >
                                        {{ row.value.quote.name }}
                                    </span>
                                    <span v-else>
                                        <a
                                            :href="row.item.pairUrl"
                                            class="text-white"
                                        >
                                            {{ row.value.quote.name }}
                                        </a>
                                    </span>
                                </span>
                                <span v-else>
                                    <span v-if="row.item.blocked">
                                        <span class="text-muted">
                                            <coin-avatar
                                                :is-crypto="!row.value.quote.isToken"
                                                :is-user-token="row.value.quote.isToken"
                                                :symbol="row.value.quote.symbol"
                                                :image="row.value.quote.image"
                                            />
                                            {{ row.value.quote.name }} /
                                            <br class="break-line">
                                            <coin-avatar
                                                :is-crypto="true"
                                                :symbol="row.value.base.symbol"
                                            />
                                            {{ row.value.base.name }}
                                        </span>
                                    </span>
                                    <span v-else>
                                        <a
                                            :href="row.item.pairUrl"
                                            class="text-white"
                                        >
                                            <span>
                                                <coin-avatar
                                                    :is-crypto="!row.value.quote.isToken"
                                                    :is-user-token="row.value.quote.isToken"
                                                    :symbol="row.value.quote.symbol"
                                                    :image="row.value.quote.image"
                                                />
                                                {{ row.value.quote.name }} /
                                                <br class="break-line">
                                                <coin-avatar
                                                    :is-crypto="true"
                                                    :symbol="row.value.base.symbol"
                                                />
                                                {{ row.value.base.name }}
                                            </span>
                                        </a>
                                    </span>
                                </span>
                            </div>
                        </template>
                        <template v-slot:cell(fee)="row">
                            <span
                                v-if="row.value.showTooltip"
                                v-b-tooltip="row.value.tooltip"
                            >
                                {{ row.value.money }}
                            </span>
                            <span
                                v-else
                            >
                                {{ row.value.money }}
                            </span>
                            <coin-avatar
                                :is-crypto="true"
                                :symbol="row.value.avatar"
                            />
                            {{ row.value.currency }}
                        </template>
                        <template v-slot:cell(price)="row">
                            <span
                                v-if="row.item.price.subunit > GENERAL.precision"
                                v-b-tooltip="{title: row.value.value}"
                            >
                                {{ getPriceAbbreviation(row.value.value) }}
                            </span>
                            <span v-else>
                                {{ row.value.value }}
                            </span>
                        </template>
                        <template v-slot:cell(total)="row">
                            <price-converter
                                :amount="row.value.value"
                                :from="row.value.symbol"
                                :to="USD.symbol"
                                :is-token="row.value.isToken"
                                :subunit="subunitUsd"
                                symbol="$"
                            />
                        </template>
                        <template v-slot:cell(date)="row">
                            <div v-b-tooltip="{title: row.value.hoverFormatDate}">
                                {{ row.value.tableFormatDate }}
                            </div>
                        </template>
                    </b-table>
                </div>
                <div class="table-bottom d-flex justify-content-around mt-2">
                    <div v-if="loading" class="p-1 text-center">
                        <div class="spinner-border spinner-border-sm" role="status"></div>
                    </div>
                    <m-button
                        v-if="showSeeMoreButton"
                        type="secondary-rounded"
                        @click="updateTableData"
                    >
                        {{ $t('see_more') }}
                    </m-button>
                </div>
            </template>
            <div v-else>
                <p class="text-center p-5">
                    {{ $t('wallet.trading_history.no_deals') }}
                </p>
            </div>
        </div>
        <template v-else>
            <div class="p-5 text-center">
                <span v-if="serviceUnavailable">
                    {{ this.$t('toasted.error.service_unavailable_support') }}
                </span>
                <div v-else class="spinner-border spinner-border-sm" role="status"></div>
            </div>
        </template>
    </div>
</template>

<script>
import moment from 'moment';
import {MButton} from '../UI';
import {BTable, VBTooltip} from 'bootstrap-vue';
import {Decimal} from 'decimal.js';
import {
    toMoney,
    toMoneyWithTrailingZeroes,
    formatMoney,
    getPriceAbbreviation,
} from '../../utils';
import {
    GENERAL,
    WSAPI,
    WALLET_ITEMS_BATCH_SIZE,
    MINTME,
    USD,
    usdCustomPricePrecision,
} from '../../utils/constants';
import {
    FiltersMixin,
    RebrandingFilterMixin,
    PairNameMixin,
    OrderMixin,
} from '../../mixins/';
import TableHeader from './TableHeader';
import CoinAvatar from '../CoinAvatar';
import PriceConverter from '../PriceConverter';

export default {
    name: 'TradingHistory',
    components: {
        BTable,
        MButton,
        TableHeader,
        CoinAvatar,
        PriceConverter,
    },
    directives: {
        'b-tooltip': VBTooltip,
    },
    mixins: [
        FiltersMixin,
        RebrandingFilterMixin,
        PairNameMixin,
        OrderMixin,
    ],
    data() {
        return {
            serviceUnavailable: false,
            tableData: null,
            scrollListenerAutoStart: false,
            currentPage: 0,
            donations: 0,
            fullDonations: 0,
            perPage: WALLET_ITEMS_BATCH_SIZE,
            loading: false,
            allHistoryLoaded: false,
            subunitUsd: 2,
            USD,
            GENERAL,
            fields: {
                date: {
                    key: 'date',
                    label: this.$t('wallet.trading_history.table.date'),
                    sortable: true,
                    formatter: (date) => {
                        return {
                            tableFormatDate: moment.unix(date).format(GENERAL.dateTimeFormatTable),
                            hoverFormatDate: moment.unix(date).format(GENERAL.dateTimeFormat),
                        };
                    },
                },
                side: {
                    key: 'side',
                    label: this.$t('wallet.trading_history.table.type'),
                    sortable: true,
                    type: 'string',
                },
                crypto: {
                    key: 'crypto',
                    label: this.$t('wallet.trading_history.table.name'),
                    sortable: true,
                    class: 'pair-cell',
                },
                amount: {
                    key: 'amount',
                    label: this.$t('wallet.trading_history.table.amount'),
                    sortable: true,
                    formatter: (val) => '0' === val ? '-' : val,
                    type: 'numeric',
                    tdClass: 'text-right',
                    thClass: 'text-right sorting-arrows-th',
                },
                price: {
                    key: 'price',
                    label: this.$t('wallet.trading_history.table.price'),
                    sortable: true,
                    formatter: (val) => '0' === val ? '-' : val,
                    type: 'numeric',
                    tdClass: 'text-right',
                    thClass: 'text-right sorting-arrows-th',
                },
                total: {
                    key: 'total',
                    label: this.$t('wallet.trading_history.table.total_cost'),
                    sortable: true,
                    type: 'totalcost',
                    tdClass: 'text-right',
                    thClass: 'text-right sorting-arrows-th',
                },
                fee: {
                    key: 'fee',
                    label: this.$t('wallet.trading_history.table.fee'),
                    sortable: true,
                    type: 'fee',
                    tdClass: 'text-right',
                    thClass: 'text-right sorting-arrows-th',
                },
            },
            getPriceAbbreviation,
        };
    },
    computed: {
        loaded: function() {
            return null !== this.tableData;
        },
        hasHistory: function() {
            return !!(Array.isArray(this.tableData) && this.tableData.length);
        },
        history: function() {
            return this.tableData.map((history) => {
                const isDonationOrder = 0 === history.orderId || 0 === history.dealOrderId;
                const isQuoteToken = this.isToken(history.market.quote);
                return {
                    date: history.timestamp,
                    side: this.getSideByType(history.side, isDonationOrder),
                    crypto: {
                        quote: {
                            symbol: history.market.quote.symbol,
                            name: this.truncateFunc(this.rebrandingFunc(history.market.quote), 15),
                            isToken: isQuoteToken,
                            image: history.market.quote.image,
                        },
                        base: {
                            symbol: history.market.base.symbol,
                            name: this.rebrandingFunc(history.market.base),
                            avatar: history.market.base,
                        },
                        isDonationOrder,
                        isToken: isQuoteToken,
                    },
                    amount: toMoneyWithTrailingZeroes(history.amount, history.market.quote.subunit),
                    // For donation orders, history.price is the total cost.
                    price: {
                        value: isDonationOrder
                            ? '0'
                            : toMoneyWithTrailingZeroes(history.price, this.getPriceSubunits(history.market)),
                        subunit: this.getPriceSubunits(history.market),
                    },
                    total: {
                        value: formatMoney(this.calculateTotalCost(history, isDonationOrder, isQuoteToken)),
                        symbol: history.market.base.symbol,
                        currency: this.rebrandingFunc(history.market.base),
                    },
                    fee: this.createTicker(history.fee, history, isQuoteToken),
                    pairUrl: this.generatePairUrl(history.market),
                    blocked: history.market.quote.hasOwnProperty('blocked') ? history.market.quote.blocked : false,
                };
            });
        },
        fieldsArray: function() {
            return Object.values(this.fields);
        },
        showSeeMoreButton: function() {
            return !this.loading
                && !this.allHistoryLoaded
                && this.hasHistory;
        },
        nextPage: function() {
            return this.currentPage + 1;
        },
        currencyMode: function() {
            return localStorage.getItem('_currency_mode');
        },
    },
    mounted: function() {
        this.updateTableData();
    },
    methods: {
        getPriceSubunits(market) {
            return market.quote.hasOwnProperty('priceDecimals') && null !== market.quote.priceDecimals
                ? market.quote.priceDecimals
                : market.base.subunit;
        },
        updateTableData: async function() {
            this.loading = true;

            try {
                const response = await this.$axios.retry.get(this.$routing.generate('executed_user_orders', {
                    page: this.nextPage,
                    donations: this.donations,
                    fullDonations: this.fullDonations,
                }));
                const orders = response.data;

                orders.forEach((order) => {
                    if (0 === order.id) {
                        this.donations++;
                    }

                    if (0 === order.id && 0 === parseFloat(order.amount)) {
                        this.fullDonations++;
                    }
                });

                this.tableData = null === this.tableData
                    ? response.data
                    : this.tableData.concat(response.data);

                if (response.data.length < this.perPage) {
                    this.allHistoryLoaded = true;
                }

                if (0 < response.data.length) {
                    this.currentPage++;
                }
            } catch (err) {
                this.serviceUnavailable = true;
                this.$logger.error('Service unavailable. Can not update trading history', err);
            }

            this.loading = false;
        },
        generatePairUrl: function(market) {
            if (market.quote.hasOwnProperty('exchangeble') && market.quote.exchangeble && market.quote.tradable) {
                return this.$routing.generate('coin', {
                    base: this.rebrandingFunc(market.base.symbol),
                    quote: this.rebrandingFunc(market.quote.symbol),
                    tab: 'trade',
                });
            }

            return this.$routing.generate('token_show_trade', {
                name: market.quote.name,
                crypto: this.rebrandingFunc(market.base.symbol),
            });
        },
        isToken(symbol) {
            return undefined === symbol?.isToken;
        },
        createTicker: function(amount, history, isToken) {
            if (!isToken) {
                return {
                    money: WSAPI.order.type.BUY === history.side
                        ? toMoneyWithTrailingZeroes(amount, history.market.quote.subunit)
                        : toMoneyWithTrailingZeroes(amount, this.getPriceSubunits(history.market)),
                    avatar: WSAPI.order.type.BUY === history.side
                        ? history.market.quote.symbol
                        : history.market.base.symbol,
                    currency: WSAPI.order.type.BUY === history.side
                        ? this.rebrandingFunc(history.market.quote.symbol)
                        : this.rebrandingFunc(history.market.base.symbol),
                };
            }

            const feeSubunits = history.market.quote.priceDecimals
                ? usdCustomPricePrecision
                : history.market.base.subunit;

            const shouldShowAbbreviation = feeSubunits > GENERAL.precision;

            return {
                showTooltip: shouldShowAbbreviation,
                tooltip: toMoneyWithTrailingZeroes(amount, feeSubunits),
                money: shouldShowAbbreviation
                    ? getPriceAbbreviation(amount)
                    : toMoneyWithTrailingZeroes(amount, feeSubunits),
                avatar: history.market.base.symbol,
                currency: this.rebrandingFunc(history.market.base.symbol),
            };
        },
        /**
         * @param {object} history
         * @param {boolean} isDonationOrder
         * @param {boolean} isToken
         * @return {string}
         */
        calculateTotalCost: function(history, isDonationOrder, isToken) {
            let cost = isDonationOrder
                ? new Decimal(history.price)
                : Decimal.mul(history.price, history.amount);

            if (WSAPI.order.type.BUY === history.side && isToken) {
                cost = cost.add(history.fee);
            }

            return toMoney(cost.toString(), this.getPriceSubunits(history.market));
        },
        isWebSymbol(symbol) {
            return MINTME.symbol === this.rebrandingFunc(symbol.name);
        },
    },
};
</script>
