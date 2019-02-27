<template>
    <div class="d-flex">
        <div class="col-12 col-md-6 mt-3">
            <token-trade-buy-orders v-if="ordersLoaded" :buy-orders="buyOrders" :token-name="tokenName"
                                    :user-id="userId"/>
            <template v-else>
                <font-awesome-icon icon="circle-notch" spin class="loading-spinner d-block text-white mx-auto my-3"
                                   size="5x"/>
            </template>
        </div>
        <div class="col-12 col-md-6 mt-3">
            <token-trade-sell-orders v-if="ordersLoaded" :sell-orders="sellOrders" :token-name="tokenName"
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
};
</script>
