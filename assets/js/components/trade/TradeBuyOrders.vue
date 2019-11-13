<template>
    <div class="h-100">
        <div class="card h-100">
            <div class="card-header">
                Buy Orders
                <span class="card-header-icon">
                    Total: {{ total | formatMoney }} {{ tokenName }}
                    <guide>
                        <template slot="header">
                            Buy Orders
                        </template>
                        <template slot="body">
                            List of all active buy orders for {{ tokenName }}.
                        </template>
                    </guide>
                </span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive fixed-head-table">
                    <b-table v-if="hasOrders"
                         ref="table"
                         @row-clicked="orderClicked"
                         :sort-by.sync="sortBy"
                         :sort-desc.sync="sortDesc"
                         :items="tableData"
                         :fields="fields">
                        <template v-slot:cell(trader)="row">
                        <a v-if="!row.item.isAnonymous" :href="row.item.traderUrl">
                                <span v-b-tooltip="{title: row.item.traderFullName, boundary:'viewport'}">
                                    {{ row.value }}
                                </span>
                            <img
                              src="../../../img/avatar.png"
                              class="float-right"
                              alt="avatar">
                        </a>
                        <span v-else>{{ row.value }}</span>

                        <a @click="removeOrderModal(row.item)"
                           v-if="row.item.owner">
                            <font-awesome-icon icon="times" class="text-danger c-pointer ml-2" />
                        </a>
                        </template>
                    </b-table>
                    <div v-if="!hasOrders">
                        <p class="text-center p-5">No order was added yet</p>
                    </div>
                </div>
                <div class="text-center pb-2" v-if="showDownArrow && !loading">
                    <img
                        src="../../../img/down-arrows.png"
                        class="icon-arrows-down c-pointer"
                        alt="arrow down"
                        @click="scrollDown">
                </div>
                <div v-if="loading" class="p-1 text-center">
                        <font-awesome-icon icon="circle-notch" spin class="loading-spinner" fixed-width />
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import Guide from '../Guide';
import {toMoney} from '../../utils';
import Decimal from 'decimal.js';
import {LazyScrollTableMixin, MoneyFilterMixin, OrderClickedMixin} from '../../mixins';

export default {
    name: 'TradeBuyOrders',
    mixins: [LazyScrollTableMixin, MoneyFilterMixin, OrderClickedMixin],
    props: {
        ordersList: [Array],
        tokenName: String,
        fields: Array,
        sortBy: String,
        sortDesc: Boolean,
        basePrecision: Number,
        loggedIn: Boolean,
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
                new Decimal(order.sum).add(sum), 0), this.basePrecision
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
