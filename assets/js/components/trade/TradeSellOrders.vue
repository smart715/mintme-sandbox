<template>
    <div class="h-100">
        <div class="card h-100">
            <div class="card-header">
                Sell Orders
                <span class="card-header-icon">
                    Total: {{ total | formatMoney }}
                    <span v-if="shouldTruncate" v-b-tooltip="{title: rebrandingFunc(market.quote), boundary:'viewport'}">
                        {{ market.quote | rebranding | truncate(17) }}
                    </span>
                    <span v-else>
                        {{ market.quote | rebranding }}
                    </span>
                    <guide>
                        <template slot="header">
                            Sell Orders
                        </template>
                        <template slot="body">
                            List of all active sell orders for {{ market.quote |rebranding }}.
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
                            :tbody-tr-class="rowClass"
                        >
                            <template v-slot:cell(trader)="row">
                                <div class="d-flex flex-row flex-nowrap justify-content-between w-100">
                                    <span
                                        v-if="row.item.isAnonymous"
                                        class="d-inline-block truncate-name flex-grow-1 c-pointer"
                                        v-b-tooltip="popoverConfig"
                                        v-on:mouseover="mouseoverHandler(fullOrdersList, basePrecision, row.item.price)"
                                    >
                                        {{ row.value }}
                                    </span>
                                    <a
                                        v-else
                                        :href="row.item.traderUrl"
                                        class="d-flex flex-row flex-nowrap justify-content-between w-100"
                                        v-b-tooltip="popoverConfig"
                                        v-on:mouseover="mouseoverHandler(fullOrdersList, basePrecision, row.item.price)"
                                    >
                                        <span class="d-inline-block truncate-name flex-grow-1 pointer-events-none">
                                            {{ row.value }}
                                        </span>
                                        <img
                                            src="../../../img/avatar.png"
                                            class="d-block flex-grow-0 pointer-events-none"
                                            alt="avatar">
                                    </a>
                                    <a
                                        v-if="row.item.owner"
                                        class="d-inline-block flex-grow-0"
                                        @click="removeOrderModal(row.item)"
                                    >
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
    FiltersMixin,
    MoneyFilterMixin,
    OrderClickedMixin,
    RebrandingFilterMixin,
    TraderHoveredMixin,
} from '../../mixins/';

export default {
    name: 'TradeSellOrders',
    mixins: [
        FiltersMixin,
        LazyScrollTableMixin,
        MoneyFilterMixin,
        OrderClickedMixin,
        RebrandingFilterMixin,
        TraderHoveredMixin,
    ],
    props: {
        fullOrdersList: [Array],
        ordersList: [Array],
        market: Object,
        fields: Array,
        basePrecision: Number,
        loggedIn: Boolean,
        ordersLoaded: Boolean,
        ordersUpdated: {
            type: Boolean,
            default: false,
        },
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
        shouldTruncate: function() {
            return this.market.quote.symbol.length > 17;
        },
        total: function() {
            return toMoney(this.tableData.reduce((sum, order) =>
                new Decimal(order.amount).add(sum), 0), this.quotePrecision
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
        rowClass: function(item, type) {
            return 'row' === type && item.highlightClass
                ? item.highlightClass
                : '';
        },
    },
    watch: {
        ordersList: function(newOrders) {
            let orders = this.tableData;

            if (this.ordersUpdated && orders.length < newOrders.length) {
                let newOrder = newOrders.filter((order) => {
                    return !orders.some((order2) => order.price === order2.price);
                });

                if (newOrder.length) {
                    newOrder[0].highlightClass = 'success-highlight';
                }
            }

            if (this.ordersUpdated && orders.length === newOrders.length) {
                let newOrder = newOrders.filter((order) => {
                    return !orders.some((order2) => order.price === order2.price && order.amount === order2.amount);
                });

                if (newOrder.length) {
                    newOrder = newOrder[0];
                    let oldOrder = orders.find((order) => order.id === newOrder.id);
                    let newAmount = new Decimal(newOrder.amount);

                    newAmount.greaterThanOrEqualTo(oldOrder.amount)
                        ? newOrder.highlightClass = 'success-highlight'
                        : newOrder.highlightClass = 'error-highlight';
                }
            }

            if (this.tableData.length > 0 && this.tableData.length > newOrders.length) {
                let removedOrder = this.tableData.filter((order) => {
                    return !newOrders.some((order2) => order.price === order2.price);
                });

                if (removedOrder.length) {
                    removedOrder[0].highlightClass = 'error-highlight';
                    setTimeout(()=> this.tableData = newOrders, 1000);
                }
            } else {
                this.tableData = newOrders;
                setTimeout(()=> this.tableData.forEach((order) => order.highlightClass = ''), 1000);
            }
        },
    },
};
</script>

