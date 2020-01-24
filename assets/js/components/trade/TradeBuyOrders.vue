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
                <div class="table-responsive fixed-head-table">
                    <b-table v-if="hasOrders"
                        ref="table"
                        @row-clicked="orderClicked"
                        :sort-by.sync="sortBy"
                        :sort-desc.sync="sortDesc"
                        :items="tableData"
                        :fields="fields"
                    >
                        <template v-slot:cell(trader)="row">
                            <div class="d-flex flex-row flex-nowrap justify-content-between w-100">
                                <span v-if="row.item.isAnonymous" class="d-inline-block truncate-name flex-grow-1">
                                    {{ row.value }}
                                </span>
                                <a
                                    v-else
                                    :href="row.item.traderUrl"
                                    class="d-flex flex-row flex-nowrap justify-content-between w-100 buy-orders-tooltip"
                                    v-b-tooltip="popoverConfig"
                                    v-on:mouseover="mouseover"
                                    :id="'side_' + row.item.side + '_order_' + row.item.orderId"
                                    :data-order-id="row.item.orderId"
                                    :data-owner-id="row.item.ownerId"
                                    :data-side="row.item.side"
                                    :data-price="row.item.price"
                                >
                                    <span class="d-inline-block truncate-name flex-grow-1">
                                        {{ row.value }}
                                    </span>
                                    <img
                                        src="../../../img/avatar.png"
                                        class="d-block flex-grow-0"
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
            </div>
        </div>
        <div id="buy-traiders-tooltip-container"></div>
    </div>
</template>

<script>
import Guide from '../Guide';
import {toMoney} from '../../utils';
import Decimal from 'decimal.js';
import {LazyScrollTableMixin, MoneyFilterMixin, OrderClickedMixin, RebrandingFilterMixin} from '../../mixins/';

export default {
    name: 'TradeBuyOrders',
    mixins: [LazyScrollTableMixin, MoneyFilterMixin, OrderClickedMixin, RebrandingFilterMixin],
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
            tooltip: 'Loading...',
        };
    },
    components: {
        Guide,
    },
    mounted: function() {
        this.startScrollListeningOnce(this.ordersList);

        this.$root.$on('bv::tooltip::hidden', () => {
            this.tooltip = 'Loading...';
        });
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
        popoverConfig() {
            return {
                title: this.tooltipContent,
                html: true,
                boundary:'viewport',
                show: 300,
                hide: 100,
                id: 'buy-traiders-tooltip-container'
            };
        },
    },
    methods: {
        mouseover: function(event) {
            let target = event.target;

            if ('a' !== target.tagName.toLowerCase()) {
                target = target.parentElement;
            }

            let orderId = target.getAttribute('data-order-id'),
                ownerId = target.getAttribute('data-owner-id'),
                side = target.getAttribute('data-side'),
                price = target.getAttribute('data-price');

            let data = {
                orderId,
                ownerId,
                side,
                price
            };

            this.orderTraderHovered(data);
        },
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
