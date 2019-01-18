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
            :buy-orders="pendingBuyOrders"
            :token-name="tokenName"
        />
        <token-trade-sell-orders
            container-class="col-12 col-md-6 mt-3"
            :sell-orders="pendingSellOrders"
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
import WebSocket from '../../../js/websocket';
import OrderModal from '../../modal/OrderModal';

Vue.use(WebSocket);

export default {
    name: 'TokenTrade',
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
        ordersHistory: String,
        pendingBuyOrders: String,
        pendingSellOrders: String,
        websocketUrl: String,
        hash: String,
        currency: String,
        loginUrl: String,
        signupUrl: String,
        marketName: Object,
        loggedIn: Boolean,
        tokenName: String,
        placeOrderUrl: String,
        balances: Object,
        tokenHiddenName: String,
    },
    data() {
        return {
            wsClient: null,
            wsResult: {},
            buy: {
                amount: 0,
                price: 0,
            },
            sell: {
                amount: 0,
                price: 0,
            },
        };
    },
    computed: {
        market: function() {
            return this.marketName;
        },
        tokenBalance: function() {
            return this.balances[this.tokenName] ? this.balances[this.tokenName].available : '';
        },
        webBalance: function() {
            return this.balances['WEB'] ? this.balances['WEB'].available : '';
        },
    },
    mounted() {
        if (this.websocketUrl) {
            this.wsClient = this.$socket(this.websocketUrl);
            this.wsClient.onmessage = (result) => {
                if (typeof result.data === 'string') {
                    this.wsResult = JSON.parse(result.data);
                }
            };
            this.wsClient.onopen = () => {
                const request = JSON.stringify({
                    method: 'deals.subscribe',
                    params: [this.market.hiddenName],
                    id: parseInt(Math.random().toString().replace('0.', '')),
                });
                this.wsClient.send(request);
            };
        }
    },
    methods: {
        updateMarketData: function(marketData) {
            if (!marketData.params) {
                return;
            }

            const marketDealsInfo = marketData.params[1];

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
        },
    },
    watch: {
        wsResult: {
            handler(value) {
                this.updateMarketData(value);
            },
            deep: true,
        },
    },
};
</script>
