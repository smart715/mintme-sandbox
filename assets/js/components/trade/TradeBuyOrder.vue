<template>
    <div class="card pt-2 h-100">
        <div class="card-header d-flex align-items-center justify-content-between">
            <div
                class="font-size-3 font-weight-semibold header-highlighting"
                v-html="$t('trade.buy_order.header')"
            ></div>
            <span class="card-header-icon font-size-3">
                <guide :key="tooltipKey">
                    <template slot="header">
                        {{ $t('trade.buy_order.guide_header') }}
                    </template>
                    <template slot="body">
                        <span v-html="this.$t('trade.buy_order.guide_body', translationsContext)"></span>
                    </template>
                </guide>
            </span>
        </div>
        <div class="card-body px-2">
            <div v-if="serviceUnavailable" class="p-5 text-center text-white">
                {{ this.$t('toasted.error.service_unavailable_short') }}
            </div>
            <div v-else-if="balanceLoaded">
                <div v-if="loggedIn">
                    <div v-if="immutableBalance" class="font-weight-semibold font-size-1">
                        <div class="d-flex flex-lg-nowrap flex-wrap justify-content-between pt-2">
                            <div class="w-75 pl-3">
                                <div class="d-flex align-items-center">
                                    {{ $t('trade.buy_order.your.header') }}
                                    <span class="c-pointer d-flex align-items-center" @click="balanceClicked">
                                        <coin-avatar
                                            :symbol="market.base.symbol"
                                            :is-crypto="true"
                                            class="mr-1"
                                        />
                                        {{ market.base.symbol | rebranding }}:
                                        <span class="ml-1 text-nowrap text-primary d-flex align-items-center">
                                            {{ immutableBalance | toMoney(market.base.subunit) | formatMoney }}
                                            <guide
                                                class="font-size-2 mtn-3 ml-1"
                                                :key="tooltipKey"
                                            >
                                                <template slot="header">
                                                    {{ $t('trade.buy_order.your.guide_header') }}
                                                    <coin-avatar
                                                        :symbol="market.base.symbol"
                                                        :is-crypto="true"
                                                    />
                                                    {{ market.base.symbol | rebranding }}:
                                                </template>
                                                <template slot="body">
                                                    <span v-html="tradeBuyOrderYourGuideBody" />
                                                </template>
                                            </guide>
                                        </span>
                                    </span>
                                </div>
                            </div>
                            <div class="pl-3 pr-md-3">
                                <div v-if="showDepositMoreLink" class="pt-2 pt-md-0 text-nowrap">
                                    <span
                                        :class="getTradeDepositDisabledClasses(getCurrencySymbol)"
                                        tabindex="1"
                                        @click="openDepositModal(getCurrencySymbol)"
                                    >
                                        {{ $t('trade.buy_order.add_more_funds') }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-group ml-3 mt-3">
                        <label
                            for="buy-price-input"
                            class="text-uppercase font-weight-bold"
                        >
                            {{ $t('trade.buy_order.price_in.header') }}
                        </label>
                        <guide
                            :key="tooltipKey"
                            class="font-size-1"
                        >
                            <template slot="header">
                            {{ $t('trade.buy_order.price_in.guide_header', translationsContext) }}
                            </template>
                            <template slot="body">
                                <span v-html="$t('trade.buy_order.price_in.guide_body', translationsContext)"></span>
                            </template>
                        </guide>
                    </div>
                    <div class="row align-items-center m-0">
                        <div class="col-12">
                            <div class="d-flex flex-1 pt-2">
                                <div class="w-100 flex-nowrap">
                                    <price-converter-input
                                        v-model="buyPrice"
                                        input-id="buy-price-input"
                                        :disabled="disabledPriceConverterInput"
                                        tabindex="2"
                                        :from="market.base.symbol"
                                        :to="USD.symbol"
                                        :subunit="priceSubunits"
                                        symbol="$"
                                        :input-class="{'trade-order-input form-control white-input py-3 h-auto' : true}"
                                        :overflow-class="{'trade-order-input--overflow' : true}"
                                        @keyup.enter="keyupEnterInput"
                                        @keypress="checkPriceInput"
                                        @keyup="keyupPriceInput($event)"
                                        @paste="checkPriceInput"
                                    />
                                </div>
                                <div class="price-order d-flex justify-content-center align-items-center">
                                    <div class="d-flex align-items-center">
                                        <coin-avatar
                                            :symbol="market.base.symbol"
                                            :is-crypto="true"
                                            class="mr-2"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div v-if="loggedIn" class="my-3">
                    <label
                        for="buy-price-amount"
                        class="font-weight-semibold ml-3 mb-1 text-uppercase"
                    >
                        <span class="d-inline-block text-nowrap">
                            {{ $t('trade.buy_order.amount') }}
                        </span>
                    </label>
                    <div class="row m-0 align-items-center">
                        <div class="col-12">
                            <div class="d-flex flex-1 pt-2">
                                <div class="w-100 flex-nowrap">
                                    <input
                                        id="buy-price-amount"
                                        type="text"
                                        v-model="buyAmount"
                                        class="trade-order-input form-control white-input py-3 h-auto"
                                        :disabled="!loggedIn || tradeDisabled"
                                        tabindex="3"
                                        :placeholder="0"
                                        @keyup.enter="keyupEnterInput"
                                        @keypress="checkAmountInput"
                                        @keyup="keyupAmountInput($event)"
                                        @paste="checkAmountInput"
                                    >
                                </div>
                                <div class="price-order d-flex justify-content-center align-items-center">
                                    <div class="d-flex align-items-center">
                                        <coin-avatar
                                            :image="getCoinAvatar(this.market.quote.symbol)"
                                            :symbol="this.market.quote.symbol"
                                            :is-crypto="!getCoinAvatar(this.market.quote.symbol)"
                                            :is-user-token="!!getCoinAvatar(this.market.quote.symbol)"
                                            class="mr-2"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-show="maxAmountWarning" class="col-12 pt-2 pl-3">
                            <span class="max-amount-warning">
                                {{ maxAmountWarning }}
                            </span>
                        </div>
                    </div>
                </div>
                <div v-if="loggedIn" class="col-12 container-slider">
                    <m-slider
                        :value="sliderAmount"
                        :max-value="maxSliderAmount"
                        :disabled="disabledSlider"
                        :precision="market.base.subunit"
                        tabindex="4"
                        @change="sliderNewAmount"
                    />
                </div>
                <div v-if="loggedIn" class="col-12 pt-3 mb-1">
                    <div class="text-uppercase font-weight-semibold mb-1">
                        {{ $t('trade.buy_order.total_price.header') }}
                        <guide class="font-size-2 mtn-6 ml-1">
                            <template slot="header">
                                {{ $t('trade.buy_order.total_price.guide_header') }}
                            </template>
                            <template slot="body">
                                {{ $t('trade.buy_order.total_price.guide_body') }}
                            </template>
                        </guide>
                    </div>
                    <div class="col-12 p-0">
                        <div class="d-flex flex-1 pt-2">
                            <div class="w-100 flex-nowrap">
                                <price-converter-input
                                    v-model="totalPrice"
                                    input-id="buy-total-price-input"
                                    :disabled="disabledPriceConverterInput"
                                    tabindex="5"
                                    :from="market.base.symbol"
                                    :to="USD.symbol"
                                    :subunit="market.base.subunit"
                                    symbol="$"
                                    :input-class="{'trade-order-input white-input' : true}"
                                    :overflow-class="{'trade-order-input--overflow' : true}"
                                    @keyup="keyupTotalPriceInput($event)"
                                    @keypress="checkPriceInput"
                                    @paste="checkPriceInput"
                                />
                            </div>
                            <div class="price-order d-flex justify-content-center align-items-center">
                                <div class="d-flex align-items-center">
                                    <coin-avatar
                                        :symbol="market.base.symbol"
                                        :is-crypto="true"
                                        class="mr-2"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row m-0 pt-4 text-left">
                    <div class="col-12">
                        <m-button
                            v-if="loggedIn"
                            class="btn btn-primary py-3 w-100 text-uppercase"
                            :disabled="!buttonValid"
                            :loading="placingOrder"
                            tabindex="6"
                            @click="placeOrder"
                        >
                            <span :class="{'text-muted': tradeDisabled}">
                                {{ $t('trade.buy_order.submit') }}
                            </span>
                        </m-button>
                        <template v-else>
                            <button
                                id="buy-login-url"
                                class="btn btn-primary"
                                @click.prevent="goToPage(loginUrl)"
                            >
                                {{ $t('log_in') }}
                            </button>
                            <span class="px-2">{{ $t('or') }}</span>
                            <button
                                id="buy-signup-url"
                                class="btn btn-link seo-link"
                                @click.prevent="goToPage(signupUrl)"
                            >
                                {{ $t('sign_up') }}
                            </button>
                        </template>
                    </div>
                </div>
            </div>
            <div v-else class="p-5 text-center text-white">
                <font-awesome-icon icon="circle-notch" spin class="loading-spinner" fixed-width />
            </div>
        </div>
        <deposit-modal
            :visible="showDepositModal"
            :currency="getCurrencySymbol"
            :is-token="isTokenModal"
            :is-created-on-mintme-site="isCreatedOnMintmeSite"
            :is-owner="isOwner"
            :token-networks="currentTokenNetworks"
            :crypto-networks="currentCryptoNetworks"
            :subunit="currentSubunit"
            :no-close="false"
            :add-phone-alert-visible="addPhoneAlertVisible"
            :deposit-add-phone-modal-visible="depositAddPhoneModalVisible"
            @close-confirm-modal="closeConfirmModal"
            @phone-alert-confirm="onPhoneAlertConfirm(getCurrencySymbol)"
            @close-add-phone-modal="closeAddPhoneModal"
            @deposit-phone-verified="onDepositPhoneVerified"
            @close="closeDepositModal"
        />
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
import MSlider from '../UI/Slider';
import {toMoney, generateCoinAvatarHtml} from '../../utils';
import PriceConverterInput from '../PriceConverterInput';
import DepositModal from '../modal/DepositModal';
import {MButton} from '../UI';
import CoinAvatar from '../CoinAvatar';
import {
    WebSocketMixin,
    PlaceOrder,
    MoneyFilterMixin,
    PricePositionMixin,
    RebrandingFilterMixin,
    OrderMixin,
    FiltersMixin,
    OpenPageMixin,
    FloatInputMixin,
    DepositModalMixin,
    TradeCheckInput,
} from '../../mixins/';
import {
    USD,
    webSymbol,
    SLIDER_DEFAULT_MAX_AMOUNT,
    TRADE_ORDER_INPUT_FLAGS,
} from '../../utils/constants';

library.add(faCircleNotch);

const FLAGS_TRADE_BUY = {
    price: TRADE_ORDER_INPUT_FLAGS.buyPrice,
    amount: TRADE_ORDER_INPUT_FLAGS.buyAmount,
    totalPrice: TRADE_ORDER_INPUT_FLAGS.buyTotalPrice,
};

export default {
    name: 'TradeBuyOrder',
    components: {
        Guide,
        PriceConverterInput,
        FontAwesomeIcon,
        DepositModal,
        MButton,
        MSlider,
        CoinAvatar,
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
        FiltersMixin,
        OpenPageMixin,
        FloatInputMixin,
        DepositModalMixin,
        TradeCheckInput,
    ],
    props: {
        loginUrl: String,
        signupUrl: String,
        loggedIn: Boolean,
        market: Object,
        balance: [String, Boolean],
        balanceLoaded: [String, Boolean],
        serviceUnavailable: Boolean,
        takerFee: Number,
        tradeDisabled: Boolean,
        currencyMode: String,
        isOwner: Boolean,
        isCreatedOnMintmeSite: Boolean,
        changingMarket: Boolean,
    },
    data() {
        return {
            action: 'buy',
            placingOrder: false,
            USD,
            tooltipKey: 0,
            setHighestPrice: true,
        };
    },
    methods: {
        ...mapMutations('tradeBalance', [
            'setBuyPriceInput',
            'setBuyAmountInput',
            'setBuyTotalPriceInput',
            'setBaseBalance',
            'setQuoteFullBalance',
            'setUseBuyMarketPrice',
            'setTakerFee',
            'setBuyPriceManuallyEdited',
        ]),
        keyupEnterInput() {
            if (this.buttonValid) {
                this.placeOrder();
            }
        },
        checkPriceInput() {
            this.$emit('check-input', this.priceSubunits);
        },
        checkAmountInput() {
            this.$emit(
                'check-input',
                this.market.quote.decimals > this.market.quote.subunit
                    ? this.market.quote.subunit
                    : this.market.quote.decimals
            );
        },
        keyupPriceInput(event) {
            this.priceManuallyEdited = true;
            const price = event.target.value;

            this.syncInputPriceFlag(
                price,
                this.buyAmount,
                FLAGS_TRADE_BUY
            );

            this.setInputValuesByFlags();
        },
        keyupAmountInput(event) {
            const amount = event.target.value;

            this.syncInputAmountFlag(
                this.buyPrice,
                amount,
                FLAGS_TRADE_BUY
            );

            this.setInputValuesByFlags();
        },
        keyupTotalPriceInput(event) {
            const totalPrice = event.target.value;

            this.syncInputTotalPriceFlag(
                this.buyPrice,
                this.buyAmount,
                totalPrice,
                FLAGS_TRADE_BUY
            );

            this.setInputValuesByFlags();
        },
        placeOrder: function() {
            if (this.serviceUnavailable) {
                this.notifyError(this.$t('toasted.error.service_unavailable'));
                return;
            }
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
                const data = {
                    amountInput: toMoney(this.buyAmount, this.market.quote.subunit),
                    priceInput: toMoney(this.buyPrice, this.priceSubunits),
                    marketPrice: this.useMarketPrice,
                    action: this.action,
                };

                this.$axios.single.post(this.$routing.generate('token_place_order', {
                    base: this.market.base.symbol,
                    quote: this.market.quote.symbol,
                }), data)
                    .then(({data}) => {
                        if (
                            data.hasOwnProperty('message') &&
                            data.hasOwnProperty('notified') &&
                            'token.not_deployed_response' === data.message
                        ) {
                            this.showTokenNotDeployedNotification(this.market.quote.symbol, data.notified);

                            return;
                        }

                        if (
                            data.hasOwnProperty('error') &&
                            data.hasOwnProperty('type')
                        ) {
                            this.$emit('making-order-prevented');
                            return;
                        }
                        if (1 === data.result) {
                            this.resetOrder();
                        }
                        this.setQuoteFullBalance(data.balance ?? 0);
                        this.showNotification(data);
                    })
                    .catch((error) => {
                        this.handleOrderError(error);
                        this.$logger.error('Can not get place order', error);
                    })
                    .then(() => this.placingOrder = false);
            }
        },
        resetOrder: function() {
            if (!this.useMarketPrice) {
                this.buyPrice = '';
            }
            this.buyAmount = '';
            this.totalPrice = '';
        },
        updateMarketPrice: function() {
            if (this.useMarketPrice) {
                this.buyPrice = this.price || '';
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
                new Decimal(this.immutableBalance || 0).div(parseFloat(this.buyPrice)|| 1).toString(),
                this.market.quote.subunit
            );

            if (this.isZero(this.buyPrice)) {
                this.setDefaultBuyPrice();
            }

            this.syncInputAmountFlag(
                this.buyPrice,
                this.buyAmount,
                FLAGS_TRADE_BUY
            );

            this.setInputValuesByFlags();
        },
        getCoinAvatar(symbol) {
            return (this.market.quote.symbol === symbol && webSymbol !== symbol)
                ? this.market.quote.image
                : null;
        },
        setDefaultBuyPrice() {
            this.setInputFlag(FLAGS_TRADE_BUY.price);

            if (this.setHighestPrice && this.highestPrice) {
                this.buyPrice = this.parseFloatInput(
                    new Decimal(this.highestPrice).plus(this.minTotalPrice).toFixed()
                );
                this.setHighestPrice = false;

                return;
            }

            this.buyPrice = this.marketPrice
                ? this.parseFloatInput(this.marketPrice)
                : '';
            this.setHighestPrice = false;
        },
        sliderNewAmount(sliderAmount) {
            this.sliderAmount = sliderAmount;
        },
        setInputValuesByFlags() {
            if (
                this.hasInputFlag(FLAGS_TRADE_BUY.amount) &&
                this.hasInputFlag(FLAGS_TRADE_BUY.totalPrice)
            ) {
                this.buyPrice = this.getNewPrice(
                    this.totalPrice,
                    this.buyAmount,
                    this.priceSubunits
                );
            }

            if (
                this.hasInputFlag(FLAGS_TRADE_BUY.price) &&
                this.hasInputFlag(FLAGS_TRADE_BUY.totalPrice)
            ) {
                this.buyAmount = this.getNewAmount(
                    this.totalPrice,
                    this.buyPrice,
                    this.market.quote.subunit
                );
            }

            if (
                this.hasInputFlag(FLAGS_TRADE_BUY.price) &&
                this.hasInputFlag(FLAGS_TRADE_BUY.amount)
            ) {
                this.totalPrice = this.getTotalPrice(
                    this.buyPrice,
                    this.buyAmount,
                    this.priceSubunits
                );
            }
        },
        checkBuyMaxAmount(price, amount, found) {
            const maxAmount = new Decimal(found).div(price).toNumber();

            return this.getMessageMaxAmount(
                amount,
                maxAmount,
                this.market.quote.subunit
            );
        },
        resetInputAmount() {
            this.buyAmount = '';
        },
        getMessageMaxAmount(value, maxAmount) {
            if (new Decimal(value).gt(maxAmount)) {
                const translationsContext = {
                    maxAmount: maxAmount,
                    currency: this.rebrandingFunc(this.market.quote.symbol),
                };

                return this.$t('max.amount.warning', translationsContext);
            }

            return false;
        },
        resetInputValues() {
            this.buyPrice = '';
            this.buyAmount = '';
            this.totalPrice = '';
        },
    },
    computed: {
        highestPrice() {
            return this.buyOrders[0]
                ? this.buyOrders[0].price
                : 0;
        },
        priceSubunits() {
            return this.isToken && this.market.quote.priceDecimals
                ? this.market.quote.priceDecimals
                : this.market.base.subunit;
        },
        isToken() {
            return undefined === this.market.quote?.isToken;
        },
        disabledPriceConverterInput: function() {
            return this.useMarketPrice || !this.loggedIn || this.tradeDisabled;
        },
        maxAmountWarning: function() {
            const maxAmount = this.getNewValue(
                this.founds,
                this.buyPrice,
                this.market.quote.subunit,
            ).toString();

            const message = this.getMessageMaxAmount(
                this.buyAmount || 0,
                maxAmount,
            );

            if (message && '0' !== maxAmount) {
                this.resetInputAmount();
            }

            return message;
        },
        tooltipConfig: function() {
            return {
                title: this.rebrandingFunc(this.market.quote.symbol),
                boundary: 'window',
                customClass: 'tooltip-custom',
            };
        },
        shouldTruncate: function() {
            return 10 < this.market.quote.symbol.length;
        },
        founds: function() {
            return new Decimal(this.immutableBalance).toNumber();
        },
        disabledSlider: function() {
            return 0 === this.founds;
        },
        maxSliderAmount: function() {
            return 0 < this.founds
                ? this.founds
                : SLIDER_DEFAULT_MAX_AMOUNT;
        },
        sliderAmount: {
            get() {
                const totalPrice = this.buyAmount * this.buyPrice || '0';
                return totalPrice > this.maxSliderAmount
                    ? this.maxSliderAmount
                    : totalPrice;
            },
            set(value) {
                if (this.isZero(this.buyPrice) && this.isZero(this.buyAmount)) {
                    return;
                }

                this.buyAmount = toMoney(
                    new Decimal(value || 0).div(parseFloat(this.buyPrice) || 1).toString(),
                    this.market.quote.subunit
                );

                this.syncInputAmountFlag(
                    this.buyPrice,
                    this.buyAmount,
                    FLAGS_TRADE_BUY
                );

                this.setInputValuesByFlags();
            },
        },
        totalPrice: {
            get() {
                return 0 !== this.getBuyTotalPriceInput
                    ? this.parseFloatInput(this.getBuyTotalPriceInput)
                    : '';
            },
            set(value) {
                this.setBuyTotalPriceInput(value || '');
            },
        },
        price: function() {
            return toMoney(this.marketPrice, this.priceSubunits) || null;
        },
        minTotalPrice: function() {
            return toMoney('1e-' + this.priceSubunits, this.priceSubunits);
        },
        fieldsValid: function() {
            return 0 < this.buyPrice && 0 < this.buyAmount;
        },
        buttonValid: function() {
            return this.fieldsValid && !this.placingOrder && !this.maxAmountWarning && !this.tradeDisabled;
        },
        disabledMarketPrice: function() {
            return 0 >= this.marketPrice || !this.loggedIn;
        },
        tradeBuyOrderYourGuideBody: function() {
            return this.$t('trade.buy_order.your.guide_body', this.translationsContext);
        },
        translationsContext: function() {
            return {
                baseSymbol: this.rebrandingFunc(this.market.base),
                quoteSymbol: this.rebrandingFunc(this.market.quote),
                quoteBlock: this.isToken
                    ? generateCoinAvatarHtml({
                        image: this.market.quote.image.url,
                        isUserToken: true,
                        symbol: this.rebrandingFunc(this.market.quote.symbol),
                    })
                    : generateCoinAvatarHtml({
                        symbol: this.rebrandingFunc(this.market.quote.symbol),
                        isCrypto: true,
                    }),
                baseBlock: generateCoinAvatarHtml({
                    symbol: this.rebrandingFunc(this.market.base.symbol),
                    isCrypto: true,
                }),
                rebrandedQuoteSymbol: this.rebrandingFunc(this.market.quote.symbol),
                minTotalPrice: this.minTotalPrice,
            };
        },
        ...mapGetters('tradeBalance', [
            'getBuyPriceInput',
            'getBuyAmountInput',
            'getBuyTotalPriceInput',
            'getBaseBalance',
            'getUseBuyMarketPrice',
            'getBuyPriceManuallyEdited',
        ]),
        ...mapGetters('orders', {
            sellOrders: 'getSellOrders',
            buyOrders: 'getBuyOrders',
        }),
        buyPrice: {
            get() {
                return 0 !== this.getBuyPriceInput
                    ? this.parseFloatInput(this.getBuyPriceInput)
                    : '';
            },
            set(value) {
                this.setBuyPriceInput(value || '');
            },
        },
        buyAmount: {
            get() {
                return 0 !== this.getBuyAmountInput
                    ? this.parseFloatInput(this.getBuyAmountInput)
                    : '';
            },
            set(value) {
                this.setBuyAmountInput(value || '');
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
                return this.getUseBuyMarketPrice && 0 < this.marketPrice;
            },
            set(val) {
                this.setUseBuyMarketPrice(val);
            },
        },
        amountManuallyEdited: {
            get() {
                return this.getBuyAmountManuallyEdited;
            },
            set(val) {
                this.setBuyAmountManuallyEdited(val);
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
            const balance = new Decimal(this.immutableBalance || 0);

            let result = this.sellOrders[0] ? this.sellOrders[this.sellOrders.length - 1].price : 0;

            for (const order of this.sellOrders) {
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
        sellOrders: function() {
            if (this.sellOrders && 0 === this.sellOrders.length && this.buyOrders) {
                this.setHighestPrice = true;
                this.setDefaultBuyPrice();
            }
        },
        buyOrders: function() {
            if (this.sellOrders && 0 === this.sellOrders.length && this.buyOrders) {
                this.setHighestPrice = true;
                this.setDefaultBuyPrice();
            }
        },
        useMarketPrice: function() {
            this.updateMarketPrice();
        },
        marketPrice: function() {
            this.updateMarketPrice();
            this.setDefaultBuyPrice();
        },
        balance: function() {
            this.immutableBalance = this.balance;
            if (!this.balance) {
                return;
            }
        },
        market: function() {
            this.tooltipKey += 1;
        },
        changingMarket: function() {
            this.resetInputValues();
        },
    },
    mounted: function() {
        this.setTakerFee(this.takerFee);
    },
};
</script>
