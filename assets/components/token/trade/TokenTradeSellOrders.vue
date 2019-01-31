<template>
    <div :class="containerClass">
        <div class="card">
            <div class="card-header">
                Sell Orders
                <font-awesome-icon
                        icon="circle-notch"
                        spin class="loading-spinner"
                        fixed-width
                        v-if="showLoadingIcon"
                />
                <span v-else class="card-header-icon">
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
            </div>
            <div class="card-body p-0">
                <div class="table-responsive fix-height">
                    <b-table v-if="hasOrders" ref="table"
                        :items="ordersList"
                        :fields="fields">
                        <template slot="trader" slot-scope="row">
                           {{ row.value }}
                           <img
                               src="../../../img/avatar.png"
                               class="float-right"
                               alt="avatar">
                        </template>
                    </b-table>
                    <div v-if="!hasOrders && !showLoadingIcon">
                        <h4 class="text-center p-5">No order was added yet</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import Guide from '../../Guide';
import {toMoney} from '../../../js/utils';
import Decimal from 'decimal.js';

export default {
    name: 'TokenTradeSellOrders',
    props: {
        containerClass: String,
        sellOrders: [Array, Boolean],
        tokenName: String,
    },
    components: {
        Guide,
    },
    data() {
        return {
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
            return toMoney(this.ordersList.reduce((sum, order) => parseFloat(order.amount) + sum, 0));
        },
        ordersList: function() {
            return this.sellOrders != false ? this.sellOrders.map((order) => {
                return {
                    price: toMoney(order.price),
                    amount: toMoney(order.amount),
                    sum_web: toMoney(new Decimal(order.price).mul(order.amount).toString()),
                    trader: order.maker.profile.firstName + ' ' + order.maker.profile.lastName,
                };
            }) : [];
        },
        hasOrders: function() {
            return this.sellOrders.length > 0;
        },
        showLoadingIcon: function() {
            return (this.sellOrders === false);
        },
    },
};
</script>

