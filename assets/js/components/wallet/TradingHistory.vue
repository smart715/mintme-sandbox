<template>
    <div class="px-0 pt-2">
        <template v-if="loaded">
            <div class="table-responsive table-restricted" ref="table" v-if="hasHistory">
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
                            <a :href="row.item.pairUrl" class="text-white">
                                {{ row.value.truncate }}
                            </a>
                        </div>
                        <div v-else>
                            <a :href="row.item.pairUrl" class="text-white">
                                {{ row.value.full }}
                            </a>
                        </div>
                    </template>
                </b-table>
            </div>
            <div v-if="loading" class="p-1 text-center">
                <font-awesome-icon icon="circle-notch" spin class="loading-spinner" fixed-width />
            </div>
            <div v-else-if="!hasHistory">
                <p class="text-center p-5">No deal was made yet</p>
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
import moment from 'moment';
import {Decimal} from 'decimal.js';
import {toMoney, formatMoney} from '../../utils';
import {GENERAL, WSAPI} from '../../utils/constants';
import {
    FiltersMixin,
    LazyScrollTableMixin,
    RebrandingFilterMixin,
    NotificationMixin,
    LoggerMixin,
    PairNameMixin,
} from '../../mixins/';

export default {
    name: 'TradingHistory',
    mixins: [
        FiltersMixin,
        LazyScrollTableMixin,
        RebrandingFilterMixin,
        NotificationMixin,
        LoggerMixin,
        PairNameMixin,
    ],
    data() {
        return {
            tableData: null,
            currentPage: 1,
            fields: {
                date: {
                    key: 'date',
                    label: 'Date',
                    sortable: true,
                    type: 'date',
                },
                side: {
                    key: 'side',
                    label: 'Type',
                    sortable: true,
                    type: 'string',
                },
                name: {
                    key: 'name',
                    label: 'Name',
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
                    label: 'Amount',
                    sortable: true,
                    formatter: formatMoney,
                    type: 'numeric',
                },
                price: {
                    key: 'price',
                    label: 'Price',
                    sortable: true,
                    formatter: formatMoney,
                    type: 'numeric',
                },
                total: {
                    key: 'total',
                    label: 'Total cost',
                    sortable: true,
                    formatter: formatMoney,
                    type: 'numeric',
                },
                fee: {
                    key: 'fee',
                    label: 'Fee',
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
                return {
                    date: moment.unix(history.timestamp).format(GENERAL.dateFormat),
                    side: history.side === WSAPI.order.type.SELL ? 'Sell' : 'Buy',
                    name: this.pairNameFunc(
                        this.rebrandingFunc(history.market.base),
                        this.rebrandingFunc(history.market.quote)
                    ),
                    amount: toMoney(history.amount, history.market.base.subunit),
                    price: toMoney(history.price, history.market.base.subunit),
                    total: toMoney((new Decimal(history.price).times(history.amount)).add(new Decimal(history.fee)).toString(), history.market.base.subunit),
                    fee: toMoney(history.fee, history.market.base.subunit),
                    pairUrl: this.generatePairUrl(history.market),
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
                this.$axios.retry.get(this.$routing.generate('executed_user_orders', {page: this.currentPage}))
                    .then((res) => {
                        res.data = typeof res.data === 'object' ? Object.values(res.data) : res.data;
                        if (this.tableData === null) {
                            this.tableData = res.data;
                            this.currentPage++;
                        } else if (res.data.length > 0) {
                            this.tableData = this.tableData.concat(res.data);
                            this.currentPage++;
                        }

                        resolve(this.tableData);
                    })
                    .catch((err) => {
                        this.notifyError('Can not update trading history. Try again later.');
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
    },
};
</script>
