<template>
    <div class="px-0 pt-2">
        <template v-if="loaded">
            <div class="table-responsive table-restricted" ref="table">
                <b-table
                    v-if="hasHistory"
                    :items="getHistory"
                    :fields="fields">
                    <template slot="market" slot-scope="row">
                        <div v-b-tooltip="{title: row.value.full, boundary: 'viewport'}">
                            <a :href="generatePairUrl(row.item.market)" class="text-white">{{ row.value.truncate }}</a>
                        </div>
                    </template>
                     <template slot="side" slot-scope="row">{{ getType(row.value)}}</template>
                     <template slot="timestamp" slot-scope="row">{{ getDate(row.value) }}</template>
                </b-table>
                <div v-if="!hasHistory">
                    <p class="text-center p-5">No deal was made yet</p>
                </div>
            </div>
            <div v-if="loading" class="p-1 text-center">
                <font-awesome-icon icon="circle-notch" spin class="loading-spinner" fixed-width />
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
import {FiltersMixin, LazyScrollTableMixin} from '../../mixins';

export default {
    name: 'TradingHistory',
    mixins: [FiltersMixin, LazyScrollTableMixin],
    data() {
        return {
            tableData: null,
            currentPage: 1,
            fields: {
                timestamp: {label: 'Date', sortable: true},
                side: {label: 'Type', sortable: true},
                market: {
                    label: 'Name',
                    sortable: true,
                    formatter: (name) => {
                        return {
                            full: name,
                            truncate: this.truncateFunc(name, 7),
                        };
                    },
                },
                amount: {
                    label: 'Amount',
                    sortable: true,
                    formatter: formatMoney,
                },
                price: {
                    label: 'Price',
                    sortable: true,
                    formatter: formatMoney,
                },
                total: {
                    label: 'Total cost',
                    sortable: true,
                    formatter: formatMoney,
                },
                fee: {
                    label: 'Fee',
                    sortable: true,
                    formatter: formatMoney,
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
        getHistory: function() {
            return this.tableData.map((history) => {
                return {
                    date: moment.unix(history.timestamp).format(GENERAL.dateFormat),
                    side: history.side,
                    market: history.market.base.symbol + '/' + history.market.quote.symbol,
                    amount: toMoney(history.amount, history.market.base.subunit),
                    price: toMoney(history.price, history.market.base.subunit),
                    total: toMoney((new Decimal(history.price).times(history.amount)).add(new Decimal(history.fee)).toString(), history.market.base.subunit),
                    fee: toMoney(history.fee, history.market.base.subunit),
                };
            });
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
                    .catch(() => {
                        this.$toasted.error('Can not update trading history. Try again later.');
                        reject([]);
                    });
            });
        },
        getDate: function(timestamp) {
           return moment.unix(timestamp).format(GENERAL.dateFormat);
        },
        getType: function(type) {
           return (type === WSAPI.order.type.SELL) ? 'Sell' : 'Buy';
        },
        generatePairUrl: function(market) {
            if (market.quote.hasOwnProperty('exchangeble') && market.quote.exchangeble && market.quote.tradable) {
                return this.$routing.generate('coin', {base: market.base.symbol, quote: market.quote.symbol});
            }

            return this.$routing.generate('token_show', {name: market.quote.name});
        },
    },
};
</script>
