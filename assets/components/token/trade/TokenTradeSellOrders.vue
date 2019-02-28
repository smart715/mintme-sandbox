<template>
    <div>
        <div class="card">
            <div class="card-header">
                Sell Orders
                <template v-if="loaded">
                <span class="card-header-icon">
                    Total: {{ total }} {{ tokenName }}
                    <guide>
                        <template slot="header">
                            Sell Orders
                        </template>
                        <template slot="body">
                            List of all active sell orders for {{ tokenName }}.
                        </template>
                    </guide>
                </span>
                </template>
                <template v-else>
                    <font-awesome-icon icon="circle-notch" spin class="loading-spinner" fixed-width />
                </template>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive fix-height">
                    <template v-if="loaded">
                    <b-table v-if="hasOrders" ref="table"
                        :items="filtered"
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
                               v-if="row.item.trader_id">
                                <font-awesome-icon icon="times" class="text-danger c-pointer" />
                            </a>
                        </template>
                    </b-table>
                    <div v-if="!hasOrders">
                        <h4 class="text-center p-5">No order was added yet</h4>
                    </div>
                    </template>
                    <template v-else>
                        <font-awesome-icon icon="circle-notch" spin class="loading-spinner" fixed-width />
                    </template>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import ConfirmModal from '../../modal/ConfirmModal';
import Guide from '../../Guide';
import {toMoney} from '../../../js/utils';
import Decimal from 'decimal.js';

export default {
    name: 'TokenTradeSellOrders',
    props: {
        sellOrders: [Array, Object],
        filtered: [Array, Object],
        tokenName: String,
        userId: Number,
    },
    components: {
        Guide,
    },
    data() {
        return {
            currentRow: {},
            orders: [],
            fields: {
                price: {
                    label: 'Price',
                },
                amount: {
                    label: 'Amount',
                },
                sum_web: {
                    label: 'Sum WEB',
                },
                trader: {
                    label: 'Trader',
                },
            },
        };
    },
    computed: {
        total: function() {
            return toMoney(this.filtered.reduce((sum, order) => parseFloat(order.amount) + sum, 0));
        },
        hasOrders: function() {
            return this.sellOrders.length > 0;
        },
        loaded: function() {
            return this.sellOrders !== null;
        },
    },
    methods: {
        removeOrderModal: function(row){
            this.$emit('modal', {
                row: row,
                orders: this.sellOrders,
            });
        },
    },
};
</script>

