<template>
    <div v-if="!disabledServices.tradingDisabled && !disabledServices.allServicesDisabled" class="container-fluid px-0">
        <div class="row">
            <trade-chart
                :is-token="isToken"
                class="col"
                :websocket-url="websocketUrl"
                :market="market"
                :buy-depth="buyDepth"
                :mintme-supply-url="mintmeSupplyUrl"
                :minimum-volume-for-marketcap="minimumVolumeForMarketcap"
                :is-mintme-token="isMintmeToken"
            />
        </div>
        <div class="row trade-orders">
            <div class="col-12 col-lg-6 pr-lg-2 mt-3">
                <trade-buy-order
                    :websocket-url="websocketUrl"
                    :hash="hash"
                    :login-url="loginUrl"
                    :signup-url="signupUrl"
                    :logged-in="loggedIn"
                    :market="market"
                    :balance="baseBalance"
                    :balance-loaded="balanceLoaded"
                    :taker-fee="takerFee"
                    :trade-disabled="tradeDisabled"
                    @check-input="checkInput"
                    :currency-mode="currencyMode"
                    @making-order-prevented="addPhoneModalVisible = true"
                />
            </div>
            <div class="col-12 col-lg-6 pl-lg-2 mt-3">
                 <trade-sell-order
                    :websocket-url="websocketUrl"
                    :hash="hash"
                    :login-url="loginUrl"
                    :signup-url="signupUrl"
                    :logged-in="loggedIn"
                    :market="market"
                    :balance="quoteBalance"
                    :balance-loaded="balanceLoaded"
                    :is-owner="isOwner"
                    :trade-disabled="tradeDisabled"
                    @check-input="checkInput"
                    :currency-mode="currencyMode"
                    @making-order-prevented="addPhoneModalVisible = true"
                />
            </div>
            <add-phone-alert-modal
                :visible="addPhoneModalVisible"
                :message="addPhoneModalMessage"
                @close="addPhoneModalVisible = false"
            />
    </div>
        <div class="row">
            <trade-orders
                @update-data="updateOrders"
                :orders-loaded="ordersLoaded"
                :orders-updated="ordersUpdated"
                :buy-orders="buyOrders"
                :sell-orders="sellOrders"
                :total-sell-orders="totalSellOrders"
                :total-buy-orders="totalBuyOrders"
                :market="market"
                :user-id="userId"
                :logged-in="loggedIn"
                :currency-mode="currencyMode"/>
        </div>
        <div class="row mt-3">
            <trade-trade-history
                class="col"
                :hash="hash"
                :websocket-url="websocketUrl"
                :market="market"
                :currency-mode="currencyMode"
            />
        </div>
    </div>
    <div v-else>
        <div class="h1 text-center pt-5 mt-5">
            {{ $t('trade.page.disabled') }}
        </div>
    </div>
</template>

<script>
import TradeBuyOrder from './TradeBuyOrder';
import TradeSellOrder from './TradeSellOrder';
import TradeChart from './TradeChart';
import TradeOrders from './TradeOrders';
import TradeTradeHistory from './TradeTradeHistory';
import {
    CheckInputMixin,
    LoggerMixin,
    NotificationMixin,
    WebSocketMixin,
    AddPhoneAlertMixin,
} from '../../mixins';
import {mapMutations, mapGetters} from 'vuex';
import {toMoney, Constants} from '../../utils';
import AddPhoneAlertModal from '../modal/AddPhoneAlertModal';
import Decimal from 'decimal.js';

const WSAPI = Constants.WSAPI;

export default {
    name: 'Trade',
    mixins: [
        CheckInputMixin,
        LoggerMixin,
        NotificationMixin,
        WebSocketMixin,
        AddPhoneAlertMixin,
    ],
    components: {
        TradeBuyOrder,
        TradeSellOrder,
        TradeChart,
        TradeOrders,
        TradeTradeHistory,
        AddPhoneAlertModal,
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
        mintmeSupplyUrl: String,
        minimumVolumeForMarketcap: Number,
        isToken: Boolean,
        disabledServicesConfig: String,
        takerFee: Number,
        isMintmeToken: Boolean,
        profileNickname: String,
    },
    data() {
        return {
            buyOrders: null,
            sellOrders: null,
            totalSellOrders: null,
            totalBuyOrders: null,
            sellPage: 2,
            buyPage: 2,
            buyDepth: null,
            ordersUpdated: false,
            addPhoneModalMessageType: 'make_orders',
            addPhoneModalProfileNickName: this.profileNickname,
        };
    },
    computed: {
        ...mapGetters('rates', [
            'getRequesting',
        ]),
        ...mapGetters('tradeBalance', {
            balances: 'getBalances',
        }),
        currencyMode: function() {
            return localStorage.getItem('_currency_mode');
        },
        baseBalance: function() {
            return this.balances && this.balances[this.market.base.symbol] ? this.balances[this.market.base.symbol].available
                : false;
        },
        quoteBalance: function() {
            return this.balances && this.balances[this.market.quote.symbol] ? this.balances[this.market.quote.symbol].available
                : false;
        },
        balanceLoaded: function() {
            return this.balances !== null;
        },
        ordersLoaded: function() {
            return this.buyOrders !== null && this.sellOrders !== null;
        },
        marketPriceSell: function() {
            return this.buyOrders && this.buyOrders[0] ? this.buyOrders.reduce((max, order) => Math.max(parseFloat(order.price), max), 0) : 0;
        },
        marketPriceBuy: function() {
            return this.sellOrders && this.sellOrders[0] ? this.sellOrders.reduce((min, order) => Math.min(parseFloat(order.price), min), Infinity) : 0;
        },
        requestingRates: {
            get() {
                return this.getRequesting;
            },
            set(val) {
                this.setRequesting(val);
            },
        },
        /**
         * @return {object}
         */
        disabledServices: function() {
            return JSON.parse(this.disabledServicesConfig);
        },
        /**
         * @return {boolean}
         */
        tradeDisabled: function() {
            return this.disabledServices.newTradesDisabled || this.disabledServices.allServicesDisabled;
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
            }, 'trade-update-orders', 'Trade');
        });

        if (!this.requestingRates) {
            this.requestingRates = true;
            this.$axios.retry.get(this.$routing.generate('exchange_rates'))
            .then((res) => {
                this.setRates(res.data);
            })
            .catch((err) => {
                this.sendLogs('error', 'Can\'t load conversion rates', err);
            })
            .finally(() => {
                this.requestingRates = false;
            });
        }
    },
    methods: {
        ...mapMutations('rates', [
            'setRates',
            'setRequesting',
        ]),
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
                        this.totalSellOrders = new Decimal(result.data.totalSellOrders);
                        this.totalBuyOrders = new Decimal(result.data.totalBuyOrders);
                        this.buyDepth = toMoney(result.data.buyDepth, this.market.base.subunit);
                        resolve();
                    }).catch((err) => {
                        this.sendLogs('error', 'Can not update orders', err);
                        reject();
                    });
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
                        this.totalSellOrders = new Decimal(result.data.totalSellOrders);
                        this.totalBuyOrders = new Decimal(result.data.totalBuyOrders);

                        context.resolve();
                        resolve(result.data);
                    }).catch((err) => {
                        this.sendLogs('error', 'Can not update orders', err);
                        reject();
                    });
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

                        if (isSell) {
                            this.totalSellOrders = this.totalSellOrders.add(data.amount);
                        } else {
                            this.totalBuyOrders = this.totalBuyOrders.add(data.price);
                        }

                        this.saveOrders(orders, isSell);
                        this.ordersUpdated = true;
                    })
                    .catch((err) => {
                        this.notifyError(this.$t('toasted.error.can_not_update_orders'));
                        this.sendLogs('error', 'Can not update orders', err);
                    });
                    break;
                case WSAPI.order.status.UPDATE:
                    if (typeof order === 'undefined') {
                        return;
                    }

                    if (!order.hasOwnProperty('createdTimestamp') && data.hasOwnProperty('ctime')) {
                        order.createdTimestamp = data.ctime;
                    }

                    let index = orders.indexOf(order);
                    order.amount = data.left;
                    order.price = data.price;
                    order.timestamp = data.mtime;
                    orders[index] = order;

                    if (isSell) {
                      this.totalSellOrders = this.totalSellOrders.sub(data.amount).add(order.amount);
                    } else {
                      this.totalBuyOrders = this.totalBuyOrders.sub(order.price).add(data.price);
                    }

                    this.ordersUpdated = true;
                    break;
                case WSAPI.order.status.FINISH:
                    if (typeof order === 'undefined') {
                        return;
                    }

                    this.ordersUpdated = true;
                    orders.splice(orders.indexOf(order), 1);

                    if (isSell) {
                      this.totalSellOrders = this.totalSellOrders.sub(order.amount);
                    } else {
                      this.totalBuyOrders = this.totalBuyOrders.sub(order.price);
                    }

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
