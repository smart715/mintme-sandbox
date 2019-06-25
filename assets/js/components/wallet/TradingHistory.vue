<template>
    <div class="px-0 pt-2">
        <template v-if="loaded">
            <div class="table-responsive table-restricted" ref="table">
                <b-table
                    v-if="hasHistory"
                    :items="tableData"
                    :fields="fields"
                    :sort-compare="sortCompare">
                    <template slot="market" slot-scope="row">
                        <div v-b-tooltip="{title: row.value.full, boundary: 'viewport'}">{{ row.value.truncate }}</div>
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
                    formatter: (market) => {
                        let name = market.base.symbol + '/' + market.quote.symbol;
                        return {
                            full: name,
                            truncate: this.truncateFunc(name, 15),
                        };
                    },
                },
                amount: {
                    label: 'Amount',
                    sortable: true,
                    formatter: (value, key, item) => formatMoney(toMoney(value, item.market.quote.subunit)),
                },
                price: {
                    label: 'Price',
                    sortable: true,
                    formatter: (value, key, item) => formatMoney(toMoney(value, item.market.base.subunit)),
                },
                total: {
                    label: 'Total cost',
                    sortable: true,
                    formatter: (value, key, item) => {
                        let tWF = new Decimal(item.amount).times(item.price);
                        let f = new Decimal(item.fee);
                        return formatMoney(toMoney(tWF.add(f).toString(), item.market.base.subunit));
                    },
                },
                fee: {
                    label: 'Fee',
                    sortable: true,
                    formatter: (value, key, item) => formatMoney(toMoney(value, item.market.base.subunit)),
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
        sortCompare: function(a, b, key) {
            return a[key].name.localeCompare(b[key].name, undefined, {
                numeric: true
            });
        },
        getDate: function(timestamp) {
           return moment.unix(timestamp).format(GENERAL.dateFormat);
        },
        getType: function(type) {
           return (type === WSAPI.order.type.SELL) ? 'Sell' : 'Buy';
        },
    },
};
</script>
