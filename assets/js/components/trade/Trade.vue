<template>
    <div
        v-if="showTradePage"
        class="container-fluid px-0"
    >
        <div
            v-if="isToken"
            class="row mt-3 ml-3"
        >
            <ul
                v-if="showMarketsTab"
                class="nav nav-tabs trading-tabs"
            >
                <li
                    v-for="market in markets"
                    :key="market.base.symbol"
                    class="nav nav-item font-size-2 mb-2"
                >
                    <a
                        class="btn mr-3"
                        :class="getMarketTabClass(market)"
                        :href="getTradeUrl(market)"
                        @click.prevent="changeMarket(market)"
                    >
                        {{ market.base.symbol | rebranding }}
                    </a>
                </li>
            </ul>
            <a
                v-if="showAddMarketButton"
                class="btn btn-secondary mb-2"
                :href="tokenSettingsMarketsTabLink"
            >
                <font-awesome-icon
                    icon="plus"
                    class="mr-1"
                />
                {{ $t('trade.add_market.label') }}
            </a>
        </div>
        <div class="row mx-3">
            <trade-chart
                :orders-loaded="ordersLoaded"
                :is-token="isToken"
                class="col"
                :websocket-url="websocketUrl"
                :market="currentMarket"
                :buy-depth="buyDepth"
                :mintme-supply-url="mintmeSupplyUrl"
                :minimum-volume-for-marketcap="minimumVolumeForMarketcap"
                :is-created-on-mintme-site="isCreatedOnMintmeSite"
                :changing-market="changingMarket"
                :trades-disabled="tradesDisabled"
            />
        </div>
        <div id="trade-section" class="row trade-orders mx-0">
            <div class="col-12 col-lg-6 mt-3 pr-lg-2">
                <trade-buy-order
                    :websocket-url="websocketUrl"
                    :hash="hash"
                    :login-url="loginUrl"
                    :signup-url="signupUrl"
                    :logged-in="loggedIn"
                    :market="currentMarket"
                    :balance="baseBalance"
                    :balance-loaded="balanceLoaded"
                    :service-unavailable="serviceUnavailable"
                    :is-owner="isOwner"
                    :is-created-on-mintme-site="isCreatedOnMintmeSite"
                    :disabled-services-config="disabledServicesConfig"
                    :disabled-cryptos="disabledCryptos"
                    :is-user-blocked="isUserBlocked"
                    :taker-fee="takerFee"
                    :trade-disabled="tradeDisabled"
                    :currency-mode="currencyMode"
                    :changing-market="changingMarket"
                    ref="tradeBuyOrder"
                    @check-input="checkInput"
                    @making-order-prevented="preventAction('tradeBuyOrder')"
                />
            </div>
            <div class="col-12 col-lg-6 mt-3 pl-lg-2">
                 <trade-sell-order
                    :websocket-url="websocketUrl"
                    :hash="hash"
                    :login-url="loginUrl"
                    :signup-url="signupUrl"
                    :logged-in="loggedIn"
                    :market="currentMarket"
                    :balance="quoteBalance"
                    :balance-loaded="balanceLoaded"
                    :service-unavailable="serviceUnavailable"
                    :is-owner="isOwner"
                    :is-created-on-mintme-site="isCreatedOnMintmeSite"
                    :disabled-services-config="disabledServicesConfig"
                    :disabled-cryptos="disabledCryptos"
                    :is-user-blocked="isUserBlocked"
                    :trade-disabled="tradeDisabled"
                    :currency-mode="currencyMode"
                    :changing-market="changingMarket"
                    ref="tradeSellOrder"
                    @check-input="checkInput"
                    @making-order-prevented="preventAction('tradeSellOrder')"
                />
            </div>
            <add-phone-alert-modal
                :visible="addPhoneModalVisible"
                :message="addPhoneModalMessage"
                @close="addPhoneModalVisible = false"
                @phone-verified="onPhoneVerified"
            />
        </div>
        <div class="row mx-0">
            <trade-orders
                @update-data="updateOrders"
                :is-token="isToken"
                :orders-loaded="ordersLoaded"
                :orders-updated="ordersUpdated"
                :buy-orders="buyOrders"
                :sell-orders="sellOrders"
                :total-sell-orders="totalSellOrders"
                :total-buy-orders="totalBuyOrders"
                :service-unavailable="serviceUnavailable"
                :market="currentMarket"
                :user-id="userId"
                :logged-in="loggedIn"
                :currency-mode="currencyMode"/>
        </div>
        <div class="row mt-3 mx-0">
            <trade-trade-history
                class="col"
                :hash="hash"
                :websocket-url="websocketUrl"
                :market="currentMarket"
                :changing-market="changingMarket"
                :is-token="isToken"
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
    NotificationMixin,
    WebSocketMixin,
    AddPhoneAlertMixin,
    RebrandingFilterMixin,
} from '../../mixins';
import {mapMutations, mapGetters} from 'vuex';
import {toMoney, Constants} from '../../utils';
import AddPhoneAlertModal from '../modal/AddPhoneAlertModal';
import Decimal from 'decimal.js';
import {debounce} from 'lodash';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import {library} from '@fortawesome/fontawesome-svg-core';
import {faPlus} from '@fortawesome/free-solid-svg-icons';

library.add(faPlus);

const WSAPI = Constants.WSAPI;

export default {
    name: 'Trade',
    mixins: [
        CheckInputMixin,
        NotificationMixin,
        WebSocketMixin,
        AddPhoneAlertMixin,
        RebrandingFilterMixin,
    ],
    components: {
        TradeBuyOrder,
        TradeSellOrder,
        TradeChart,
        TradeOrders,
        TradeTradeHistory,
        AddPhoneAlertModal,
        FontAwesomeIcon,
    },
    props: {
        websocketUrl: String,
        hash: String,
        loginUrl: String,
        signupUrl: String,
        loggedIn: Boolean,
        tokenName: String,
        isOwner: Boolean,
        userId: Number,
        precision: Number,
        mintmeSupplyUrl: String,
        minimumVolumeForMarketcap: Number,
        isToken: Boolean,
        disabledServicesConfig: String,
        disabledCryptos: Array,
        isUserBlocked: Boolean,
        takerFee: Number,
        isCreatedOnMintmeSite: Boolean,
        profileNickname: String,
        minimumOrder: String,
        newMarketsEnabled: {
            type: Boolean,
            default: false,
        },
        tradesDisabled: Boolean,
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
            preventedActionRefName: null,
            changingMarket: false,
            requestsCounter: 0,
            startTime: Date.now(),
            addNewOrderDebounce: null,
        };
    },
    computed: {
        ...mapGetters('rates', [
            'getRequesting',
        ]),
        ...mapGetters('tradeBalance', {
            balances: 'getBalances',
            balanceServiceUnavailable: 'isServiceUnavailable',
        }),
        ...mapGetters('orders', {
            orderServiceUnavailable: 'isServiceUnavailable',
        }),
        ...mapGetters('market', {
            markets: 'getMarkets',
            currentMarket: 'getCurrentMarket',
        }),
        ...mapGetters('crypto', {
            enabledCryptos: 'getCryptos',
        }),
        serviceUnavailable: function() {
            return this.balanceServiceUnavailable || this.orderServiceUnavailable;
        },
        marketsSymbols: function() {
            return Object.keys(this.markets);
        },
        currencyMode: function() {
            return localStorage.getItem('_currency_mode');
        },
        baseBalance: function() {
            return this.balances && this.balances[this.currentMarket.base.symbol]
                ? this.balances[this.currentMarket.base.symbol].available
                : false;
        },
        quoteBalance: function() {
            return this.balances && this.balances[this.currentMarket.quote.symbol]
                ? this.balances[this.currentMarket.quote.symbol].available
                : false;
        },
        balanceLoaded: function() {
            return null !== this.balances;
        },
        ordersLoaded: function() {
            return null !== this.buyOrders && null !== this.sellOrders;
        },
        marketPriceSell: function() {
            return this.buyOrders && this.buyOrders[0]
                ? this.buyOrders.reduce((max, order) => Math.max(parseFloat(order.price), max), 0)
                : 0;
        },
        marketPriceBuy: function() {
            return this.sellOrders && this.sellOrders[0]
                ? this.sellOrders.reduce((min, order) => Math.min(parseFloat(order.price), min), Infinity)
                : 0;
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
            return this.disabledServices.newTradesDisabled
                || this.disabledServices.allServicesDisabled
                || this.tradesDisabled;
        },
        showTradePage() {
            return !this.disabledServices.tradingDisabled
                && !this.disabledServices.allServicesDisabled;
        },
        showMarketsTab() {
            return this.newMarketsEnabled && 1 < this.marketsSymbols.length;
        },
        showAddMarketButton() {
            return this.newMarketsEnabled
                && this.isOwner
                && !this.hasMaxTokenMarkets;
        },
        hasMaxTokenMarkets() {
            return !this.enabledCryptos.some((crypto) => !this.marketsSymbols.includes(crypto.symbol));
        },
        tokenSettingsMarketsTabLink() {
            return this.$routing.generate('token_settings', {
                tokenName: this.tokenName,
                tab: 'markets',
            });
        },
    },
    created() {
        this.addNewOrderDebounce = debounce(this.addNewOrder, 1000);
    },
    mounted() {
        this.setMinOrder(this.minimumOrder);

        this.updateOrders()
            .then(() => this.subscribeOnWsUpdate());

        if (!this.requestingRates) {
            this.requestingRates = true;

            this.$axios.retry.get(this.$routing.generate('exchange_rates'))
                .then((res) => this.setRates(res.data))
                .catch((err) => this.$logger.error('Can\'t load conversion rates', err))
                .finally(() => this.requestingRates = false);
        }
    },
    methods: {
        ...mapMutations('rates', [
            'setRates',
            'setRequesting',
        ]),
        ...mapMutations('market', [
            'setCurrentMarketIndex',
        ]),
        ...mapMutations('minOrder', [
            'setMinOrder',
        ]),
        ...mapMutations('order', {
            setOrderServiceUnavailable: 'setServiceUnavailable',
        }),
        subscribeOnWsUpdate: function() {
            this.sendMessage(JSON.stringify({
                method: 'order.subscribe',
                params: [this.currentMarket.identifier],
                id: parseInt(Math.random().toString().replace('0.', '')),
            }));

            this.addMessageHandler((response) => {
                if ('order.update' === response.method) {
                    this.processOrders(response.params[1], response.params[0]);
                }
            }, 'trade-update-orders', 'Trade');
        },
        updateOrders: function(context = undefined) {
            return new Promise((resolve, reject) => {
                if (!context) {
                    const baseSymbol = this.currentMarket.base.symbol;
                    this.$axios.retry.get(this.$routing.generate('pending_orders', {
                        base: this.currentMarket.base.symbol,
                        quote: this.currentMarket.quote.symbol,
                    })).then((result) => {
                        if (baseSymbol !== this.currentMarket.base.symbol) {
                            return reject();
                        }

                        this.buyOrders = result.data.buy;
                        this.sellOrders = result.data.sell;
                        this.totalSellOrders = new Decimal(result.data.totalSellOrders);
                        this.totalBuyOrders = new Decimal(result.data.totalBuyOrders);
                        this.buyDepth = toMoney(result.data.buyDepth, this.currentMarket.base.subunit);

                        resolve();
                    }).catch((err) => {
                        this.$logger.error('Can not update orders', err);
                        this.setOrderServiceUnavailable(true);
                        reject();
                    });
                } else {
                    this.$axios.retry.get(this.$routing.generate('pending_orders', {
                        base: this.currentMarket.base.symbol,
                        quote: this.currentMarket.quote.symbol,
                        page: 'sell' === context.type ?
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
                        this.$logger.error('Can not update orders', err);
                        reject();
                    });
                }
            });
        },
        addNewOrder: function(data, orders, isSell) {
            // if n orders are added, update orders with 1 request instead of doing n requests
            if (5 <= this.requestsCounter) {
                this.updateOrders();
                return;
            }

            this.$axios.retry.get(this.$routing.generate('pending_order_details', {
                base: this.currentMarket.base.symbol,
                quote: this.currentMarket.quote.symbol,
                id: data.id,
            }))
                .then((res) => {
                    orders.push(res.data);

                    if (isSell) {
                        this.totalSellOrders = this.totalSellOrders.add(data.left);
                    } else {
                        this.totalBuyOrders = this.totalBuyOrders.add(
                            new Decimal(data.price).mul(data.left)
                        );
                    }

                    this.saveOrders(orders, isSell);
                    this.ordersUpdated = true;
                })
                .catch((err) => {
                    this.$logger.error('Can not fetch pending order', err);
                });
        },
        processOrders: async function(data, type) {
            const isSell = WSAPI.order.type.SELL === parseInt(data.side);
            const orders = isSell ? this.sellOrders : this.buyOrders;
            const order = orders.find((order) => data.id === order.id);

            switch (type) {
                case WSAPI.order.status.PUT:
                    this.requestsCounter = 1000 > Date.now() - this.startTime
                        ? this.requestsCounter + 1
                        : 0;

                    this.startTime = Date.now();

                    this.addNewOrderDebounce.cancel();
                    this.addNewOrderDebounce(data, orders, isSell);

                    break;
                case WSAPI.order.status.UPDATE:
                    if ('undefined' === typeof order) {
                        return;
                    }

                    if (!order.hasOwnProperty('createdTimestamp') && data.hasOwnProperty('ctime')) {
                        order.createdTimestamp = data.ctime;
                    }

                    const index = orders.indexOf(order);
                    const deltaAmount = (new Decimal(order.amount)).sub(data.left);

                    order.amount = data.left;
                    order.price = data.price;
                    order.timestamp = data.mtime;
                    orders[index] = order;

                    if (isSell) {
                        this.totalSellOrders = this.totalSellOrders.sub(deltaAmount);
                    } else {
                        this.totalBuyOrders = this.totalBuyOrders.sub(
                            new Decimal(data.price).mul(deltaAmount)
                        );
                    }

                    this.ordersUpdated = true;
                    break;
                case WSAPI.order.status.FINISH:
                    if ('undefined' === typeof order) {
                        return;
                    }

                    this.ordersUpdated = true;
                    orders.splice(orders.indexOf(order), 1);

                    if (isSell) {
                        this.totalSellOrders = this.totalSellOrders.sub(order.amount);
                    } else {
                        this.totalBuyOrders = this.totalBuyOrders.sub(
                            new Decimal(order.price).mul(order.amount)
                        );
                    }

                    break;
            }

            this.saveOrders(orders, isSell);
        },
        /**
         * @param {object} market
         * @return {string}
         */
        getTradeUrl: function(market) {
            return this.$routing.generate('token_show_trade', {
                name: market.quote.name,
                crypto: this.rebrandingFunc(market.base.symbol),
            });
        },
        /**
         * @param {object} market
         */
        changeMarket: function(market) {
            this.changingMarket = true;
            this.setCurrentMarketIndex(market.base.symbol);
            this.$root.$emit('market-changed', market);

            window.history.replaceState({}, '', this.getTradeUrl(market));

            this.buyOrders = null;
            this.sellOrders = null;
            this.totalSellOrders = null;
            this.totalBuyOrders = null;
            this.buyDepth = null;
            this.ordersUpdated = false;

            this.updateOrders()
                .then(() => {
                    this.subscribeOnWsUpdate();
                    this.changingMarket = false;
                })
                // Empty catch to prevent "Uncaught (in promise) undefined" error in console
                .catch((err) => {});
        },
        saveOrders: function(orders, isSell) {
            if (isSell) {
                this.sellOrders = orders;
            } else {
                this.buyOrders = orders;
            }
        },
        preventAction(refName) {
            this.preventedActionRefName = refName;
            this.addPhoneModalVisible = true;
        },
        getMarketTabClass(market) {
            return market.base.symbol === this.currentMarket.base.symbol ? 'btn-primary' : 'btn-secondary';
        },
        onPhoneVerified() {
            this.addPhoneModalVisible = false;

            if (this.preventedActionRefName && this.$refs[this.preventedActionRefName]) {
                this.$refs[this.preventedActionRefName].placeOrder();
            }
        },
    },
};
</script>
