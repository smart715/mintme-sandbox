<template>
    <div class="row">
        <token-trade-chart
            class="col-12"
            :websocket-url="websocketUrl"
            :currency="currency"
            :market-name="marketName"
        />
        <div class="buy-order col-12 col-md-6 col-lg-4 mt-3">
            <token-trade-buy-order
                v-if="balanceLoaded"
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
            <template v-else>
                <font-awesome-icon
                    icon="circle-notch"
                    spin class="loading-spinner d-block text-white mx-auto my-3"
                    size="5x" />
            </template>
        </div>
        <div class="sell-order mt-3 col-12 col-md-6 col-lg-4">
            <token-trade-sell-order
                v-if="balanceLoaded"
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
            <template v-else>
                <font-awesome-icon
                    icon="circle-notch"
                    spin class="loading-spinner d-block text-white mx-auto my-3"
                    size="5x" />
            </template>
        </div>
        <div class="col-12 col-md-6 mt-3">
            <token-trade-buy-orders
                v-if="ordersLoaded"
                :buy-orders="buyOrders"
                :token-name="tokenName" />
            <template v-else>
                <font-awesome-icon
                    icon="circle-notch"
                    spin
                    class="loading-spinner d-block text-white mx-auto my-3"
                    size="5x" />
            </template>
        </div>
        <div class="col-12 col-md-6 mt-3">
            <token-trade-sell-orders
                v-if="ordersLoaded"
                :sell-orders="sellOrders"
                :token-name="tokenName" />
            <template v-else>
                <font-awesome-icon
                    icon="circle-notch"
                    spin
                    class="loading-spinner d-block text-white mx-auto my-3"
                    size="5x" />
            </template>
        </div>
        <token-trade-trade-history
            class="col-12 mt-3"
            :token-name="tokenName" />
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
        websocketUrl: String,
        hash: String,
        currency: String,
        loginUrl: String,
        signupUrl: String,
        marketName: Object,
        loggedIn: Boolean,
        tokenName: String,
        placeOrderUrl: String,
        tokenHiddenName: String,
    },
    data() {
        return {
            pendingBuyOrders: null,
            pendingSellOrders: null,
            buy: {
                amount: 0,
                price: 0,
            },
            sell: {
                amount: 0,
                price: 0,
            },
            buyOrders: null,
            sellOrders: null,
            balances: null,
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
        balanceLoaded: function() {
            return this.balances !== null;
        },
        ordersLoaded: function() {
            return this.buyOrders !== null && this.sellOrders !== null;
        },
    },
    mounted() {
        this.$axios.retry.get(this.$routing.generate('tokens'))
            .then((res) => this.balances = {...res.data.common, ...res.data.predefined})
            .catch((err) => {
                if (401 === err.response.status) {
                    this.balances = false;
                } else {
                    this.$toasted.error('Can not load current balance. try again later.');
                }
            });

        this.updateOrders();
        this.$store.state.interval.make(this.updateOrders, 10000);

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
        updateOrders: function() {
            this.$axios.single.get(this.$routing.generate('pending_orders', {
                'tokenName': this.tokenName,
            })).then((result) => {
                this.buyOrders = result.data.buy;
                this.sellOrders = result.data.sell;
            }).catch((error) => { });
        },
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
