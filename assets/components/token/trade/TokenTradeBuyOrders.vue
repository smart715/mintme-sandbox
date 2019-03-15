<template>
    <div class="h-100">
        <div class="card h-100">
            <div class="card-header">
                Buy Orders
                <template v-if="loaded">
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
                </template>
                <template v-else>
                    <div class="p-5 text-center">
                        <font-awesome-icon icon="circle-notch" spin class="loading-spinner" fixed-width />
                    </div>
                </template>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive fix-height" ref="buyorders">
                    <template v-if="loaded">
                    <b-table v-if="hasOrders" ref="table"
                        :items="ordersList"
                        :fields="fields">
                        <template slot="trader" slot-scope="row">
                                <a :href="row.item.trader_url">
                                    <span>{{ row.value }}</span>
                                    <img
                                        src="../../../img/avatar.png"
                                        class="float-right"
                                        alt="avatar">
                                </a>
                                <a @click="removeOrderModal(row.item)"
                                   v-if="row.item.owner">
                                     <font-awesome-icon icon="times" class="text-danger c-pointer" />
                                </a>
                        </template>
                    </b-table>
                    <div v-if="!hasOrders">
                        <p class="text-center p-5">No order was added yet</p>
                    </div>
                    </template>
                    <template v-else>
                        <div class="p-5 text-center">
                            <font-awesome-icon icon="circle-notch" spin class="loading-spinner" fixed-width />
                        </div>
                    </template>
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
import Guide from '../../Guide';
import {toMoney} from '../../../js/utils';

export default {
    name: 'TokenTradeBuyOrders',
    props: {
        ordersList: [Array, Object],
        tokenName: String,
        fields: Object,
    },
    components: {
        Guide,
    },
    computed: {
        total: function() {
            return toMoney(this.ordersList.reduce((sum, order) => parseFloat(order.sum_web) + sum, 0));
        },
        hasOrders: function() {
              return this.ordersList.length > 0;
        },
        loaded: function() {
            return this.ordersList !== null;
        },
        showDownArrow: function() {
            return (this.buyOrders.length > 7);
        },
    },
    methods: {
        scrollDown: function() {
            let parentDiv = this.$refs.buyorders;
            parentDiv.scrollTop = parentDiv.scrollHeight;
        },
    },
    methods: {
        removeOrderModal: function(row) {
            this.$emit('modal', row);
        },
    },
};
</script>
