<template>
    <div class="h-100">
        <div class="card h-100">
            <div class="card-header">
                Buy Orders
                <span class="card-header-icon">
                    Total: {{ total | formatMoney }} {{ tokenName|rebranding }}
                    <guide>
                        <template slot="header">
                            Buy Orders
                        </template>
                        <template slot="body">
                            List of all active buy orders for {{ tokenName|rebranding }}.
                        </template>
                    </guide>
                </span>
            </div>
            <div class="card-body p-0">
                <template v-if="ordersLoaded">
                    <div class="table-responsive fixed-head-table">
                        <b-table v-if="hasOrders"
                            ref="table"
                            @row-clicked="orderClicked"
                            :items="tableData"
                            :fields="fields"
                        >
                            <template v-slot:cell(trader)="row">
                                <div class="d-flex flex-row flex-nowrap justify-content-between w-100">
                                    <span
                                        v-if="row.item.isAnonymous"
                                        class="d-inline-block truncate-name flex-grow-1 c-pointer"
                                        v-b-tooltip="popoverConfig"
                                        v-on:mouseover="mouseoverHandler(fullOrdersList, basePrecision, row.item.price, row.item.side)"
                                    >
                                        {{ row.value }}
                                    </span>
                                    <a
                                        v-else
                                        :href="row.item.traderUrl"
                                        class="d-flex flex-row flex-nowrap justify-content-between w-100"
                                        v-b-tooltip="popoverConfig"
                                        v-on:mouseover="mouseoverHandler(fullOrdersList, basePrecision, row.item.price, row.item.side)"
                                    >
                                        <span class="d-inline-block truncate-name flex-grow-1 pointer-events-none">
                                            {{ row.value }}
                                        </span>
                                        <img
                                            src="../../../img/avatar.png"
                                            class="d-block flex-grow-0 pointer-events-none"
                                            alt="avatar">
                                    </a>
                                    <a v-if="row.item.owner" class="d-inline-block flex-grow-0" @click="removeOrderModal(row.item)">
                                        <font-awesome-icon icon="times" class="text-danger c-pointer ml-2" />
                                    </a>
                                </div>
                            </template>
                        </b-table>
                        <div v-else>
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
import Guide from '../Guide';
import {toMoney} from '../../utils';
import Decimal from 'decimal.js';
import {
    LazyScrollTableMixin,
    MoneyFilterMixin,
    OrderClickedMixin,
    RebrandingFilterMixin,
    TraderHoveredMixin,
} from '../../mixins/';

export default {
    name: 'TradeBuyOrders',
    mixins: [
        LazyScrollTableMixin,
        MoneyFilterMixin,
        OrderClickedMixin,
        RebrandingFilterMixin,
        TraderHoveredMixin,
    ],
    props: {
        fullOrdersList: [Array],
        ordersList: [Array],
        tokenName: String,
        fields: Array,
        basePrecision: Number,
        loggedIn: Boolean,
        ordersLoaded: Boolean,
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
