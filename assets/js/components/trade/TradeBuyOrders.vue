<template>
    <div class="h-100">
        <div class="card h-100">
            <div class="card-header">
                Buy Orders
                <span class="card-header-icon">
                    Total: {{ total }} WEB
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
                <div class="table-responsive fixed-head-table" ref="ordersList">
                    <b-table v-if="hasOrders" ref="table"
                             :sort-by.sync="sortBy"
                             :sort-desc.sync="sortDesc"
                             :items="ordersList"
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

export default {
    name: 'TradeBuyOrders',
    props: {
        ordersList: [Array, Object],
        tokenName: String,
        fields: Object,
        sortBy: String,
        sortDesc: Boolean,
        precision: Number,
    },
    components: {
        Guide,
    },
    computed: {
        total: function() {
            return toMoney(this.ordersList.reduce((sum, order) =>
                new Decimal(order.sumWeb).add(sum), 0), this.precision
            );
        },
        hasOrders: function() {
              return this.ordersList.length > 0;
        },
        showDownArrow: function() {
            return (this.ordersList.length > 7);
        },
    },
    methods: {
        scrollDown: function() {
            let parentDiv = this.$refs.table.$el.tBodies[0];
            parentDiv.scrollTop = parentDiv.scrollHeight;
        },
        removeOrderModal: function(row) {
            this.$emit('modal', row);
        },
    },
};
</script>
