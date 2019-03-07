<template>
    <div class="row">
        <token-trade-chart
            class="col-12"
            :websocket-url="websocketUrl"
            :currency="currency"
            :market-name="marketName"
        />
        <div class="col-12 col-lg-4 mt-3 pr-lg-2">
            <token-trade-buy-order
                v-if="balanceLoaded"
                :websocket-url="websocketUrl"
                :hash="hash"
                :currency="currency"
                :login-url="loginUrl"
                :signup-url="signupUrl"
                :logged-in="loggedIn"
                :market="market"
                :market-price="marketPriceBuy"
                :token-name="tokenName"
                :place-order-url="placeOrderUrl"
                :balance="webBalance"
            />
            <template v-else>
                <div class="p-5 text-center text-white">
                    <font-awesome-icon icon="circle-notch" spin class="loading-spinner" fixed-width />
                </div>
            </template>
        </div>
        <div class="pt-3 col-12 col-lg-4 px-lg-2">
            <token-trade-sell-order
                v-if="balanceLoaded"
                :websocket-url="websocketUrl"
                :hash="hash"
                :currency="currency"
                :login-url="loginUrl"
                :signup-url="signupUrl"
                :logged-in="loggedIn"
                :market="market"
                :market-price="marketPriceSell"
                :token-name="tokenName"
                :place-order-url="placeOrderUrl"
                :balance="tokenBalance"
                :token-hidden-name="tokenHiddenName"
            />
            <template v-else>
                <div class="p-5 text-center text-white">
                    <font-awesome-icon icon="circle-notch" spin class="loading-spinner" fixed-width />
                </div>
            </template>
        </div>
        <div class="pt-3 col-12 col-lg-4 pl-lg-2">
            <token-top-traders/>
        </div>
        <div class="col-12 col-lg-6 pt-3 pr-lg-2">
            <token-trade-buy-orders
                v-if="ordersLoaded"
                :buy-orders="buyOrders"
                :token-name="tokenName" />
            <template v-else>
                <div class="p-5 text-center text-white">
                    <font-awesome-icon icon="circle-notch" spin class="loading-spinner" fixed-width />
                </div>
            </template>
        </div>
        <div class="col-12 col-lg-6 pt-3 pl-lg-2">
            <token-trade-sell-orders
                v-if="ordersLoaded"
                :sell-orders="sellOrders"
                :token-name="tokenName" />
            <template v-else>
                <div class="p-5 text-center text-white">
                    <font-awesome-icon icon="circle-notch" spin class="loading-spinner" fixed-width />
                </div>
            </template>
        </div>
        <token-trade-trade-history
            class="col-12 pt-3"
            :token-name="tokenName" />
    </div>
</template>

<script>
import TokenTradeBuyOrder from './TokenTradeBuyOrder';
import TokenTradeSellOrder from './TokenTradeSellOrder';
import TokenTradeChart from './TokenTradeChart';
import TokenTradeBuyOrders from './TokenTradeBuyOrders';
import TokenTradeSellOrders from './TokenTradeSellOrders';
import TokenTopTraders from './TokenTopTraders';
import TokenTradeTradeHistory from './TokenTradeTradeHistory';
import OrderModal from '../../modal/OrderModal';
import WebSocketMixin from '../../../js/mixins/websocket';
import {toMoney} from '../../../js/utils';

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
        TokenTopTraders,
        OrderModal,
    },
    props: {
        websocketUrl: String,
        hash: String,
        currency: String,
        loginUrl: String,
        signupUrl: String,
        market: Object,
        loggedIn: Boolean,
        tokenName: String,
        placeOrderUrl: String,
        tokenHiddenName: String,
    },
    data() {
        return {
            pendingBuyOrders: null,
            pendingSellOrders: null,
            buyOrders: null,
            sellOrders: null,
            balances: null,
        };
    },
    computed: {
        tokenBalance: function() {
            return this.balances[this.tokenName] ? this.balances[this.tokenName].available
                : this.balances ? toMoney(0) : false;
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
        marketPriceSell: function() {
            return this.buyOrders && this.buyOrders[0] ? this.buyOrders[0].price : 0;
        },
        marketPriceBuy: function() {
            return this.sellOrders && this.sellOrders[0] ? this.sellOrders[0].price : 0;
        },
    },
    mounted() {
        this.$axios.retry.get(this.$routing.generate('tokens'))
            .then((res) => {
                this.balances = {...res.data.common, ...res.data.predefined};
                this.authorize()
                    .then(() => {
                        this.sendMessage(JSON.stringify({
                            method: 'asset.subscribe',
                            params: [this.tokenHiddenName, this.market.currencySymbol],
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

        this.updateOrders();
        this.$store.state.interval.make(this.updateOrders, 10000);

        this.addOnOpenHandler(() => {
            this.sendMessage(JSON.stringify({
                method: 'deals.subscribe',
                params: [this.market.hiddenName],
                id: parseInt(Math.random().toString().replace('0.', '')),
            }));

            if (!this.loggedIn) {
                return;
            }

            this.authorize()
                .then(() => {
                    this.sendMessage(JSON.stringify({
                        method: 'asset.subscribe',
                        params: [this.tokenHiddenName, this.market.currencySymbol],
                        id: parseInt(Math.random().toString().replace('0.', '')),
                    }));
                })
                .catch(() => {
                    this.$toasted.error(
                        'Can not connect to internal services'
                    );
                });
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
    },
};
</script>
