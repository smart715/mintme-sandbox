<template>
    <div class="pb-3">
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
    </div>
</template>

<script>
import {Decimal} from 'decimal.js';
export default {
    name: 'TradingHistory',
    props: {
        executedHistory: {type: Array, required: true},
    },
    data() {
        return {
            currentPage: 1,
            perPage: 10,
            fields: {
                timestamp: {
                    label: 'Date',
                    sortable: true,
                },
                side: {
                    label: 'Type',
                    sortable: true,
                },
                market: {
                    label: 'Name',
                    sortable: true,
                },
                amount: {
                    label: 'Amount',
                    sortable: true,
                },
                price: {
                    label: 'Price',
                    sortable: true,
                },
                total: {
                    label: 'Total cost',
                    sortable: true,
                    formatter: (value, key, item) => {
                        let a = new Decimal(item.amount);
                        let p = new Decimal(item.price);
                        let tWF = a.times(p);
                        let f = new Decimal(item.fee);
                        return tWF.plus(f).toDP(12);
                    },
                },
                fee: {
                    label: 'Fee',
                    sortable: true,
                },
            },
        };
    },
    computed: {
        history: function() {
            return this.executedHistory;
        },
        totalRows: function() {
            return this.history.length;
        },
        hasHistory: function() {
            return (this.totalRows > 0);
        },
    },
    methods: {
        getDate: function(timestamp) {
           let d = new Date(timestamp * 1000);
           return d.toLocaleFormat('d-m-Y');
        },
        getType: function(type) {
           return (type === 1) ? 'Sell' : 'Buy';
        },
    },
};
</script>
