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
                    <div class="table-responsive fixed-head-table mb-0">
                        <b-table v-if="hasOrders"
                            ref="table"
                            @row-clicked="orderClicked"
                            :items="tableData"
                            :fields="fields"
                            :tbody-tr-class="rowClass"
                            :tbody-class="'table-orders'"
                        >
                            <template v-slot:cell(trader)="row">
                                <div class="d-flex flex-row flex-nowrap justify-content-between w-100">
                                    <div class="col-11 pl-0 ml-0">
                                    <a
                                        :href="row.item.traderUrl"
                                        class="d-flex flex-row flex-nowrap justify-content-between w-100 text-white"
                                        v-b-tooltip="popoverConfig"
                                        v-on:mouseover="mouseoverHandler(fullOrdersList, basePrecision, row.item.price)"
                                    >
                                        <img
                                            :src="row.item.traderAvatar"
                                            class="rounded-circle d-block flex-grow-0 pointer-events-none pull-right mr-1"
                                            alt="avatar">
                                        <span class="d-inline-block truncate-name flex-grow-1 pointer-events-none">
                                            {{ row.value }}
                                        </span>
                                    </a>
                                    </div>
                                    <div class="col-1 pull-right pl-0 ml-0">
                                        <a v-if="row.item.owner" class="d-inline-block flex-grow-0" @click="removeOrderModal(row.item)">
                                            <font-awesome-icon icon="times" class="text-danger c-pointer" />
                                        </a>
                                    </div>
                                </div>
                            </template>
                        </b-table>
                        <div v-else>
                            <p class="text-center p-5">No order was added yet</p>
                        </div>
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
    OrderHighlights,
} from '../../mixins/';

export default {
    name: 'TradeBuyOrders',
    mixins: [
        LazyScrollTableMixin,
        MoneyFilterMixin,
        OrderClickedMixin,
        RebrandingFilterMixin,
        TraderHoveredMixin,
        OrderHighlights,
    ],
    props: {
        fullOrdersList: [Array],
        ordersList: [Array],
        tokenName: String,
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
        rowClass: function(item, type) {
            return 'row' === type && item.highlightClass
                ? item.highlightClass
                : '';
        },
    },
    watch: {
        ordersList: function(newOrders) {
            let delayOrdersUpdating = this.ordersUpdated
                ? this.handleOrderHighlights(this.tableData, newOrders)
                : false;

            if (delayOrdersUpdating) {
                setTimeout(()=> this.tableData = newOrders, 1000);
            } else {
                this.tableData = newOrders;
            }

            setTimeout(()=> this.tableData.forEach((order) => order.highlightClass = ''), 1000);
        },
    },
};
</script>
