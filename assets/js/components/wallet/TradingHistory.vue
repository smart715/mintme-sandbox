<template>
    <div class="px-0 pt-2">
        <template v-if="loaded">
            <div class="table-responsive table-restricted trading-history" ref="table" v-if="hasHistory">
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
                    <template v-slot:cell(name)="row">
                        <div v-if="row.value.full.length > 17"
                            v-b-tooltip="{title: row.value.full, boundary: 'viewport'}"
                        >
                            <span v-if="row.item.blocked">
                                <span class="text-muted">
                                    {{ row.value.truncate }}
                                </span>
                            </span>
                            <span v-else>
                                <a :href="row.item.pairUrl" class="text-white">
                                    {{ row.value.truncate }}
                                </a>
                            </span>

                        </div>
                        <div v-else>
                            <span v-if="row.item.blocked">
                                <span class="text-muted">
                                    {{ row.value.full }}
                                </span>
                            </span>
                            <span v-else>
                                <a :href="row.item.pairUrl" class="text-white">
                                    {{ row.value.full }}
                                </a>
                            </span>
                        </div>
                    </template>
                </b-table>
            </div>
            <div v-if="loading" class="p-1 text-center">
                <font-awesome-icon icon="circle-notch" spin class="loading-spinner" fixed-width />
            </div>
            <div v-else-if="!hasHistory">
                <p class="text-center p-5">{{ $t('wallet.trading_history.no_deals') }}</p>
            </div>
        </template>
        <template v-else>
            <div class="p-5 text-center">
                <font-awesome-icon icon="circle-notch" spin class="loading-spinner" fixed-width />
            </div>
        </template>
    </div>
</template>

<script>
import {library} from '@fortawesome/fontawesome-svg-core';
import {faCircleNotch} from '@fortawesome/free-solid-svg-icons';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import moment from 'moment';
import {BTable, VBTooltip} from 'bootstrap-vue';
import {Decimal} from 'decimal.js';
import {toMoney, formatMoney} from '../../utils';
import {
    GENERAL,
    WSAPI,
    BTC,
    MINTME,
    webBtcSymbol,
    webEthSymbol,
    webUsdcSymbol,
} from '../../utils/constants';
import {
    FiltersMixin,
    LazyScrollTableMixin,
    RebrandingFilterMixin,
    LoggerMixin,
    PairNameMixin,
    OrderMixin,
} from '../../mixins/';

library.add(faCircleNotch);

export default {
    name: 'TradingHistory',
    components: {
        BTable,
        FontAwesomeIcon,
    },
    directives: {
        'b-tooltip': VBTooltip,
    },
    mixins: [
        FiltersMixin,
        LazyScrollTableMixin,
        RebrandingFilterMixin,
        LoggerMixin,
        PairNameMixin,
        OrderMixin,
    ],
    data() {
        return {
            tableData: null,
            currentPage: 1,
            donations: 0,
            fields: {
                date: {
                    key: 'date',
                    label: this.$t('wallet.trading_history.table.date'),
                    sortable: true,
                    type: 'date',
                },
                side: {
                    key: 'side',
                    label: this.$t('wallet.trading_history.table.type'),
                    sortable: true,
                    type: 'string',
                },
                name: {
                    key: 'name',
                    label: this.$t('wallet.trading_history.table.name'),
                    sortable: true,
                    class: 'pair-cell',
                    formatter: (name) => {
                        return {
                            full: name,
                            truncate: this.truncateFunc(name, 17),
                        };
                    },
                },
                amount: {
                    key: 'amount',
                    label: this.$t('wallet.trading_history.table.amount'),
                    sortable: true,
                    formatter: formatMoney,
                    type: 'numeric',
                },
                price: {
                    key: 'price',
                    label: this.$t('wallet.trading_history.table.price'),
                    sortable: true,
                    formatter: (val) => '0' === val ? '-' : toMoney(val),
                    type: 'numeric',
                },
                total: {
                    key: 'total',
                    label: this.$t('wallet.trading_history.table.total_cost'),
                    sortable: true,
                    formatter: formatMoney,
                    type: 'numeric',
                },
                fee: {
                    key: 'fee',
                    label: this.$t('wallet.trading_history.table.fee'),
                    sortable: true,
                    formatter: formatMoney,
                    type: 'numeric',
                },
            },
        };
    },
    computed: {
        loaded: function() {
            return this.tableData !== null;
        },
        hasHistory: function() {
            return !!(Array.isArray(this.tableData) && this.tableData.length);
        },
        history: function() {
            return this.tableData.map((history) => {
                let isDonationOrder = 0 === history.orderId || 0 === history.dealOrderId;

                return {
                    date: moment.unix(history.timestamp).format(GENERAL.dateTimeFormat),
                    side: this.getSideByType(history.side, isDonationOrder),
                    name: isDonationOrder ? this.rebrandingFunc(history.market.quote) : this.pairNameFunc(
                        this.rebrandingFunc(history.market.base),
                        this.rebrandingFunc(history.market.quote)
                    ),
                    amount: toMoney(history.amount, history.market.base.subunit),
                    price: toMoney(history.price, history.market.base.subunit),
                    total: toMoney(this.calculateTotalCost(history, isDonationOrder), GENERAL.precision) + ' '
                        + this.rebrandingFunc(history.market.base),
                    fee: this.createTicker(toMoney(history.fee, history.market.base.subunit), history, isDonationOrder),
                    pairUrl: this.generatePairUrl(history.market),
                    blocked: history.market.quote.hasOwnProperty('blocked') ? history.market.quote.blocked : false,
                };
            });
        },
        fieldsArray: function() {
            return Object.values(this.fields);
        },
    },
    mounted: function() {
        this.updateTableData();
    },
    methods: {
        updateTableData: function() {
            return new Promise((resolve, reject) => {
                this.$axios.retry.get(this.$routing.generate('executed_user_orders', {
                    page: this.currentPage,
                    donations: this.donations,
                }))
                    .then((res) => {
                        let orders = typeof res.data === 'object' ? Object.values(res.data) : res.data;
                        orders.forEach((order) => {
                            if (0 === order.id) {
                               this.donations++;
                            }
                        });

                        if (this.tableData === null) {
                            this.tableData = orders;
                            this.currentPage++;
                        } else if (orders.length > 0) {
                            this.tableData = this.tableData.concat(orders);
                            this.currentPage++;
                        }

                        resolve(this.tableData);
                    })
                    .catch((err) => {
                        this.sendLogs('error', 'Service unavailable. Can not update trading history', err);
                        reject([]);
                    });
            });
        },
        generatePairUrl: function(market) {
            if (market.quote.hasOwnProperty('exchangeble') && market.quote.exchangeble && market.quote.tradable) {
                return this.$routing.generate('coin', {
                    base: this.rebrandingFunc(market.base.symbol),
                    quote: this.rebrandingFunc(market.quote.symbol),
                    tab: 'trade',
                });
            }

            return this.$routing.generate('token_show', {name: market.quote.name, tab: 'trade'});
        },
        createTicker: function(amount, history, isDonationOrder) {
            if ([webBtcSymbol, webEthSymbol, webUsdcSymbol].includes(history.market.identifier)) {
                return amount + ' ' + (WSAPI.order.type.BUY === history.side
                    ? this.rebrandingFunc(history.market.quote.symbol)
                    : this.rebrandingFunc(history.market.base.symbol));
            }
            return amount + ' '
                + (isDonationOrder ? this.rebrandingFunc(history.market.base.symbol) : MINTME.symbol);
        },
        /**
         * @param {object} history
         * @param {boolean} isDonationOrder
         * @return {string}
         */
        calculateTotalCost: function(history, isDonationOrder) {
            if (
                WSAPI.order.type.BUY === history.side &&
                ![webBtcSymbol, webEthSymbol, webUsdcSymbol].includes(history.market.identifier)
            ) {
                return toMoney(
                    (new Decimal(isDonationOrder ? 1 : history.price).times(history.amount))
                        .add(history.fee)
                        .toString(),
                    history.market.base.subunit
                );
            }
            return toMoney(
                (new Decimal(isDonationOrder ? 1 : history.price).times(history.amount))
                    .toString(),
                history.market.base.subunit
            );
        },
        producePrecision(history) {
            if (history.market.identifier !== webBtcSymbol) {
                return MINTME.subunit;
            }

            return isDonationOrder
                ? history.market.base.subunit
                : (WSAPI.order.type.BUY === history.side ? MINTME.subunit : BTC.subunit);
        },

    },
};
</script>
