<template>
    <div class="pb-3">
        <template v-if="loaded">
        <div class="table-responsive">
            <b-table
                v-if="hasHistory"
                :items="history"
                :fields="fields"
                :current-page="currentPage"
                :per-page="perPage">
                 <template slot="side" slot-scope="row">{{ getType(row.value)}}</template>
                 <template slot="timestamp" slot-scope="row">{{ getDate(row.value) }}</template>
            </b-table>
            <div v-if="!hasHistory">
                <h4 class="text-center p-5">No deal was made yet</h4>
            </div>
        </div>
        <div
            class="row justify-content-center"
            v-if="hasHistory">
            <b-pagination
                :total-rows="totalRows"
                :per-page="perPage"
                v-model="currentPage"
                class="my-0" />
        </div>
        </template>
        <template v-else>
            <font-awesome-icon icon="circle-notch" spin class="loading-spinner" fixed-width />
        </template>
    </div>
</template>

<script>
import {Decimal} from 'decimal.js';
import {toMoney} from '../../js/utils';
import {WSAPI} from '../../js/utils/constants';

export default {
    name: 'TradingHistory',
    data() {
        return {
            history: null,
            currentPage: 1,
            perPage: 10,
            fields: {
                timestamp: {label: 'Date', sortable: true},
                side: {label: 'Type', sortable: true},
                market: {
                    label: 'Name',
                    sortable: true,
                    formatter: (market) => market.token.name + '/' + market.currencySymbol,
                },
                amount: {
                    label: 'Amount',
                    sortable: true,
                    formatter: (value) => toMoney(value),
                },
                price: {
                    label: 'Price',
                    sortable: true,
                    formatter: (value) => toMoney(value),
                },
                total: {
                    label: 'Total cost',
                    sortable: true,
                    formatter: (value, key, item) => {
                        let tWF = new Decimal(item.amount).times(item.price);
                        let f = new Decimal(item.fee);
                        return toMoney(tWF.add(f).toString());
                    },
                },
                fee: {
                    label: 'Fee',
                    sortable: true,
                    formatter: (value) => toMoney(value),
                },
            },
        };
    },
    computed: {
        totalRows: function() {
            return this.history.length;
        },
        loaded: function() {
            return this.history !== null;
        },
        hasHistory: function() {
            return (this.totalRows > 0);
        },
    },
    mounted: function() {
        this.$axios.retry.get(this.$routing.generate('executed_user_orders'))
            .then((res) =>
                this.history = typeof res.data === 'object' ? Object.values(res.data) : res.data
            )
            .catch(() => this.$toasted.error('Can not update history now. Try again later.'));
    },
    methods: {
        getDate: function(timestamp) {
           return new Date(timestamp * 1000).toDateString();
        },
        getType: function(type) {
           return (type === WSAPI.order.type.SELL) ? 'Sell' : 'Buy';
        },
    },
};
</script>
