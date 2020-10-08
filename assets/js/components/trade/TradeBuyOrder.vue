<template>
    <div  class="h-100">
        <div class="card h-100">
            <div class="card-header text-left">
                {{ $t('trade.buy_order.header') }}
                <span class="card-header-icon">
                    <guide>
                        <template slot="header">
                            {{ $t('trade.buy_order.guide_header') }}
                        </template>
                        <template slot="body">
                            <span v-html="this.$t('trade.buy_order.guide_body', translationsContext)"></span>
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
                                {{ $t('trade.buy_order.price_in.header', translationsContext) }}
                            </label>
                            <guide>
                                <template slot="header">
                                  {{ $t('trade.buy_order.price_in.guide_header', translationsContext) }}
                                </template>
                                <template slot="body">
                                  {{ $t('trade.buy_order.price_in.guide_body', translationsContext) }}
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
                                {{ $t('trade.buy_order.your.header') }}
                                <span>
                                    <span class="c-pointer" @click="balanceClicked">{{ market.base.symbol | rebranding }}:
                                        <span class="text-white">
                                            <span class="text-nowrap">
                                                {{ immutableBalance | toMoney(market.base.subunit) | formatMoney }}
                                            </span>
                                        </span>
                                    </span>
                                    <guide>
                                        <template slot="header">
                                            {{ $t('trade.buy_order.your.guide_header', translationsContext) }}
                                        </template>
                                        <template slot="body">
                                            {{ $t('trade.buy_order.your.guide_body', translationsContext) }}
                                        </template>
                                    </guide>
                                </span>
                                <p class="text-nowrap">
                                    <a
                                        v-if="showDepositMoreLink"
                                        :href="depositMoreLink"
                                        tabindex="1"
                                    >{{ $t('trade.buy_order.deposit_more') }}</a>
                                </p>
                            </div>
                        </div>
                    </div>
                    <div v-if="loggedIn" class="col-12 pt-2">
                        <label
                            for="buy-price-amount"
                            class="d-flex flex-row flex-nowrap justify-content-start w-50"
                        >
                            <span class="d-inline-block text-nowrap">{{ $t('trade.buy_order.amount') }} </span>
                            <span v-if="shouldTruncate"
                                  v-b-tooltip="{title:market.quote.symbol, boundary: 'window', customClass: 'tooltip-custom'}"
                                  class="d-inline-block ml-1">
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
                                <div
                                    v-if="!disabledMarketPrice"
                                    class="form-group custom-control custom-checkbox pb-0">
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
                                        {{ $t('trade.buy_order.market_price.header') }}
                                    </label>
                                    <guide>
                                        <template slot="header">
                                            {{ $t('trade.buy_order.market_price.guide_header') }}
                                        </template>
                                        <template slot="body">
                                            {{ $t('trade.buy_order.market_price.guide_body', translationsContext) }}
                                        </template>
                                    </guide>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-if="loggedIn" class="col-12 pt-2">
                      {{ $t('trade.buy_order.total_price.header') }}
                        {{ totalPrice | toMoney(market.base.subunit) | formatMoney }} {{ market.base.symbol | rebranding }}
                        <guide>
                            <template slot="header">
                                {{ $t('trade.buy_order.total_price.guide_header') }}
                            </template>
                            <template slot="body">
                                {{ $t('trade.buy_order.total_price.guide_body') }}
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
                          {{ $t('trade.buy_order.submit') }}
                        </button>
                        <template v-else>
                            <a :href="loginUrl" class="btn btn-primary">{{ $t('log_in') }}</a>
                            <span class="px-2">{{ $t('or') }}</span>
                            <a :href="signupUrl">{{ $t('sign_up') }}</a>
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
        takerFee: Number,
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
                    this.showNotification({
                        result: 2,
                        message: this.$t('trade.buy_order.amount_has_to_be', this.translationsContext),
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
            'setTakerFee',
        ]),
    },
    computed: {
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
        translationsContext: function() {
            return {
                baseSymbol: this.rebrandingFunc(this.market.base.symbol),
                quoteSymbol: this.market.quote.symbol,
                rebrandedQuoteSymbol: this.rebrandingFunc(this.market.quote.symbol),
                minTotalPrice: this.minTotalPrice,
            };
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
                return new Decimal(this.getBuyAmountInput).toDP(this.market.quote.subunit, Decimal.ROUND_CEIL).toNumber();
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
                return this.getUseBuyMarketPrice && this.marketPrice > 0;
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
        this.setTakerFee(this.takerFee);
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
