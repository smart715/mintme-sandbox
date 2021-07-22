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
                            <price-converter-input
                                class="d-inline-block"
                                :class="orderInputClass"
                                v-model="buyPrice"
                                input-id="buy-price-input"
                                :disabled="useMarketPrice || !loggedIn"
                                @keypress="checkPriceInput"
                                @paste="checkPriceInput"
                                tabindex="3"
                                :from="market.base.symbol"
                                :to="USD.symbol"
                                :subunit="4"
                                symbol="$"
                                :show-converter="currencyMode === currencyModes.usd.value"
                            />
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
                                 <a
                                     v-if="showDepositMoreLink"
                                     :href="depositMoreLink"
                                     class="d-block text-nowrap"
                                     tabindex="1"
                                 >{{ $t('trade.buy_order.deposit_more') }}</a>
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
                            <span :class="{'text-muted': tradeDisabled}">
                                {{ $t('trade.buy_order.submit') }}
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
import {library} from '@fortawesome/fontawesome-svg-core';
import {faCircleNotch} from '@fortawesome/free-solid-svg-icons';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import Decimal from 'decimal.js';
import {VBTooltip} from 'bootstrap-vue';
import {mapMutations, mapGetters} from 'vuex';
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
import {USD, currencyModes} from '../../utils/constants';
import PriceConverterInput from '../PriceConverterInput';

library.add(faCircleNotch);

export default {
    name: 'TradeBuyOrder',
    components: {
        Guide,
        PriceConverterInput,
        FontAwesomeIcon,
    },
    directives: {
        'b-tooltip': VBTooltip,
    },
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
    props: {
        loginUrl: String,
        signupUrl: String,
        loggedIn: Boolean,
        market: Object,
        balance: [String, Boolean],
        balanceLoaded: [String, Boolean],
        takerFee: Number,
        tradeDisabled: Boolean,
        currencyMode: String,
    },
    data() {
        return {
            action: 'buy',
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
                    amountInput: toMoney(this.buyAmount, this.market.quote.subunit),
                    priceInput: toMoney(this.buyPrice, this.market.base.subunit),
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

            if (!this.priceManuallyEdited || !parseFloat(this.buyPrice)) {
                this.buyPrice = this.price;
                this.priceManuallyEdited = false;
            }

            this.fillAmount();
        },
        fillAmount() {
            this.buyAmount = toMoney(
                new Decimal(this.immutableBalance).div(parseFloat(this.buyPrice)|| 1).toString(),
                this.market.quote.subunit
            );
        },
        ...mapMutations('tradeBalance', [
            'setBuyPriceInput',
            'setBuyAmountInput',
            'setBaseBalance',
            'setUseBuyMarketPrice',
            'setTakerFee',
            'setBuyPriceManuallyEdited',
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
            return this.marketPrice <= 0 || !this.loggedIn;
        },
        translationsContext: function() {
            return {
                baseSymbol: this.rebrandingFunc(this.market.base.symbol),
                quoteSymbol: this.rebrandingFunc(this.market.quote.symbol),
                rebrandedQuoteSymbol: this.rebrandingFunc(this.market.quote.symbol),
                minTotalPrice: this.minTotalPrice,
            };
        },
        ...mapGetters('tradeBalance', [
            'getBuyPriceInput',
            'getBuyAmountInput',
            'getBaseBalance',
            'getUseBuyMarketPrice',
            'getBuyPriceManuallyEdited',
        ]),
        ...mapGetters('orders', {
             sellOrders: 'getSellOrders',
        }),
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
                return this.getUseBuyMarketPrice && this.marketPrice > 0;
            },
            set(val) {
                this.setUseBuyMarketPrice(val);
            },
        },
        priceManuallyEdited: {
            get() {
                return this.getBuyPriceManuallyEdited;
            },
            set(val) {
                this.setBuyPriceManuallyEdited(val);
            },
        },
        marketPrice() {
            let tokenAmount = new Decimal(0);
            let balance = new Decimal(this.immutableBalance || 0);

            let result = this.sellOrders[0] ? this.sellOrders[this.sellOrders.length - 1].price : 0;

            for (let order of this.sellOrders) {
                tokenAmount = tokenAmount.add(order.amount);

                if (balance.div(order.price).lessThanOrEqualTo(tokenAmount)) {
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
        balance: function() {
            this.immutableBalance = this.balance;
            if (!this.balance) {
                return;
            }
        },
    },
    mounted: function() {
        this.setTakerFee(this.takerFee);
    },
};
</script>
