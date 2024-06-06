<template>
    <div class="card pt-2 h-100">
        <div class="card-header d-flex align-items-center justify-content-between">
            <div
                class="font-size-3 font-weight-semibold header-highlighting"
                v-html="$t('trade.sell_order.header')"
            ></div>
            <span class="card-header-icon font-size-3">
                <guide :key="tooltipKey">
                    <template slot="header">
                        {{ $t('trade.sell_order.guide_header') }}
                    </template>
                    <template slot="body">
                        <span v-html="this.$t('trade.sell_order.guide_body', translationsContext)" />
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
                    <div
                        v-if="immutableBalance"
                        class="px-3 font-size-1 font-weight-semibold"
                    >
                        <div class="row pt-2">
                            <div class="col-lg-8 col-md-10 col-12 pl-3">
                                <div class="d-flex align-items-center">
                                    {{ $t('trade.sell_order.your.header') }}
                                    <div
                                        class="d-inline-block sell-order-width c-pointer ml-1 d-flex align-items-center"
                                        @click="balanceClicked"
                                    >
                                        <coin-avatar
                                            :symbol="market.quote.symbol"
                                            :is-crypto="!this.isToken"
                                            :is-user-token="this.isToken"
                                            :image="market.quote.image"
                                            class="mr-1"
                                        />
                                        <span
                                            ref="trade-token-name"
                                            class="truncate-token-name"
                                            v-b-tooltip="truncateTokenName.tooltip"
                                        >
                                            {{ truncateTokenName.name }}:
                                        </span>
                                        <span class="text-nowrap text-primary ml-1 d-flex align-items-center">
                                            {{ immutableBalance | toMoney(market.quote.subunit) | formatMoney }}
                                            <guide class="font-size-2 mtn-3 ml-1 d-block">
                                                <template slot="header">
                                                    {{ this.$t('trade.sell_order.your.guide_header') }}
                                                    <coin-avatar
                                                        :symbol="market.quote.symbol"
                                                        :is-crypto="!isToken"
                                                        :is-user-token="isToken"
                                                        :image="market.quote.image"
                                                    />
                                                    {{ tokenSymbol }}
                                                </template>
                                                <template slot="body">
                                                    <span v-html="getYourGuideBody" />
                                                </template>
                                            </guide>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-4 col-md-2 col-12 d-flex justify-content-start justify-content-md-end">
                                <div
                                    v-if="showDepositMoreLink"
                                    class="pt-2 pt-md-0 d-inline-block text-nowrap"
                                >
                                    <span
                                        :class="getTradeDepositDisabledClasses(getCurrencySymbol)"
                                        tabindex="8"
                                        @click="openDepositModal(getCurrencySymbol)"
                                    >
                                        {{ $t('trade.sell_order.add_more_funds') }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-group ml-3 mt-3">
                        <label for="sell-price-input" class="text-uppercase font-weight-bold">
                            {{ $t('trade.sell_order.price_in.header', translationsContext) }}
                        </label>
                        <guide
                            :key="tooltipKey"
                            class="font-size-1"
                        >
                            <template slot="header">
                                {{ $t('trade.sell_order.price_in.guide_header', translationsContext) }}
                            </template>
                            <template slot="body">
                                <span v-html="$t('trade.sell_order.price_in.guide_body', translationsContext)"></span>
                            </template>
                        </guide>
                    </div>
                    <div class="row align-items-center m-0">
                        <div class="col-12">
                            <div class="d-flex flex-1 pt-2">
                                <div class="w-100 flex-nowrap">
                                    <price-converter-input
                                        v-model="sellPrice"
                                        input-id="sell-price-input"
                                        :disabled="disabledPriceConverterInput"
                                        tabindex="9"
                                        :from="market.base.symbol"
                                        :to="USD.symbol"
                                        :subunit="4"
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
                        for="sell-price-amount"
                        class="d-flex flex-row flex-nowrap justify-content-start
                            font-weight-semibold ml-3 mb-1 text-uppercase"
                    >
                        <span class="d-inline-block text-nowrap">
                            {{ $t('trade.sell_order.amount') }}
                        </span>
                    </label>
                    <div class="row align-items-center m-0">
                        <div class="col-12">
                            <div class="d-flex flex-1 pt-2">
                                <div class="w-100 flex-nowrap">
                                    <input
                                        id="sell-price-amount"
                                        type="text"
                                        v-model="sellAmount"
                                        class="trade-order-input form-control white-input py-3 h-auto"
                                        :disabled="!loggedIn || tradeDisabled"
                                        tabindex="10"
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
                        tabindex="11"
                        @change="sliderNewAmount"
                    />
                </div>
                <div v-if="loggedIn" class="col-12 pt-3">
                    <div class="text-uppercase font-weight-semibold mb-1">
                        {{ $t('trade.sell_order.total_price.header') }}
                        <guide class="font-size-2 mtn-6 ml-1">
                            <template slot="header">
                                {{ $t('trade.sell_order.total_price.guide_header') }}
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
                                    input-id="sell-total-price-input"
                                    :disabled="disabledPriceConverterInput"
                                    tabindex="12"
                                    :from="market.base.symbol"
                                    :to="USD.symbol"
                                    :subunit="priceSubunits"
                                    symbol="$"
                                    :input-class="{'trade-order-input white-input' : true}"
                                    :overflow-class="{'trade-order-input--overflow' : true}"
                                    @keypress="checkPriceInput"
                                    @keyup="keyupTotalPriceInput($event)"
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
                <div class="row m-0 pt-4">
                    <div class="col-12">
                        <m-button
                            v-if="loggedIn"
                            class="btn btn-primary py-3 w-100 text-uppercase"
                            :disabled="!buttonValid"
                            :loading="placingOrder"
                            tabindex="13"
                            @click="placeOrder"
                        >
                            <span :class="{'text-muted': tradeDisabled}">
                                {{ $t('trade.sell_order.submit') }}
                            </span>
                        </m-button>
                        <template v-else>
                            <button
                                id="sell-login-url"
                                class="btn btn-primary"
                                @click.prevent="goToPage(loginUrl)"
                            >
                                {{ $t('log_in') }}
                            </button>
                            <span class="px-2">{{ $t('or') }}</span>
                            <button
                                id="sell-signup-url"
                                class="btn btn-link seo-link"
                                @click.prevent="goToPage(signupUrl)"
                            >
                                {{ $t('sign_up') }}
                            </button>
                        </template>
                    </div>
                </div>
            </div>
            <template v-else>
                <div class="p-5 text-center text-white">
                    <font-awesome-icon icon="circle-notch" spin class="loading-spinner" fixed-width />
                </div>
            </template>
        </div>
        <deposit-modal
            :visible="showDepositModal"
            :currency="getCurrencySymbol"
            :is-token="isTokenModal"
            :is-created-on-mintme-site="isCreatedOnMintmeSite"
            :is-owner="isOwner"
            :token-avatar="getTokenImageUrl"
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
import {VBTooltip} from 'bootstrap-vue';
import Decimal from 'decimal.js';
import {mapMutations, mapGetters} from 'vuex';
import Guide from '../Guide';
import MSlider from '../UI/Slider';
import {toMoney, generateCoinAvatarHtml} from '../../utils';
import PriceConverterInput from '../PriceConverterInput';
import DepositModal from '../modal/DepositModal';
import {MButton} from '../UI';
import CoinAvatar from '../CoinAvatar';
import {
    FiltersMixin,
    PlaceOrder,
    WebSocketMixin,
    MoneyFilterMixin,
    PricePositionMixin,
    RebrandingFilterMixin,
    OrderMixin,
    OpenPageMixin,
    FloatInputMixin,
    DepositModalMixin,
    TradeCheckInput,
} from '../../mixins/';
import {
    MINTME,
    USD,
    webSymbol,
    SLIDER_DEFAULT_MAX_AMOUNT,
    TRADE_ORDER_INPUT_FLAGS,
} from '../../utils/constants';

library.add(faCircleNotch);

const FLAGS_TRADE_SELL = {
    price: TRADE_ORDER_INPUT_FLAGS.sellPrice,
    amount: TRADE_ORDER_INPUT_FLAGS.sellAmount,
    totalPrice: TRADE_ORDER_INPUT_FLAGS.sellTotalPrice,
};

const MEDIA_BREAKPOINT = {
    xs: {
        width: 320,
        elementWidth: 63,
    },
    sm: {
        width: 375,
        elementWidth: 133,
    },
    sl: {
        width: 425,
        elementWidth: 143,
    },
    md: {
        width: 768,
        elementWidth: 330,
    },
    lg: {
        width: 1024,
        elementWidth: 79,
    },
};

export default {
    name: 'TradeSellOrder',
    components: {
        PriceConverterInput,
        Guide,
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
        FiltersMixin,
        MoneyFilterMixin,
        PricePositionMixin,
        RebrandingFilterMixin,
        OrderMixin,
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
        isOwner: Boolean,
        balanceLoaded: Boolean,
        serviceUnavailable: Boolean,
        tradeDisabled: Boolean,
        currencyMode: String,
        isCreatedOnMintmeSite: Boolean,
        changingMarket: Boolean,
    },
    data() {
        return {
            action: 'sell',
            placingOrder: false,
            USD,
            tooltipKey: 0,
            windowWidth: window.innerWidth,
            elementWidth: MEDIA_BREAKPOINT.lg.elementWidth,
            setLowestPrice: true,
        };
    },
    mounted() {
        window.addEventListener('resize', () => {
            this.windowWidth = window.innerWidth;
        });
    },
    methods: {
        ...mapMutations('tradeBalance', [
            'setSellPriceInput',
            'setSellAmountInput',
            'setSellTotalPriceInput',
            'setQuoteFullBalance',
            'setUseSellMarketPrice',
            'setSellPriceManuallyEdited',
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
                this.sellAmount,
                FLAGS_TRADE_SELL
            );

            this.setInputValuesByFlags();
        },
        keyupAmountInput(event) {
            const amount = event.target.value;

            this.syncInputAmountFlag(
                this.sellPrice,
                amount,
                FLAGS_TRADE_SELL
            );

            this.setInputValuesByFlags();
        },
        keyupTotalPriceInput(event) {
            const totalPrice = event.target.value;

            this.syncInputTotalPriceFlag(
                this.sellPrice,
                this.sellAmount,
                totalPrice,
                FLAGS_TRADE_SELL
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

            if (this.sellPrice && this.sellAmount) {
                if ((new Decimal(this.sellPrice)).times(this.sellAmount).lessThan(this.minTotalPrice)) {
                    this.showNotification({
                        result: 2,
                        message: this.$t('trade.sell_order.amount_has_to_be', this.translationsContext),
                    });
                    return;
                }

                this.placingOrder = true;
                const data = {
                    amountInput: toMoney(this.sellAmount, this.market.quote.subunit),
                    priceInput: toMoney(this.sellPrice, this.priceSubunits),
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
                        this.placingOrder = false;
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
                this.sellPrice = '';
            }
            this.sellAmount = '';
            this.totalPrice = '';
        },
        updateMarketPrice: function() {
            if (this.useMarketPrice) {
                this.sellPrice = this.price || '';
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

            if (this.isZero(this.sellPrice)) {
                this.setDefaultSellPrice();
            }

            this.syncInputAmountFlag(
                this.sellPrice,
                this.sellAmount,
                FLAGS_TRADE_SELL
            );

            this.setInputValuesByFlags();
        },
        getCoinAvatar(symbol) {
            return (this.market.quote.symbol === symbol && webSymbol !== symbol)
                ? this.market.quote.image
                : null;
        },
        setDefaultSellPrice() {
            this.setInputFlag(FLAGS_TRADE_SELL.price);

            if (this.setLowestPrice && this.lowestPrice) {
                this.sellPrice = this.parseFloatInput(
                    new Decimal(this.lowestPrice).minus(this.minTotalPrice).toFixed()
                );
                this.setLowestPrice = false;

                return;
            }

            this.sellPrice = this.marketPrice
                ? this.parseFloatInput(this.marketPrice)
                : '';
            this.setLowestPrice = false;
        },
        sliderNewAmount(sliderAmount) {
            this.sliderAmount = sliderAmount;
        },
        setInputValuesByFlags() {
            if (
                this.hasInputFlag(FLAGS_TRADE_SELL.amount) &&
                this.hasInputFlag(FLAGS_TRADE_SELL.totalPrice)
            ) {
                this.sellPrice = this.getNewPrice(
                    this.totalPrice,
                    this.sellAmount,
                    this.priceSubunits
                );
            }

            if (
                this.hasInputFlag(FLAGS_TRADE_SELL.price) &&
                this.hasInputFlag(FLAGS_TRADE_SELL.totalPrice)
            ) {
                this.sellAmount = this.getNewAmount(
                    this.totalPrice,
                    this.sellPrice,
                    this.market.quote.subunit
                );
            }

            if (
                this.hasInputFlag(FLAGS_TRADE_SELL.price) &&
                this.hasInputFlag(FLAGS_TRADE_SELL.amount)
            ) {
                this.totalPrice = this.getTotalPrice(
                    this.sellPrice,
                    this.sellAmount,
                    this.priceSubunits
                );
            }
        },
        resetInputAmount() {
            this.sellAmount = '';
        },
        getMessageMaxAmount(value, maxAmount, subunit) {
            if (new Decimal(value).gt(maxAmount)) {
                const translationsContext = {
                    maxAmount: new Decimal(maxAmount)
                        .toDP(subunit, Decimal.ROUND_DOWN)
                        .toString(),
                    currency: this.rebrandingFunc(this.market.quote.symbol),
                };

                return this.$t('max.amount.warning', translationsContext);
            }

            return false;
        },
        resetInputValues() {
            this.sellPrice = '';
            this.sellAmount = '';
            this.totalPrice = '';
        },
        tooltipConfig() {
            return {
                title: this.rebrandingFunc(this.market.quote.symbol),
                boundary: 'window',
                customClass: 'tooltip-custom',
            };
        },
    },
    computed: {
        lowestPrice() {
            return this.sellOrders[0]
                ? this.sellOrders[0].price
                : 0;
        },
        priceSubunits() {
            return this.isToken && this.market.quote.priceDecimals
                ? this.market.quote.priceDecimals
                : this.market.base.subunit;
        },
        getTokenImageUrl() {
            return this.isToken
                ? this.market.quote?.image.url
                : '';
        },
        isToken() {
            return undefined === this.market.quote?.isToken;
        },
        truncateTokenName() {
            const name = this.rebrandingFunc(this.market.quote.symbol);
            const tokenNameConfig = {
                name,
                tooltip: this.tooltipConfig(),
            };

            return this.dynamicTruncate(
                this.windowWidth,
                this.elementWidth,
                MEDIA_BREAKPOINT,
                tokenNameConfig,
            );
        },
        disabledPriceConverterInput: function() {
            return this.useMarketPrice || !this.loggedIn || this.tradeDisabled;
        },
        maxAmountWarning: function() {
            const message = this.getMessageMaxAmount(
                this.sellAmount || '0',
                this.founds,
                this.market.quote.subunit
            );

            if (message) {
                this.resetInputAmount();
            }

            return message;
        },
        tokenSymbol: function() {
            return this.rebrandingFunc(this.market.quote) === MINTME.symbol ? MINTME.symbol : 'Token';
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
                const sellAmount = this.sellAmount || '0';
                return sellAmount > this.maxSliderAmount
                    ? this.maxSliderAmount
                    : sellAmount;
            },
            set(value) {
                if (this.isZero(this.sellPrice) && this.isZero(this.sellAmount)) {
                    return;
                }

                this.sellAmount = new Decimal(value).toString();

                this.syncInputAmountFlag(
                    this.sellPrice,
                    this.sellAmount,
                    FLAGS_TRADE_SELL
                );

                this.setInputValuesByFlags();
            },
        },
        totalPrice: {
            get() {
                return 0 !== this.getSellTotalPriceInput
                    ? this.parseFloatInput(this.getSellTotalPriceInput)
                    : '';
            },
            set(value) {
                this.setSellTotalPriceInput(value || '');
            },
        },
        price: function() {
            return toMoney(this.marketPrice, this.priceSubunits) || null;
        },
        minTotalPrice: function() {
            return toMoney('1e-' + this.priceSubunits, this.priceSubunits);
        },
        fieldsValid: function() {
            return 0 < this.sellPrice && 0 < this.sellAmount;
        },
        buttonValid: function() {
            return this.fieldsValid && !this.placingOrder && !this.maxAmountWarning && !this.tradeDisabled;
        },
        disabledMarketPrice: function() {
            return 0 >= this.marketPrice || !this.loggedIn;
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
                minTotalPrice: this.minTotalPrice,
                tokenSymbol: this.tokenSymbol,
            };
        },
        ...mapGetters('tradeBalance', [
            'getSellPriceInput',
            'getSellAmountInput',
            'getSellTotalPriceInput',
            'getQuoteBalance',
            'getUseSellMarketPrice',
            'getSellPriceManuallyEdited',
        ]),
        ...mapGetters('orders', {
            buyOrders: 'getBuyOrders',
            sellOrders: 'getSellOrders',
        }),
        sellPrice: {
            get() {
                return 0 !== this.getSellPriceInput
                    ? this.parseFloatInput(this.getSellPriceInput)
                    : '';
            },
            set(value) {
                this.setSellPriceInput(value || '');
            },
        },
        sellAmount: {
            get() {
                return 0 !== this.getSellAmountInput
                    ? this.parseFloatInput(this.getSellAmountInput)
                    : '';
            },
            set(value) {
                this.setSellAmountInput(value || '');
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
        amountManuallyEdited: {
            get() {
                return this.getSellAmountManuallyEdited;
            },
            set(val) {
                this.setSellAmountManuallyEdited(val);
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
            const balance = new Decimal(this.immutableBalance || 0)
                .toDecimalPlaces(this.market.quote.subunit, Decimal.ROUND_DOWN);

            let result = this.buyOrders[0] ? this.buyOrders[this.buyOrders.length - 1].price : 0;

            for (const order of this.buyOrders) {
                tokenAmount = tokenAmount.add(order.amount);

                if (balance.lessThanOrEqualTo(tokenAmount)) {
                    result = order.price;

                    break;
                }
            }

            return result;
        },
        getYourGuideBody() {
            return this.$t('trade.sell_order.your.guide_body', this.translationsContext);
        },
    },
    watch: {
        buyOrders: function() {
            if (this.buyOrders && 0 === this.buyOrders.length && this.sellOrders) {
                this.setLowestPrice = true;
                this.setDefaultSellPrice();
            }
        },
        sellOrders: function() {
            if (this.buyOrders && 0 === this.buyOrders.length && this.sellOrders) {
                this.setLowestPrice = true;
                this.setDefaultSellPrice();
            }
        },
        useMarketPrice: function() {
            this.updateMarketPrice();
        },
        marketPrice: function() {
            this.updateMarketPrice();
            this.setDefaultSellPrice();
        },
        market: function() {
            this.tooltipKey += 1;
        },
        changingMarket: function() {
            this.resetInputValues();
        },
        balanceLoaded: function() {
            setTimeout(() => {
                this.elementWidth = this.$refs['trade-token-name'].clientWidth;
            }, 0);
        },
    },
    filters: {
        toMoney: function(val, precision) {
            return toMoney(val, precision);
        },
    },
};
</script>
