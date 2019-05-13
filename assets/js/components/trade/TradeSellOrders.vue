<template>
    <div class="h-100">
        <div class="card h-100">
            <div class="card-header">
                Sell Orders
                <span class="card-header-icon">
                    Total: {{ total | formatMoney }} <span v-b-tooltip:title="tokenName">{{ tokenName | truncate(7) }}</span>
                    <guide>
                        <template slot="header">
                            Sell Orders
                        </template>
                        <template slot="body">
                            List of all active sell orders for {{ tokenName }}.
                        </template>
                    </guide>
                </span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive fixed-head-table">
                    <b-table v-if="hasOrders" ref="table"
                         :sort-by.sync="sortBy"
                         :sort-desc.sync="sortDesc"
                         :items="tableData"
                         :fields="fields">
                        <template slot="trader" slot-scope="row">
                        <a :href="row.item.traderUrl">
                            <span v-b-tooltip="{title: row.item.traderFullName, boundary:'viewport'}">
                                {{ row.value }}
                            </span>
                            <img
                                src="../../../img/avatar.png"
                                class="float-right"
                                alt="avatar">
                        </a>
                        <a @click="removeOrderModal(row.item)"
                           v-if="row.item.owner">
                            <font-awesome-icon icon="times" class="text-danger c-pointer ml-2" />
                        </a>
                        </template>
                    </b-table>
                    <div v-if="!hasOrders">
                        <p class="text-center p-5">No order was added yet</p>
                    </div>
                    <div v-if="loading" class="p-1 text-center">
                        <font-awesome-icon icon="circle-notch" spin class="loading-spinner" fixed-width />
                    </div>
                </div>
                <div class="text-center pb-2" v-if="showDownArrow">
                    <img
                        src="../../../img/down-arrows.png"
                        class="icon-arrows-down c-pointer"
                        alt="arrow down"
                        @click="scrollDown">
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import Guide from '../Guide';
import {toMoney} from '../../utils';
import Decimal from 'decimal.js';
import {LazyScrollTableMixin, FiltersMixin, MoneyFilterMixin} from '../../mixins';

export default {
    name: 'TradeSellOrders',
    mixins: [FiltersMixin, LazyScrollTableMixin, MoneyFilterMixin],
    props: {
        ordersList: [Array],
        tokenName: String,
        fields: Object,
        sortBy: String,
        sortDesc: Boolean,
        precision: Number,
    },
    data() {
        return {
            tableData: this.ordersList,
        };
    },
    components: {
        Guide,
    },
    mounted: function() {
        this.startScrollListeningOnce(this.ordersList);
    },
    computed: {
        total: function() {
            return toMoney(this.tableData.reduce((sum, order) =>
                new Decimal(order.amount).add(sum), 0), this.precision
            );
        },
        hasOrders: function() {
            return this.tableData.length > 0;
        },
    },
    methods: {
        removeOrderModal: function(row) {
            this.$emit('modal', row);
        },
        updateTableData: function(attach = false) {
            return new Promise((resolve) => {
                this.$emit('update-data', {attach, resolve});
            });
        },
    },
    watch: {
        ordersList: function(val) {
            this.tableData = val;
        },
    },
};
</script>

