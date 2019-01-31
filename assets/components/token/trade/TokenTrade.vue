<template>
    <div class="row">
        <token-trade-buy-order
            container-class="buy-order col-12 col-md-6 col-lg-4"
            :websocket-url="websocketUrl"
            :hash="hash"
            :currency="currency"
            :login-url="loginUrl"
            :signup-url="signupUrl"
            :logged-in="loggedIn"
            :market-name="marketName"
            :buy="buy"
            :token-name="tokenName"
            :place-order-url="placeOrderUrl"
            :balance="webBalance"
        />
        <token-trade-sell-order
            container-class="sell-order mt-3 mt-md-0 col-12 col-md-6 col-lg-4"
            :websocket-url="websocketUrl"
            :hash="hash"
            :currency="currency"
            :login-url="loginUrl"
            :signup-url="signupUrl"
            :logged-in="loggedIn"
            :market-name="marketName"
            :sell="sell"
            :token-name="tokenName"
            :place-order-url="placeOrderUrl"
            :balance="tokenBalance"
            :token-hidden-name="tokenHiddenName"
        />
        <token-trade-chart
            container-class="chart mt-3 mt-lg-0 col-12 col-lg-4"
            :websocket-url="websocketUrl"
            :currency="currency"
            :market-name="marketName"
        />
        <token-trade-buy-orders
            container-class="col-12 col-md-6 mt-3"
            :buy-orders="buyOrders"
            :token-name="tokenName"
        />
        <token-trade-sell-orders
            container-class="col-12 col-md-6 mt-3"
            :sell-orders="sellOrders"
            :token-name="tokenName"
        />
        <token-trade-trade-history
            container-class="col-12 mt-3"
            :orders-history="ordersHistory"
            :token-name="tokenName"
        />
    </div>
</template>

<script>
import TokenTradeBuyOrder from './TokenTradeBuyOrder';
import TokenTradeSellOrder from './TokenTradeSellOrder';
import TokenTradeChart from './TokenTradeChart';
import TokenTradeBuyOrders from './TokenTradeBuyOrders';
import TokenTradeSellOrders from './TokenTradeSellOrders';
import TokenTradeTradeHistory from './TokenTradeTradeHistory';
import OrderModal from '../../modal/OrderModal';
import WebSocketMixin from '../../../js/mixins/websocket';

export default {
    name: 'TokenTrade',
    mixins: [WebSocketMixin],
    components: {
        TokenTradeBuyOrder,
        TokenTradeSellOrder,
        TokenTradeChart,
        TokenTradeBuyOrders,
        TokenTradeSellOrders,
        TokenTradeTradeHistory,
        OrderModal,
    },
    props: {
        ordersHistory: [String, Boolean],
        pendingBuyOrders: [String, Boolean],
        pendingSellOrders: [String, Boolean],
        websocketUrl: String,
        hash: String,
        currency: String,
        loginUrl: String,
        signupUrl: String,
        marketName: Object,
        loggedIn: Boolean,
        tokenName: String,
        placeOrderUrl: String,
        balances: [Object, Boolean],
        tokenHiddenName: String,
    },
    data() {
        return {
            buy: {
                amount: 0,
                price: 0,
            },
            sell: {
                amount: 0,
                price: 0,
            },
            buyOrders: [],
            sellOrders: [],
        };
    },
    computed: {
        market: function() {
            return this.marketName;
        },
        tokenBalance: function() {
            return this.balances[this.tokenName] ? this.balances[this.tokenName].available : false;
        },
        webBalance: function() {
            return this.balances['WEB'] ? this.balances['WEB'].available : false;
        },
    },
    mounted() {
        this.buyOrders = JSON.parse(this.pendingBuyOrders);
        this.sellOrders = JSON.parse(this.pendingSellOrders);
        setInterval(() => {
            this.$axios.get(this.$routing.generate('pending_orders', {
                tokenName: this.tokenName,
            })).then((result) => {
                this.buyOrders = result.data.buy;
                this.sellOrders = result.data.sell;
            }).catch((error) => { });
        }, 10000);
        this.addMessageHandler((result) => {
            if ('state.update' === result.method) {
                this.updateMarketData(result);
            }
        });
        this.addOnOpenHandler(() => {
            const request = JSON.stringify({
                method: 'deals.subscribe',
                params: [this.market.hiddenName],
                id: parseInt(Math.random().toString().replace('0.', '')),
            });
            this.sendMessage(request);
        });
    },
    methods: {
        updateMarketData: function(marketData) {
            if (!marketData.params) {
                return;
            }

            const marketDealsInfo = marketData.params[1];

            if (Array.isArray(marketDealsInfo)) {
                marketDealsInfo.forEach((deal) => {
                    // Pending order.
                    if (!deal.taker_id) {
                        const price = parseFloat(deal.price);
                        const amount = parseFloat(deal.amount);
                        switch (deal.type) {
                            case 'buy':
                                if (price > this.sell.price) {
                                    this.sell.price = price;
                                    this.sell.amount = amount;
                                }
                                break;
                            case 'sell':
                                if (0 === this.buy.price || price < this.buy.price) {
                                    this.buy.price = price;
                                    this.buy.amount = amount;
                                }
                                break;
                        }
                    }
                });
            }
        },
    },
};
</script>
