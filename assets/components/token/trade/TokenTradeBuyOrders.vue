<template>
    <div>
        <div class="card">
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
                    <font-awesome-icon icon="circle-notch" spin class="loading-spinner" fixed-width />
                </template>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive fix-height">
                    <template v-if="loaded">
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
                    <div v-if="!hasOrders">
                        <p class="text-center p-5">No order was added yet</p>
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
import Guide from '../../Guide';
import {toMoney} from '../../../js/utils';
import Decimal from 'decimal.js';

export default {
    name: 'TokenTradeBuyOrders',
    props: {
        buyOrders: [Array, Object],
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
            return toMoney(this.ordersList.reduce((sum, order) => parseFloat(order.sum_web) + sum, 0));
        },
        ordersList: function() {
            return this.buyOrders.map((order) => {
                return {
                    price: toMoney(order.price),
                    amount: toMoney(order.amount),
                    sum_web: toMoney(new Decimal(order.price).mul(order.amount).toString()),
                    trader: order.maker.profile.firstName + ' ' + order.maker.profile.lastName,
                };
            });
        },
        hasOrders: function() {
              return this.buyOrders.length > 0;
        },
        loaded: function() {
            return this.buyOrders !== null;
        },
    },
};
</script>
