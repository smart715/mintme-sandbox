<template>
    <div class="d-flex">
        <div class="col-12 col-md-6 mt-3">
            <token-trade-buy-orders
                    v-if="ordersLoaded"
                    :buy-orders="buyOrders"
                    :filtered="filteredBuyOrders"
                    :token-name="tokenName"
                    :user-id="userId"/>
            <template v-else>
                <font-awesome-icon icon="circle-notch" spin class="loading-spinner d-block text-white mx-auto my-3"
                                   size="5x"/>
            </template>
        </div>
        <div class="col-12 col-md-6 mt-3">
            <token-trade-sell-orders
                    v-if="ordersLoaded"
                    :sell-orders="sellOrders"
                    :filtered="filteredSellOrders"
                    :token-name="tokenName"
                    :user-id="userId"/>
            <template v-else>
                <font-awesome-icon icon="circle-notch" spin class="loading-spinner d-block text-white mx-auto my-3"
                                   size="5x"/>
            </template>
        </div>
    </div>
</template>

<script>
import TokenTradeBuyOrders from './TokenTradeBuyOrders';
import TokenTradeSellOrders from './TokenTradeSellOrders';
import Decimal from 'decimal.js';

export default {
    name: 'TokenTradeOrders',
    components: {
        TokenTradeBuyOrders,
        TokenTradeSellOrders,
    },
    props: {
        ordersLoaded: Boolean,
        buyOrders: [Array, Object],
        sellOrders: [Array, Object],
        tokenName: String,
        userId: Number,
    },
    computed: {
        filteredBuyOrders: function() {
            return this.groupByPrice(this.buyOrders);
        },
        filteredSellOrders: function() {
            return this.groupByPrice(this.sellOrders);
        }
    },
    methods: {
        groupByPrice: function(orders) {
            let filtered = [];
            let grouped = {};
            this.clone(orders).forEach( (item) => {
                if (grouped[item.price] === undefined) {
                    grouped[item.price] = [];
                }
                grouped[item.price].push(item);
            });
            for (let orders in grouped) {
                if (grouped.hasOwnProperty(orders)) {
                    let sum = grouped[orders].reduce((sum, order)=> parseFloat(order.amount) + sum, 0);
                    grouped[orders].sort((first, second) => first.maker.id - second.maker.id);
                    grouped[orders].forEach((order, i, arr) => {
                        if (arr[i-1] !== undefined && arr[i-1].maker.id === order.maker.id) {
                            order.amount = new Decimal(order.amount).add(arr[i-1].amount);
                        }
                    });
                    grouped[orders].sort((first, second) => parseFloat(second.amount) - parseFloat(first.amount));
                    grouped[orders][0].amount = sum;
                    filtered.push(grouped[orders][0]);
                }
            }
            return filtered;
        },
        clone: function(orders) {
            return JSON.parse(JSON.stringify(orders));
        },
    },
};
</script>
