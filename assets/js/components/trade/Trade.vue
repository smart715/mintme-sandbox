<template>
    <div class="container-fluid px-0">
        <div class="row px-0">
            <trade-chart
                    class="col"
                    :websocket-url="websocketUrl"
                    :market="market"
            />
        </div>
        <div class="row px-0 mt-3">
            <div class="col-12 col-lg-6 pr-lg-2">
                <trade-buy-order
                        v-if="balanceLoaded"
                        :websocket-url="websocketUrl"
                        :hash="hash"
                        :login-url="loginUrl"
                        :signup-url="signupUrl"
                        :logged-in="loggedIn"
                        :market="market"
                        :market-price="marketPriceBuy"
                        :balance="baseBalance"
                />
                <template v-else>
                    <div class="p-5 text-center text-white">
                        <font-awesome-icon icon="circle-notch" spin class="loading-spinner" fixed-width />
                    </div>
                </template>
            </div>
            <div class="col-12 col-lg-6 pl-lg-2">
                <trade-sell-order
                        v-if="balanceLoaded"
                        :websocket-url="websocketUrl"
                        :hash="hash"
                        :login-url="loginUrl"
                        :signup-url="signupUrl"
                        :logged-in="loggedIn"
                        :market="market"
                        :market-price="marketPriceSell"
                        :balance="quoteBalance"
                        :is-owner="isOwner"
                />
                <template v-else>
                    <div class="p-5 text-center text-white">
                        <font-awesome-icon icon="circle-notch" spin class="loading-spinner" fixed-width />
                    </div>
                </template>
            </div>
        </div>
        <trade-orders :orders-loaded="ordersLoaded" :buy-orders="buyOrders" :sell-orders="sellOrders" :market="market" :user-id="userId" />
        <div class="row px-0 mt-3">
            <trade-trade-history class="col" :market="market" />
        </div>
    </div>
</template>

<script>
import TradeBuyOrder from './TradeBuyOrder';
import TradeSellOrder from './TradeSellOrder';
import TradeChart from './TradeChart';
import TradeOrders from './TradeOrders';
import TopTraders from './TopTraders';
import TradeTradeHistory from './TradeTradeHistory';
import OrderModal from '../modal/OrderModal';
import WebSocketMixin from '../../mixins/websocket';
import {toMoney} from '../../utils';

export default {
    name: 'Trade',
    mixins: [WebSocketMixin],
    components: {
        TradeBuyOrder,
        TradeSellOrder,
        TradeChart,
        TradeOrders,
        TradeTradeHistory,
        TopTraders,
        OrderModal,
    },
    props: {
        websocketUrl: String,
        hash: String,
        loginUrl: String,
        signupUrl: String,
        market: Object,
        loggedIn: Boolean,
        tokenName: String,
        isOwner: Boolean,
        userId: Number,
    },
    data() {
        return {
            buyOrders: null,
            sellOrders: null,
            balances: null,
        };
    },
    computed: {
        baseBalance: function() {
            return this.balances[this.market.base.symbol] ? this.balances[this.market.base.symbol].available
                : false;
        },
        quoteBalance: function() {
            return this.balances[this.market.quote.symbol] ? this.balances[this.market.quote.symbol].available
                : false;
        },
        balanceLoaded: function() {
            return this.balances !== null;
        },
        ordersLoaded: function() {
            return this.buyOrders !== null && this.sellOrders !== null;
        },
        marketPriceSell: function() {
            return this.buyOrders && this.buyOrders[0] ? this.buyOrders[0].price : 0;
        },
        marketPriceBuy: function() {
            return this.sellOrders && this.sellOrders[0] ? this.sellOrders[0].price : 0;
        },
    },
    mounted() {
        this.updateOrders();
        this.$store.state.interval.make(this.updateOrders, 10000);

        this.addOnOpenHandler(() => {
            this.sendMessage(JSON.stringify({
                method: 'deals.subscribe',
                params: [this.market.identifier],
                id: parseInt(Math.random().toString().replace('0.', '')),
            }));

            this.updateAssets();
        });
    },
    methods: {
        updateOrders: function() {
            this.$axios.single.get(this.$routing.generate('pending_orders', {
                'base': this.market.base.symbol,
                'quote': this.market.quote.symbol,
            })).then((result) => {
                this.buyOrders = result.data.buy;
                this.sellOrders = result.data.sell;
            }).catch((error) => { });
        },
        updateAssets: function() {
            this.$axios.retry.get(this.$routing.generate('tokens'))
                .then((res) => {
                    this.balances = {...res.data.common, ...res.data.predefined};

                    if (!this.balances.hasOwnProperty(this.market.quote.symbol)) {
                        this.balances[this.market.quote.symbol] = {available: toMoney(0)};
                    }

                    this.authorize()
                        .then(() => {
                            this.sendMessage(JSON.stringify({
                                method: 'asset.subscribe',
                                params: [this.market.base.identifier, this.market.quote.identifier],
                                id: parseInt(Math.random().toString().replace('0.', '')),
                            }));
                        })
                        .catch(() => {
                            this.$toasted.error(
                                'Can not connect to internal services'
                            );
                        });
                })
                .catch((err) => {
                    if (401 === err.response.status) {
                        this.balances = false;
                    } else {
                        this.$toasted.error('Can not load current balance. Try again later.');
                    }
                });
        },
    },
};
</script>
