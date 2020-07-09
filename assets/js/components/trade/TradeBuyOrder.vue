<template>
    <div  class="h-100">
        <div class="card h-100">
            <div class="card-header text-left">
                Buy Order
                <span class="card-header-icon">
                    <guide>
                        <template slot="header">
                            Buy Order
                        </template>
                        <template slot="body">
                            Form used to create  an order so you can
                            buy {{ market.base.symbol | rebranding }} or make offer.
                        </template>
                    </guide>
                </span>
            </div>
            <div class="card-body">
                <div v-if="balanceLoaded" class="row">
                    <div v-if="loggedIn" class="col-12">
                        <div class="form-group">
                            <label
                                for="buy-price-input"
                                class="text-white">
                                Price in {{ market.base.symbol | rebranding }}:
                            </label>
                            <guide>
                                <template slot="header">
                                    Price in {{ market.base.symbol | rebranding }}
                                </template>
                                <template slot="body">
                                    The price at which you want to buy one {{ market.quote | rebranding }}.
                                </template>
                            </guide>
                        </div>    
                        <div class="d-flex">
                            <input
                                v-model="buyPrice"
                                type="text"
                                id="buy-price-input"
                                class="form-control"
                                :class="orderInputClass"
                                :disabled="useMarketPrice || !loggedIn"
                                @keypress="checkPriceInput"
                                @paste="checkPriceInput"
                                tabindex="3"
                            >
                            <div v-if="loggedIn && immutableBalance" class="w-50 m-auto pl-4">
                                Your
                                <span class="c-pointer" @click="balanceClicked">{{ market.base.symbol | rebranding }}:
                                    <span class="text-white">
                                        <span class="text-nowrap">
                                            {{ immutableBalance | toMoney(market.base.subunit) | formatMoney }}
                                                <guide>
                                                    <template slot="header">
                                                        Your {{ tokenSymbol }}
                                                    </template>
                                                    <template slot="body">
                                                        Your {{ market.base.symbol | rebranding }} balance.
                                                    </template>
                                                </guide>
                                        </span>
                                        <span class="text-nowrap">
                                            <a
                                                v-if="showDepositMoreLink"
                                                :href="depositMoreLink"
                                                tabindex="1"
                                            >Deposit more</a>
                                        </span>
                                    </span>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div v-if="loggedIn" class="col-12 pt-2">
                        <label
                            for="buy-price-amount"
                            class="d-flex flex-row flex-nowrap justify-content-start w-50"
                        >
                            <span class="d-inline-block text-nowrap">Amount in </span>
                            <span v-if="shouldTruncate" v-b-tooltip:title="market.quote.symbol" class="d-inline-block ml-1">
                                {{ market.quote | rebranding | truncate(17) }}
                            </span>
                            <span v-else class="d-inline-block ml-1">
                                {{ market.quote | rebranding }}
                            </span>
                            <span class="d-inline-block">:</span>
                        </label>
                        <div class="d-flex">
                            <input
                                v-model="buyAmount"
                                type="text"
                                id="buy-price-amount"
                                class="form-control"
                                :class="orderInputClass"
                                :disabled="!loggedIn"
                                @keypress="checkAmountInput"
                                @paste="checkAmountInput"
                                tabindex="4"
                            >
                            <div v-if="loggedIn" class="w-50 m-auto pl-4">
                                <label
                                    v-if="!disabledMarketPrice"
                                    class="custom-control custom-checkbox pb-0">
                                    <input
                                        v-model="useMarketPrice"
                                        step="0.00000001"
                                        type="checkbox"
                                        id="buy-price"
                                        class="custom-control-input"
                                        tabindex="2"
                                    >
                                    <label
                                        class="custom-control-label pb-0"
                                        for="buy-price">
                                        Market Price
                                        <guide>
                                            <template slot="header">
                                                Market Price
                                            </template>
                                            <template slot="body">
                                                Checking this box fetches current best market price
                                                for which you can buy {{ market.quote.symbol | rebranding }}.
                                            </template>
                                        </guide>
                                    </label>
                                </label>
                            </div>
                        </div>
                    </div>
                    <div v-if="loggedIn" class="col-12 pt-2">
                        Total Price:
                        {{ totalPrice | toMoney(market.base.subunit) | formatMoney }} {{ market.base.symbol | rebranding }}
                        <guide>
                            <template slot="header">
                                Total Price
                            </template>
                            <template slot="body">
                                Total amount to pay, including exchange fee.
                            </template>
                        </guide>
                    </div>
                    <div class="col-12 pt-3 text-left">
                        <button
                            @click="placeOrder"
                            v-if="loggedIn"
                            class="btn btn-primary"
                            :disabled="!buttonValid"
                            tabindex="5"
                        >
                            Create buy order
                        </button>
                        <template v-else>
                            <a :href="loginUrl" class="btn btn-primary">Log In</a>
                            <span class="px-2">or</span>
                            <a :href="signupUrl">Sign Up</a>
                        </template>
                    </div>
                </div>
                <template v-else>
                    <div class="p-5 text-center text-white">
                        <font-awesome-icon icon="circle-notch" spin class="loading-spinner" fixed-width />
                    </div>
                </template>
            </div>
        </div>
    </div>
</template>

<script>
import Guide from '../Guide';
import {
    WebSocketMixin,
    PlaceOrder,
    MoneyFilterMixin,
    PricePositionMixin,
    RebrandingFilterMixin,
    OrderMixin,
    LoggerMixin,
    FiltersMixin,
} from '../../mixins/';
import {toMoney} from '../../utils';
import Decimal from 'decimal.js';
import {mapMutations, mapGetters} from 'vuex';
import {BTC, MINTME} from '../../utils/constants';

export default {
    name: 'TradeBuyOrder',
    mixins: [
        WebSocketMixin,
        PlaceOrder,
        MoneyFilterMixin,
        PricePositionMixin,
        RebrandingFilterMixin,
        OrderMixin,
        LoggerMixin,
        FiltersMixin,
    ],
    components: {
        Guide,
    },
    props: {
        loginUrl: String,
        signupUrl: String,
        loggedIn: Boolean,
        market: Object,
        marketPrice: [Number, String],
        balance: [String, Boolean],
        balanceLoaded: [String, Boolean],
    },
    data() {
        return {
            action: 'buy',
            placingOrder: false,
            balanceManuallyEdited: false,
        };
    },
    methods: {
        setBalanceManuallyEdited: function(val = true) {
            this.balanceManuallyEdited = val;
        },
        checkPriceInput() {
            this.$emit('check-input', this.market.base.subunit);
            this.setBalanceManuallyEdited(true);
        },
        checkAmountInput() {
            this.$emit('check-input', this.market.quote.subunit);
        },
        placeOrder: function() {
            if (this.buyPrice && this.buyAmount) {
                if ((new Decimal(this.buyPrice)).times(this.buyAmount).lessThan(this.minTotalPrice)) {
                    let symbol = this.rebrandingFunc(this.market.base.symbol);
                    this.showNotification({
                        result: 2,
                        message: `Total amount has to be at least ${this.minTotalPrice} ${symbol}`,
                    });
                    return;
                }

                this.placingOrder = true;
                let data = {
                    'amountInput': toMoney(this.buyAmount, this.market.quote.subunit),
                    'priceInput': toMoney(this.buyPrice, this.market.base.subunit),
                    'marketPrice': this.useMarketPrice,
                    'action': this.action,
                };

                this.$axios.single.post(this.$routing.generate('token_place_order', {
                    base: this.market.base.symbol,
                    quote: this.market.quote.symbol,
                }), data)
                    .then(({data}) => {
                        if (data.result === 1) {
                            this.resetOrder();
                        }
                        this.showNotification(data);
                    })
                    .catch((error) => {
                        this.handleOrderError(error);
                        this.sendLogs('error', 'Can not get place order', error);
                    })
                    .then(() => this.placingOrder = false);
            }
        },
        resetOrder: function() {
            if (!this.useMarketPrice) {
                this.buyPrice = 0;
            }
            this.buyAmount = 0;
        },
        updateMarketPrice: function() {
            if (this.useMarketPrice) {
                this.buyPrice = this.price || 0;
            } else {
                this.buyPrice = 0;
            }

            if (this.disabledMarketPrice) {
                this.useMarketPrice = false;
            }
        },
        balanceClicked: function(event) {
            // Skip "Deposit more" link
            if ('a' === event.target.tagName.toLowerCase()) {
                return;
            }

            if (!this.balanceManuallyEdited || !parseFloat(this.buyPrice)) {
                this.buyPrice = toMoney(this.price || 0, this.market.base.subunit);
                this.setBalanceManuallyEdited(false);
            }

            this.buyAmount = toMoney(
                new Decimal(this.immutableBalance).div(parseFloat(this.buyPrice)|| 1).toString(),
                this.market.quote.subunit
            );
        },
        ...mapMutations('makeOrder', [
            'setBuyPriceInput',
            'setBuyAmountInput',
            'setBaseBalance',
            'setUseBuyMarketPrice',
        ]),
    },
    computed: {
        tokenSymbol: function() {
            return this.rebrandingFunc(this.market.base.symbol) === MINTME.symbol ? MINTME.symbol : BTC.symbol;
        },
        shouldTruncate: function() {
            return this.market.quote.symbol.length > 17;
        },
        totalPrice: function() {
            return new Decimal(this.buyPrice && !isNaN(this.buyPrice) ? this.buyPrice : 0)
                .times(this.buyAmount && !isNaN(this.buyAmount) ? this.buyAmount : 0)
                .toString();
        },
        price: function() {
            return toMoney(this.marketPrice, this.market.base.subunit) || null;
        },
        minTotalPrice: function() {
            return toMoney('1e-' + this.market.base.subunit, this.market.base.subunit);
        },
        fieldsValid: function() {
            return this.buyPrice > 0 && this.buyAmount > 0;
        },
        buttonValid: function() {
            return this.fieldsValid && !this.placingOrder;
        },
        disabledMarketPrice: function() {
            return !this.marketPrice > 0 || !this.loggedIn;
        },
        ...mapGetters('makeOrder', [
            'getBuyPriceInput',
            'getBuyAmountInput',
            'getBaseBalance',
            'getUseBuyMarketPrice',
        ]),
        buyPrice: {
            get() {
                return this.getBuyPriceInput;
            },
            set(val) {
                this.setBuyPriceInput(val);
            },
        },
        buyAmount: {
            get() {
                return this.getBuyAmountInput;
            },
            set(val) {
                this.setBuyAmountInput(val);
            },
        },
        immutableBalance: {
            get() {
                return this.getBaseBalance;
            },
            set(val) {
                this.setBaseBalance(val);
            },
        },
        useMarketPrice: {
            get() {
                return this.getUseBuyMarketPrice;
            },
            set(val) {
                this.setUseBuyMarketPrice(val);
            },
        },
    },
    watch: {
        useMarketPrice: function() {
            this.updateMarketPrice();
        },
        marketPrice: function() {
            this.updateMarketPrice();
        },
        balance: function() {
            this.immutableBalance = this.balance;
            if (!this.balance) {
                return;
            }
        },
    },
    mounted: function() {
        this.addMessageHandler((response) => {
            if (
                'asset.update' === response.method &&
                response.params[0].hasOwnProperty(this.market.base.identifier)
            ) {
                this.immutableBalance = response.params[0][this.market.base.identifier].available;
            }
        }, 'trade-buy-order-asset');
    },
};
</script>
