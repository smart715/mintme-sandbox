<template>
    <div class="h-100">
        <div class="card h-100">
            <div class="card-header text-left">
                {{ $t('trade.sell_order.header') }}
                <span  class="card-header-icon">
                    <guide>
                        <template slot="header">
                            {{ $t('trade.sell_order.guide_header') }}
                        </template>
                        <template slot="body">
                            {{ $t('trade.sell_order.guide_body', translationsContext) }}
                        </template>
                    </guide>
                </span>
            </div>
            <div class="card-body">
                <div v-if="balanceLoaded" class="row">
                    <div v-if="loggedIn" class="col-12">
                        <div class="form-group">
                            <label
                                for="sell-price-input"
                                class="text-white">
                                {{ $t('trade.sell_order.price_in.header', translationsContext) }}
                            </label>
                            <guide>
                                <template slot="header">
                                  {{ $t('trade.sell_order.price_in.guide_header', translationsContext) }}
                                </template>
                                <template slot="body">
                                    {{ $t('trade.sell_order.price_in.guide_body', translationsContext) }}
                                </template>
                            </guide>
                        </div>
                        <div class="d-flex">
                            <price-converter-input
                                class="d-inline-block"
                                :class="orderInputClass"
                                v-model="sellPrice"
                                input-id="sell-price-input"
                                :disabled="useMarketPrice || !loggedIn"
                                @keypress="checkPriceInput"
                                @paste="checkPriceInput"
                                tabindex="8"
                                :from="market.base.symbol"
                                :to="USD.symbol"
                                :subunit="4"
                                symbol="$"
                                :show-converter="currencyMode === currencyModes.usd.value"
                            />
                            <div v-if="loggedIn && immutableBalance" class="w-50 m-auto pl-4">
                                {{ $t('trade.sell_order.your.header') }}
                                <span class="c-pointer" @click="balanceClicked">
                                    <span v-if="shouldTruncate"
                                        v-b-tooltip="{title: rebrandingFunc(market.quote), boundary:'window', customClass:'tooltip-custom'}">
                                        {{ market.quote | rebranding | truncate(17) }} :
                                    </span>
                                    <span v-else>
                                        {{ market.quote | rebranding }} :
                                    </span>
                                    <span class="text-white">
                                        <span class="text-nowrap p-0">
                                            {{ immutableBalance | toMoney(market.quote.subunit) | formatMoney }}
                                                <guide>
                                                    <template slot="header">
                                                        {{ $t('trade.sell_order.your.guide_header', translationsContext) }}
                                                    </template>
                                                    <template slot="body">
                                                        {{ $t('trade.sell_order.your.guide_body', translationsContext) }}
                                                    </template>
                                                </guide>
                                        </span>
                                        <span class="text-nowrap">
                                            <a
                                                v-if="showDepositMoreLink"
                                                :href="depositMoreLink"
                                                tabindex="6"
                                            >{{ $t('trade.sell_order.deposit_more') }}</a>
                                        </span>
                                    </span>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div v-if="loggedIn" class="col-12 pt-2">
                        <label
                            for="sell-price-amount"
                            class="d-flex flex-row flex-nowrap justify-content-start w-50"
                        >
                            <span class="d-inline-block text-nowrap">{{ $t('trade.sell_order.amount') }}</span>
                            <span v-if="shouldTruncate"
                                  v-b-tooltip="{title:market.quote.symbol, boundary:'window', customClass:'tooltip-custom'}"
                                  class="d-inline-block ml-1"
                            >
                                {{ market.quote | rebranding | truncate(17) }}
                            </span>
                            <span v-else class="d-inline-block ml-1">
                                {{ market.quote | rebranding }}
                            </span>
                            <span class="d-inline-block">:</span>
                        </label>
                        <div class="d-flex">
                            <input
                                v-model="sellAmount"
                                type="text"
                                id="sell-price-amount"
                                class="form-control"
                                :class="orderInputClass"
                                :disabled="!loggedIn"
                                @keypress="checkAmountInput"
                                @paste="checkAmountInput"
                                tabindex="9"
                            >
                            <div v-if="loggedIn" class="w-50 m-auto pl-4">
                                <div
                                    v-if="!disabledMarketPrice"
                                    class="form-group custom-control custom-checkbox pb-0">
                                    <input
                                        v-model.number="useMarketPrice"
                                        step="0.00000001"
                                        type="checkbox"
                                        id="sell-price"
                                        class="custom-control-input"
                                        tabindex="7"
                                    >
                                    <label
                                        class="custom-control-label pb-0"
                                        for="sell-price">
                                        {{ $t('trade.sell_order.market_price.header') }}
                                    </label>
                                    <guide>
                                        <template slot="header">
                                            {{ $t('trade.sell_order.market_price.guide_header') }}
                                        </template>
                                        <template slot="body">
                                            {{ $t('trade.sell_order.market_price.guide_body', {quoteSymbol: market.quote.symbol }) | rebranding }}
                                        </template>
                                    </guide>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-if="loggedIn" class="col-12 pt-2">
                        {{ $t('trade.sell_order.total_price.header') }}
                        {{ totalPrice | toMoney(market.base.subunit) | formatMoney }} {{ market.base.symbol | rebranding }}
                        <guide>
                            <template slot="header">
                                {{ $t('trade.sell_order.total_price.guide_header') }}
                            </template>
                            <template slot="body">
                                {{ $t('trade.buy_order.total_price.guide_body') }}
                            </template>
                        </guide>
                    </div>
                    <div class="col-12 pt-3 text-left">
                        <button
                            v-if="loggedIn"
                            class="btn btn-primary"
                            :disabled="!buttonValid"
                            @click="placeOrder"
                            tabindex="10"
                        >
                            <span :class="{'text-muted': tradeDisabled}">
                                {{ $t('trade.sell_order.submit') }}
                            </span>
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
    FiltersMixin,
    PlaceOrder,
    WebSocketMixin,
    MoneyFilterMixin,
    PricePositionMixin,
    RebrandingFilterMixin,
    OrderMixin,
    LoggerMixin,
} from '../../mixins/';
import {toMoney} from '../../utils';
import Decimal from 'decimal.js';
import {mapMutations, mapGetters} from 'vuex';
import {MINTME, USD, currencyModes} from '../../utils/constants';
import PriceConverterInput from '../PriceConverterInput';

export default {
    name: 'TradeSellOrder',
    components: {
        PriceConverterInput,
        Guide,
    },
    mixins: [
        WebSocketMixin,
        PlaceOrder,
        FiltersMixin,
        MoneyFilterMixin,
        PricePositionMixin,
        RebrandingFilterMixin,
        OrderMixin,
        LoggerMixin,
    ],
    props: {
        loginUrl: String,
        signupUrl: String,
        loggedIn: Boolean,
        market: Object,
        balance: [String, Boolean],
        isOwner: Boolean,
        balanceLoaded: Boolean,
        tradeDisabled: Boolean,
        currencyMode: String,
    },
    data() {
        return {
            action: 'sell',
            placingOrder: false,
            USD,
            currencyModes,
        };
    },
    methods: {
        checkPriceInput() {
            this.$emit('check-input', this.market.base.subunit);
            this.priceManuallyEdited = true;
        },
        checkAmountInput() {
            this.$emit(
                'check-input',
                this.market.quote.decimals > this.market.quote.subunit
                    ? this.market.quote.subunit
                    : this.market.quote.decimals
            );
        },
        placeOrder: function() {
            if (this.tradeDisabled) {
              this.notifyError(this.$t('trade.orders.disabled'));
              return;
            }

            if (this.sellPrice && this.sellAmount) {
                if ((new Decimal(this.sellPrice)).times(this.sellAmount).lessThan(this.minTotalPrice)) {
                    this.showNotification({
                        result: 2,
                        message: this.$t('trade.sell_order.amount_has_to_be', this.translationsContext),
                    });
                    return;
                }

                this.placingOrder = true;
                let data = {
                    amountInput: toMoney(this.sellAmount, this.market.quote.subunit),
                    priceInput: toMoney(this.sellPrice, this.market.base.subunit),
                    marketPrice: this.useMarketPrice,
                    action: this.action,
                };
                this.$axios.single.post(this.$routing.generate('token_place_order', {
                    base: this.market.base.symbol,
                    quote: this.market.quote.symbol,
                }), data)
                    .then(({data}) => {
                        if (
                            data.hasOwnProperty('error') &&
                            data.hasOwnProperty('type')
                        ) {
                            this.$emit('making-order-prevented');
                            return;
                        }
                        if (data.result === 1) {
                            this.resetOrder();
                        }
                        this.showNotification(data);
                        this.placingOrder = false;
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
                this.sellPrice = 0;
            }
            this.sellAmount = 0;
        },
        updateMarketPrice: function() {
            if (this.useMarketPrice) {
                this.sellPrice = this.price || 0;
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

            if (!this.priceManuallyEdited || !parseFloat(this.sellPrice)) {
                this.sellPrice = this.price;
                this.priceManuallyEdited = false;
            }

            this.fillAmount();
        },
        fillAmount() {
            this.sellAmount = toMoney(this.immutableBalance, this.market.quote.subunit);
        },
        ...mapMutations('tradeBalance', [
            'setSellPriceInput',
            'setSellAmountInput',
            'setUseSellMarketPrice',
            'setSellPriceManuallyEdited',
        ]),
    },
    computed: {
        tokenSymbol: function() {
            return this.rebrandingFunc(this.market.quote) === MINTME.symbol ? MINTME.symbol : 'Token';
        },
        shouldTruncate: function() {
            return this.market.quote.symbol.length > 17;
        },
        totalPrice: function() {
            return new Decimal(this.sellPrice && !isNaN(this.sellPrice) ? this.sellPrice : 0)
                .times(this.sellAmount && !isNaN(this.sellAmount) ? this.sellAmount : 0)
                .toString();
        },
        price: function() {
            return toMoney(this.marketPrice, this.market.base.subunit) || null;
        },
        minTotalPrice: function() {
            return toMoney('1e-' + this.market.base.subunit, this.market.base.subunit);
        },
        fieldsValid: function() {
            return this.sellPrice > 0 && this.sellAmount > 0;
        },
        buttonValid: function() {
            return this.fieldsValid && !this.placingOrder;
        },
        disabledMarketPrice: function() {
            return this.marketPrice <= 0 || !this.loggedIn;
        },
        translationsContext: function() {
            return {
                baseSymbol: this.rebrandingFunc(this.market.base.symbol),
                quoteSymbol: this.rebrandingFunc(this.market.quote),
                minTotalPrice: this.minTotalPrice,
                tokenSymbol: this.tokenSymbol,
            };
        },
        ...mapGetters('tradeBalance', [
            'getSellPriceInput',
            'getSellAmountInput',
            'getQuoteBalance',
            'getUseSellMarketPrice',
            'getSellPriceManuallyEdited',
        ]),
        ...mapGetters('orders', {
            buyOrders: 'getBuyOrders',
        }),
        sellPrice: {
            get() {
                return this.getSellPriceInput;
            },
            set(val) {
                this.setSellPriceInput(val);
            },
        },
        sellAmount: {
            get() {
                return this.getSellAmountInput;
            },
            set(val) {
                this.setSellAmountInput(val);
            },
        },
        immutableBalance: {
            get() {
                return this.getQuoteBalance;
            },
        },
        useMarketPrice: {
            get() {
                return this.getUseSellMarketPrice;
            },
            set(val) {
                this.setUseSellMarketPrice(val);
            },
        },
        priceManuallyEdited: {
            get() {
                return this.getSellPriceManuallyEdited;
            },
            set(val) {
                this.setSellPriceManuallyEdited(val);
            },
        },
        marketPrice() {
            let tokenAmount = new Decimal(0);
            let balance = new Decimal(this.immutableBalance).toDecimalPlaces(this.market.quote.subunit, Decimal.ROUND_DOWN);

            let result = this.buyOrders[0] ? this.buyOrders[this.buyOrders.length - 1].price : 0;

            for (let order of this.buyOrders) {
                tokenAmount = tokenAmount.add(order.amount);

                if (balance.lessThanOrEqualTo(tokenAmount)) {
                    result = order.price;

                    break;
                }
            }

            return result;
        },
    },
    watch: {
        useMarketPrice: function(newVal) {
            this.updateMarketPrice();

            if (!newVal) {
                this.resetOrder();
            }
        },
        marketPrice: function() {
            this.updateMarketPrice();
        },
    },
    filters: {
        toMoney: function(val, precision) {
            return toMoney(val, precision);
        },
    },
};
</script>
