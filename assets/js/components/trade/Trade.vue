<template>
    <div class="container-fluid px-0">
        <div class="row px-0">
            <trade-chart
                    class="col"
                    :websocket-url="websocketUrl"
                    :market="market"
            />
        </div>
        <div class="row">
            <div class="col-12 col-lg-6 mt-3 pr-lg-2">
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
                        @check-input="checkInput"
                />
                <template v-else>
                    <div class="p-5 text-center text-white">
                        <font-awesome-icon icon="circle-notch" spin class="loading-spinner" fixed-width />
                    </div>
                </template>
            </div>
            <div class="col-12 col-lg-6 mt-3 pl-lg-2">
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
                        @check-input="checkInput"
                />
                <template v-else>
                    <div class="p-5 text-center text-white">
                        <font-awesome-icon icon="circle-notch" spin class="loading-spinner" fixed-width />
                    </div>
                </template>
            </div>
        </div>
        <div class="row">
            <trade-orders
                @update-data="updateOrders"
                :orders-loaded="ordersLoaded"
                :buy-orders="buyOrders"
                :sell-orders="sellOrders"
                :market="market"
                :user-id="userId"
                :logged-in="loggedIn"/>
        </div>
        <div class="row px-0 mt-3">
            <trade-trade-history
                class="col"
                :hash="hash"
                :websocket-url="websocketUrl"
                :market="market" />
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
import {isRetryableError} from 'axios-retry';
import {WebSocketMixin} from '../../mixins';
import {toMoney, Constants} from '../../utils';

const WSAPI = Constants.WSAPI;

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
        precision: Number,
    },
    data() {
        return {
            buyOrders: null,
            sellOrders: null,
            balances: null,
            sellPage: 2,
            buyPage: 2,
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
        this.updateOrders().then(() => {
            this.sendMessage(JSON.stringify({
                method: 'order.subscribe',
                params: [this.market.identifier],
                id: parseInt(Math.random().toString().replace('0.', '')),
            }));

            this.addMessageHandler((response) => {
                if ('order.update' === response.method) {
                    this.processOrders(response.params[1], response.params[0]);
                }
            }, 'trade-update-orders');
        });

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
        checkInput: function(precision) {
            let inputPos = event.target.selectionStart;
            let amount = event.srcElement.value;
            let selected = getSelection().toString();
            let regex = new RegExp(`^[0-9]{0,8}(\\.[0-9]{0,${precision}})?$`);
            let input = event instanceof ClipboardEvent
                ? event.clipboardData.getData('text')
                : String.fromCharCode(!event.charCode ? event.which : event.charCode);

            if (selected && regex.test(amount.slice(0, inputPos) + input + amount.slice(inputPos + selected.length))) {
                return true;
            }
            if (!regex.test(amount.slice(0, inputPos) + input + amount.slice(inputPos))) {
                event.preventDefault();
                return false;
            }
        },
        /**
         * @param {undefined|{type, isAssigned, resolve}} context
         * @return {Promise}
         */
        updateOrders: function(context) {
            return new Promise((resolve, reject) => {
                if (!context) {
                    this.$axios.retry.get(this.$routing.generate('pending_orders', {
                        base: this.market.base.symbol, quote: this.market.quote.symbol,
                    })).then((result) => {
                        this.buyOrders = result.data.buy;
                        this.sellOrders = result.data.sell;
                        resolve();
                    }).catch(reject);
                } else {
                    this.$axios.retry.get(this.$routing.generate('pending_orders', {
                        base: this.market.base.symbol,
                        quote: this.market.quote.symbol,
                        page: context.type === 'sell' ?
                            this.sellPage :
                            this.buyPage,
                    })).then((result) => {
                        if (!result.data.sell.length && !result.data.buy.length) {
                            context.resolve();
                            return resolve([]);
                        }

                        switch (context.type) {
                            case 'sell':
                                this.sellOrders = this.sellOrders.concat(result.data.sell);
                                this.sellPage++;
                                break;
                            case 'buy':
                                this.buyOrders = this.buyOrders.concat(result.data.buy);
                                this.buyPage++;
                                break;
                        }

                        context.resolve();
                        resolve(result.data);
                    }).catch(reject);
                }
            });
        },
        updateAssets: function() {
            this.$axios.retry.get(this.$routing.generate('tokens'))
                .then((res) => {
                    this.balances = {...res.data.common, ...res.data.predefined};

                    if (!this.balances.hasOwnProperty(this.market.quote.symbol)) {
                        this.balances[this.market.quote.symbol] = {available: toMoney(0, this.precision)};
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
                    if (!isRetryableError(err)) {
                        this.balances = false;
                    } else {
                        this.$toasted.error('Can not load current balance. Try again later.');
                    }
                });
        },
        processOrders: function(data, type) {
            const isSell = WSAPI.order.type.SELL === parseInt(data.side);
            let orders = isSell ? this.sellOrders : this.buyOrders;
            let order = orders.find((order) => data.id === order.id);

            switch (type) {
                case WSAPI.order.status.PUT:
                    this.$axios.retry.get(this.$routing.generate('pending_order_details', {
                        base: this.market.base.symbol,
                        quote: this.market.quote.symbol,
                        id: data.id,
                    }))
                    .then((res) => {
                        orders.push(res.data);
                        orders = orders.sort((a, b) => {
                            return isSell ?
                                parseFloat(a.price) - parseFloat(b.price) :
                                parseFloat(b.price) - parseFloat(a.price);
                        });
                        this.saveOrders(orders, isSell);
                    })
                    .catch(() => this.$toasted.error('Something went wrong. Can not update orders.'));
                    break;
                case WSAPI.order.status.UPDATE:
                    if (typeof order === 'undefined') {
                        return;
                    }

                    let index = orders.indexOf(order);
                    order.amount = data.left;
                    order.price = data.price;
                    order.timestamp = data.mtime;
                    orders[index] = order;
                    break;
                case WSAPI.order.status.FINISH:
                    if (typeof order === 'undefined') {
                        return;
                    }

                    orders.splice(orders.indexOf(order), 1);
                    break;
            }

            this.saveOrders(orders, isSell);
        },
        saveOrders: function(orders, isSell) {
            if (isSell) {
                this.sellOrders = orders;
            } else {
                this.buyOrders = orders;
            }
        },
    },
};
</script>
