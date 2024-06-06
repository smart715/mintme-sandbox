<template>
    <div
        v-if="!disabledServices.allServicesDisabled && !disabledServices.tradingDisabled"
        class="card card-yellow"
    >
        <div class="card-body" :class="[isHorizontalMode ? 'p-3 px-5 pb-4' : 'py-3']">
            <div v-show="!showForms" class="row">
                <div
                    class="col-12"
                    :class="{'d-flex align-items-center p-0': isHorizontalMode}"
                >
                    <div class="flex-1 qt-input-container">
                        <div id="show-balance">
                            <span class="font-weight-bold text-uppercase">
                                {{ tradeModeLabel }}
                            </span>
                            <template v-if="loggedIn">
                                <span
                                    v-if="canDeposit(topCurrency)"
                                    :class="getDepositDisabledClasses(topCurrency)"
                                    @click="openDepositModal(topCurrency)"
                                >
                                    {{ $t('quick_trade.add_more_funds') }}
                                </span>
                            </template>
                            <guide
                                v-if="isToken"
                                class="qt-tooltip float-right"
                                reactive
                            >
                                <template slot="body">
                                    {{ $t('quick_trade.donation.non_refund') }}
                                    <a
                                        href="#trade-section"
                                        class="text-primary"
                                        @click="goToTrade"
                                    >
                                        {{ $t('page.pair.tab.market') }}.
                                    </a>
                                </template>
                            </guide>
                        </div>
                        <div class="d-flex flex-1 mt-2 qt-field-height bg-white vti-border">
                            <div class="input-group flex-nowrap flex-column qt-input-wrapper">
                                <div
                                    class="qt-balance text-truncate"
                                    :class="{'d-none': !userLoggedIn}"
                                >
                                    {{ $t('balance') }}
                                    <span v-if="serviceUnavailable || !loggedIn">
                                        -/-
                                    </span>
                                    <font-awesome-icon
                                        v-else-if="null === balance"
                                        icon="circle-notch"
                                        class="loading-spinner"
                                        fixed-width
                                        spin
                                    />
                                    <span v-else>{{ balance | toMoney(topSubunit) }}</span>
                                </div>
                                <div class="form-control d-flex align-items-center w-100 pr-0">
                                    <font-awesome-icon
                                        v-if="isCheckingTradeReversed"
                                        icon="circle-notch"
                                        class="loading-spinner"
                                        fixed-width
                                        spin
                                    />
                                    <template v-else>
                                        <span>{{ topTilde }}</span>
                                        <price-converter-input
                                            class="flex-grow-1 d-flex align-items-center"
                                            v-model="amount"
                                            input-id="amount-to-donate"
                                            :convert="baseAmount"
                                            :from="selectedBase"
                                            :to="USD.symbol"
                                            :subunit="USD.subunit"
                                            symbol="$"
                                            :input-class="{'quicktrade-input white-input' : true}"
                                            :disabled="tradesDisabled"
                                            @keypress="checkAmountInput"
                                            @paste="checkAmountInput"
                                            @keyup="onKeyup"
                                        />
                                    </template>
                                </div>
                            </div>
                            <m-dropdown
                                id="quick_trade_currency"
                                :text="truncateFunc(rebrandedTop, 6)"
                                variant="primary"
                                class="qt-dropdown dark-border-on-focus m-dropdown-top-currencies"
                                theme="white"
                                hideAssistive
                                tabindex="0"
                            >
                                <template v-slot:button-content>
                                    <div class="d-flex align-items-center flex-fill">
                                        <coin-avatar
                                            :image="getCoinAvatar(topCurrency)"
                                            :symbol="topCurrency"
                                            :is-crypto="!getCoinAvatar(topCurrency)"
                                            :is-user-token="!!getCoinAvatar(topCurrency)"
                                            image-class="coin-avatar-lg"
                                            class="qt-coin-avatar mr-2 d-inline-flex"
                                        />
                                        <span class="text-truncate">
                                            {{ topCurrency | rebranding | truncate(12) }}
                                        </span>
                                    </div>
                                </template>
                                <m-dropdown-item
                                    v-for="option in topOptions"
                                    :key="option"
                                    :value="option"
                                    @click="updateCurrency(option)"
                                    :active="option === topCurrency"
                                >
                                    <div class="d-flex align-items-center">
                                        <coin-avatar
                                            :image="getCoinAvatar(option)"
                                            :symbol="option"
                                            :is-crypto="!getCoinAvatar(topCurrency)"
                                            :is-user-token="!!getCoinAvatar(topCurrency)"
                                            image-class="coin-avatar-lg"
                                            class="qt-coin-avatar mr-2 d-inline-flex"
                                        />
                                        {{ option | rebranding | truncate(7) }}
                                    </div>
                                </m-dropdown-item>
                            </m-dropdown>
                        </div>
                        <div class="text-danger" v-if="errorMessage">
                            {{ errorMessage }}
                        </div>
                    </div>
                    <div v-if="isHorizontalMode" class="px-3 pt-4 mt-2 my-auto">
                        <font-awesome-icon
                            icon="exchange-alt"
                            class="c-pointer font-size-2 text-primary"
                            fixed-width
                            @click="toggleTradeMode"
                        />
                    </div>
                    <div class="flex-1 mb-auto">
                        <div
                            v-if="!isHorizontalMode"
                            class="c-pointer qt-toggle-icon mt-3 mb-2"
                        >
                            <font-awesome-icon
                                icon="exchange-alt"
                                fixed-width
                                @click="toggleTradeMode"
                                class="font-size-3 text-primary"
                            />
                        </div>
                        <div id="show-balance" class="pb-2">
                            <span class="font-weight-bold text-uppercase">
                                {{ tradeModeSecondLabel }}
                            </span>
                        </div>
                        <div class="d-flex flex-1 qt-field-height bg-white vti-border">
                            <div class="input-group flex-nowrap flex-column qt-input-wrapper">
                                <div
                                    class="qt-balance text-truncate"
                                    :class="{'d-none': !userLoggedIn}"
                                >
                                    {{ $t('balance') }}
                                    <span v-if="serviceUnavailable || !loggedIn">
                                        -/-
                                    </span>
                                    <font-awesome-icon
                                        v-else-if="null === bottomBalance"
                                        icon="circle-notch"
                                        class="loading-spinner"
                                        fixed-width
                                        spin
                                    />
                                    <span v-else>{{ bottomBalance | toMoney(bottomSubunit) }}</span>
                                </div>
                                <div
                                    class="form-control d-flex align-items-center w-100 pr-0"
                                >
                                    <font-awesome-icon
                                        v-if="isCheckingTrade"
                                        icon="circle-notch"
                                        spin
                                        class="loading-spinner"
                                        fixed-width
                                    />
                                    <template v-else>
                                        <span>{{ bottomTilde }}</span>
                                        <price-converter-input
                                            v-model="amountToReceive"
                                            input-id="amount-to-donate-reversed"
                                            class="d-flex flex-grow-1 align-items-center"
                                            symbol="$"
                                            :convert="baseAmountReversed"
                                            :to="selectedBase"
                                            :from="USD.symbol"
                                            :subunit="USD.subunit"
                                            :show-converter="false"
                                            :input-class="{'quicktrade-input white-input' : true}"
                                            :disabled="tradesDisabled"
                                            @keypress="checkAmountReversedInput"
                                            @paste="checkAmountReversedInput"
                                            @keyup="onKeyupReversed"
                                       />
                                    </template>
                                </div>
                            </div>
                            <m-dropdown
                                id="quick_trade_currency"
                                :text="truncateFunc(rebrandedBottom, 6)"
                                variant="primary"
                                class="qt-dropdown dark-border-on-focus"
                                theme="white"
                                hideAssistive
                                tabindex="0"
                            >
                                <template v-slot:button-content>
                                    <div class="d-flex align-items-center flex-fill">
                                        <coin-avatar
                                            :image="getCoinAvatar(bottomCurrency)"
                                            :symbol="bottomCurrency"
                                            :is-crypto="!getCoinAvatar(bottomCurrency)"
                                            :is-user-token="!!getCoinAvatar(bottomCurrency)"
                                            image-class="coin-avatar-lg"
                                            class="qt-coin-avatar mr-2 d-inline-flex"
                                        />
                                        <span class="text-truncate">
                                            {{ bottomCurrency | rebranding | truncate(12) }}
                                        </span>
                                    </div>
                                </template>
                                <m-dropdown-item
                                    v-for="option in bottomOptions"
                                    :key="option"
                                    :value="option"
                                    @click="bottomCurrency = option"
                                    :active="option === bottomCurrency"
                                >
                                    <div class="d-flex align-items-center">
                                        <coin-avatar
                                            :image="getCoinAvatar(option)"
                                            :symbol="option"
                                            :is-crypto="!getCoinAvatar(bottomCurrency)"
                                            :is-user-token="!!getCoinAvatar(bottomCurrency)"
                                            image-class="coin-avatar-lg"
                                            class="qt-coin-avatar mr-2 d-inline-flex"
                                        />
                                        {{ option | rebranding | truncate(7) }}
                                    </div>
                                </m-dropdown-item>
                            </m-dropdown>
                        </div>
                    </div>
                    <div :class="{'d-flex align-items-end col-4 ml-3 h-100': isHorizontalMode}">
                        <button
                            :disabled="buttonDisabled"
                            @click="showConfirmationModal"
                            class="btn btn-primary qt-action-btn w-100 py-3 text-uppercase my-3"
                        >
                            <span :class="{'text-muted': disabledServices.newTradesDisabled}">
                                <template v-if="tradesDisabled">
                                    {{ $t('trading_disabled') }}
                                </template>
                                <template v-else-if="isBuyMode">
                                    {{ $t('buy') }}
                                </template>
                                <template v-else>
                                    {{ $t('sell') }}
                                </template>
                            </span>
                        </button>
                        <confirm-modal
                            :visible="shouldShowConfirmModal"
                            :show-image="false"
                            @confirm="makeTrade"
                            @cancel="cancelTrade"
                            @close="shouldShowConfirmModal = false"
                        >
                            <p class="text-white modal-title text-break pt-2 pb-4">
                                {{ $t('quick_trade.donation.modal.1') }}
                                <br>
                                <span v-html="$t('quick_trade.donation.modal.2', translationsContext)" />
                            </p>
                            <template v-slot:confirm>
                                {{ $t('confirm_modal.continue') }}
                            </template>
                        </confirm-modal>
                        <add-phone-alert-modal
                            :visible="addPhoneModalVisible"
                            :message="addPhoneModalMessage"
                            @close="addPhoneModalVisible = false"
                            @phone-verified="onPhoneVerified"
                        />
                    </div>
                </div>
                <deposit-modal
                    :visible="showDepositModal"
                    :currency="topCurrency"
                    :is-token="isTokenModal"
                    :is-created-on-mintme-site="isCreatedOnMintmeSite"
                    :is-owner="isOwner"
                    :token-avatar="getCoinAvatar(topCurrency)"
                    :token-networks="currentTokenNetworks"
                    :crypto-networks="currentCryptoNetworks"
                    :subunit="currentSubunit"
                    :no-close="false"
                    :add-phone-alert-visible="addPhoneAlertVisible"
                    :deposit-add-phone-modal-visible="depositAddPhoneModalVisible"
                    @close-confirm-modal="closeConfirmModal"
                    @phone-alert-confirm="onPhoneAlertConfirm(topCurrency)"
                    @close-add-phone-modal="closeAddPhoneModal"
                    @deposit-phone-verified="onDepositPhoneVerified"
                    @close="closeDepositModal"
                />
            </div>
            <div v-if="!loggedIn" class="d-flex justify-content-center">
                <login-signup-switcher
                    v-show="showForms"
                    :login-recaptcha-sitekey="loginRecaptchaSitekey"
                    :reg-recaptcha-sitekey="regRecaptchaSitekey"
                    :tab="tab"
                    @login="onLogin"
                    @signup="onSignup"
                />
            </div>
        </div>
    </div>
    <div v-else>
        <div v-if="!isTradePage" class="h4 text-center mt-3">
          {{ $t('donate.page.disabled') }}
        </div>
    </div>
</template>

<script>
import {library} from '@fortawesome/fontawesome-svg-core';
import {faCircleNotch, faExchangeAlt} from '@fortawesome/free-solid-svg-icons';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import debounce from 'lodash/debounce';
import {VBTooltip} from 'bootstrap-vue';
import {mapGetters} from 'vuex';
import Decimal from 'decimal.js';
import {
    CheckInputMixin,
    MoneyFilterMixin,
    NotificationMixin,
    RebrandingFilterMixin,
    WebSocketMixin,
    AddPhoneAlertMixin,
    FiltersMixin,
    FloatInputMixin,
    DepositModalMixin,
    CurrencyConverter,
    PlaceOrder,
} from '../mixins';
import ConfirmModal from './modal/ConfirmModal';
import AddPhoneAlertModal from './modal/AddPhoneAlertModal';
import {formatMoney, toMoney, generateCoinAvatarHtml, removeSpaces} from '../utils';
import {
    webSymbol,
    tokenDeploymentStatus,
    HTTP_BAD_REQUEST,
    USD,
    WEB,
    MINTME,
    usdSign,
} from '../utils/constants';
import PriceConverterInput from './PriceConverterInput';
import {MDropdown, MDropdownItem} from './UI';
import CoinAvatar from './CoinAvatar';
import DepositModal from './modal/DepositModal';
import Guide from './Guide';

library.add(faCircleNotch, faExchangeAlt);

const BUY_MODE = 'buy';
const SELL_MODE = 'sell';

export default {
    name: 'QuickTrade',
    components: {
        MDropdown,
        MDropdownItem,
        PriceConverterInput,
        ConfirmModal,
        LoginSignupSwitcher: () => import('./LoginSignupSwitcher').then((data) => data.default),
        AddPhoneAlertModal,
        FontAwesomeIcon,
        CoinAvatar,
        DepositModal,
        Guide,
    },
    directives: {
        'b-tooltip': VBTooltip,
    },
    mixins: [
        CheckInputMixin,
        MoneyFilterMixin,
        NotificationMixin,
        RebrandingFilterMixin,
        WebSocketMixin,
        AddPhoneAlertMixin,
        FiltersMixin,
        FloatInputMixin,
        DepositModalMixin,
        CurrencyConverter,
        PlaceOrder,
    ],
    props: {
        loggedIn: Boolean,
        loginRecaptchaSitekey: String,
        regRecaptchaSitekey: String,
        params: Object,
        minAmounts: Object,
        disabledServicesConfig: String,
        profileNickname: String,
        isToken: Boolean,
        isMobileScreen: Boolean,
        isHorizontal: Boolean,
        isCreatedOnMintmeSite: Boolean,
        isOwner: Boolean,
        tab: {
            type: String,
            default: 'intro',
        },
        isTradePage: Boolean,
        tradesDisabled: Boolean,
    },
    data() {
        return {
            topCurrency: null,
            bottomCurrency: null,
            amount: '',
            amountToReceive: '',
            placeholder: '0.0',
            worth: '0',
            ordersSummary: '0',
            left: '0',
            isCheckingTrade: false,
            isCheckingTradeReversed: false,
            isCheckingHasError: false,
            isTradeInProgress: false,
            shouldShowConfirmModal: false,
            ordersUpdated: false,
            showForms: false,
            firstInteraction: false,
            firstCheck: false,
            addPhoneModalMessageType: 'action',
            tradeMode: BUY_MODE,
            topTilde: ' ',
            bottomTilde: ' ',
            routeName: this.isToken ? 'token_show' : 'coin',
            USD: USD,
        };
    },
    computed: {
        ...mapGetters('tradeBalance', {
            balances: 'getBalances',
            serviceUnavailable: 'isServiceUnavailable',
        }),
        ...mapGetters('market', {
            currentMarket: 'getCurrentMarket',
            markets: 'getMarkets',
        }),
        ...mapGetters('tokenInfo', {
            deploymentStatus: 'getDeploymentStatus',
        }),
        ...mapGetters('user', {
            getUserId: 'getId',
        }),
        ...mapGetters('crypto', {
            enabledCryptosMap: 'getCryptosMap',
        }),
        ...mapGetters('rates', [
            'getRates',
        ]),
        userLoggedIn: function() {
            return null !== this.getUserId;
        },
        availableMarketsOptions: function() {
            return Object.keys(this.markets || {})
                .filter((symbol) => !this.disabledServices.tradesDisabled[symbol]);
        },
        availableTokenCryptoMarkets: function() {
            return Object.keys(this.enabledCryptosMap || {})
                .filter((symbol) => !this.disabledServices.tradesDisabled[symbol]);
        },
        isHorizontalMode: function() {
            return this.isHorizontal && !this.isMobileScreen;
        },
        // eslint-disable-next-line complexity
        errorMessage: function() {
            if (this.serviceUnavailable) {
                return this.$t('toasted.error.service_unavailable_short');
            }

            if (this.firstInteraction && !this.isAmountValid) {
                return this.$t('quick_trade.min_amount', this.translationsContext);
            }

            if (this.insufficientFunds) {
                return this.$t('quick_trade.insufficient_funds');
            }

            if (this.firstCheck &&
                !this.isDonationMode &&
                this.amountExceedsOrders
            ) {
                if (this.isOrdersSummaryZero) {
                    return this.$t('quick_trade.order.empty');
                }

                if (this.isSellMode) {
                    return this.$t('quick_trade.sell_exceeds', this.translationsContext);
                }

                return this.$t('quick_trade.buy_exceeds', this.translationsContext);
            }

            return '';
        },
        topOptions: function() {
            return this.isBuyMode
                ? this.isToken ? this.availableTokenCryptoMarkets : this.availableMarketsOptions
                : [this.currentMarket.quote.symbol];
        },
        bottomOptions: function() {
            return this.isBuyMode
                ? [this.currentMarket.quote.symbol]
                : this.availableMarketsOptions;
        },
        balance: function() {
            return this.balances
                ? this.balances[this.topCurrency].available
                : null;
        },
        bottomBalance: function() {
            return this.balances
                ? this.balances[this.bottomCurrency].available
                : null;
        },
        balanceLoaded: function() {
            return null !== this.balance;
        },
        isBuyMode: function() {
            return BUY_MODE === this.tradeMode;
        },
        isSellMode: function() {
            return SELL_MODE === this.tradeMode;
        },
        isDonationMode: function() {
            return this.isToken && this.isBuyMode;
        },
        isOrdersSummaryZero: function() {
            const summary = new Decimal(this.ordersSummary);

            return !this.isCheckingTrade && !this.isCheckingTradeReversed && summary.isZero();
        },
        isLeftZero: function() {
            return new Decimal(this.left || 0).isZero();
        },
        translationsContext: function() {
            return {
                amount: toMoney(this.amount || 0, this.topSubunit),
                amountToReceive: toMoney(this.amountToReceive || 0, this.bottomSubunit),
                assetToReceive: this.rebrandingFunc(this.bottomCurrency),
                assetAvatar: generateCoinAvatarHtml({
                    image: this.getCoinAvatar(this.bottomCurrency)?.url,
                    symbol: this.bottomCurrency,
                    isCrypto: !this.getCoinAvatar(this.bottomCurrency),
                    isUserToken: !!this.getCoinAvatar(this.bottomCurrency),
                }),
                worth: formatMoney(toMoney(this.worth, this.topSubunit)),
                ordersSummary: toMoney(this.ordersSummary, this.bottomSubunit),
                currency: this.rebrandedTop,
                currencyAvatar: generateCoinAvatarHtml({
                    image: this.getCoinAvatar(this.topCurrency)?.url,
                    symbol: this.topCurrency,
                    isCrypto: !this.getCoinAvatar(this.topCurrency),
                    isUserToken: !!this.getCoinAvatar(this.topCurrency),
                }),
                currencyMinAmount: this.topMinAmount,
            };
        },
        rebrandedTop: function() {
            return this.rebrandingFunc(this.topCurrency);
        },
        rebrandedBottom: function() {
            return this.rebrandingFunc(this.bottomCurrency);
        },
        topSubunit: function() {
            const tradable = this.enabledCryptosMap[this.topCurrency];

            if (tradable) {
                return this.isToken && this.currentMarket.quote.priceDecimals
                    ? this.currentMarket.quote.priceDecimals
                    : tradable.subunit;
            }

            return WEB.subunit;
        },
        bottomSubunit: function() {
            const tradable = this.enabledCryptosMap[this.bottomCurrency];

            if (tradable) {
                return this.isToken && this.currentMarket.quote.priceDecimals
                    ? this.currentMarket.quote.priceDecimals
                    : tradable.subunit;
            }

            return WEB.subunit;
        },
        topMinAmount: function() {
            return this.minAmounts[this.topCurrency] ?? this.minAmounts[MINTME.symbol];
        },
        minTotalPrice: function() {
            return toMoney('1e-' + this.topSubunit, this.topSubunit);
        },
        insufficientFunds: function() {
            return this.loggedIn && this.balanceLoaded &&
                (
                    (new Decimal(this.balance)).lessThan(this.minTotalPrice)
                    ||
                    (0 < this.amount && (new Decimal(this.amount)).greaterThan(this.balance))
                    ||
                    (this.isBuyMode && 0 < this.amount && 0 < this.left)
                );
        },
        amountExceedsOrders: function() {
            const amount = new Decimal(this.amount || 0).mul(new Decimal(1).minus(this.currentFee));

            return (!this.isCheckingTrade
                && !this.isCheckingTradeReversed
                && !amount.isZero()
                && amount.greaterThan(this.isSellMode ? this.ordersSummary : this.worth))
                || (this.isSellMode && !this.isLeftZero);
        },
        currentFee: function() {
            const feeType = this.isToken
                ? 'token'
                : 'coin';

            return this.isBuyMode
                ? this.params.buy_fee[feeType]
                : this.params.sell_fee[feeType];
        },
        isAmountValid: function() {
            const amount = new Decimal(this.amount || 0);

            return !amount.isZero()
                && amount.greaterThanOrEqualTo(this.topMinAmount);
        },
        isAmountToReceiveValid: function() {
            const amountToReceive = new Decimal(this.amountToReceive || 0);

            return !amountToReceive.isZero();
        },
        buttonDisabled: function() {
            return this.insufficientFunds
                || !this.isAmountValid
                || !parseFloat(this.amount)
                // In token buy mode we show a modal for extra donation
                || (!this.isDonationMode && this.amountExceedsOrders)
                || this.isCheckingHasError
                || this.isCheckingTrade
                || this.isCheckingTradeReversed
                || this.isTradeInProgress
                || this.serviceUnavailable
            ;
        },
        disabledServices: function() {
            return JSON.parse(this.disabledServicesConfig);
        },
        selectedBase: function() {
            return this.isBuyMode ? this.topCurrency : this.bottomCurrency;
        },
        baseAmount: function() {
            return this.isBuyMode ? this.amount : this.amountToReceive;
        },
        baseAmountReversed: function() {
            return this.isBuyMode ? this.amountToReceive : this.amount;
        },
        shouldTruncateTop: function() {
            return 10 < this.rebrandedTop.length;
        },
        shouldTruncateBottom: function() {
            return 10 < this.rebrandedBottom.length;
        },
        tradeModeLabel() {
            return this.isBuyMode
                ? this.isToken ? this.$t('quick_trade.buy_from_creator') : this.$t('quick_trade.you_buy')
                : this.$t('quick_trade.you_sell');
        },
        tradeModeSecondLabel() {
            return this.isBuyMode && this.isToken
                ? this.$t('quick_trade.and_get')
                : this.$t('quick_trade.you_get');
        },
        quickTradeRate: function() {
            return (this.getRates[this.topCurrency] || [])[USD.symbol] || 1;
        },
        currentMarketRate: function() {
            return (this.getRates[this.currentMarket.base.symbol] || [])[USD.symbol] || 1;
        },
    },
    created() {
        // non-reactive data (constants accessible from template)
        this.BUY_MODE = BUY_MODE;
        this.SELL_MODE = SELL_MODE;

        this.setDefaultCurrencies();
    },
    mounted() {
        if (null !== window.localStorage.getItem('mintme_loggedin_from_quick_trade')) {
            this.topCurrency = window.localStorage.getItem('mintme_quick_trade_currency');
            this.$nextTick(() => {
                const amount = window.localStorage.getItem('mintme_quick_trade_amount');
                this.amount = '0' === amount ? '' : amount;
                window.localStorage.removeItem('mintme_quick_trade_amount');
            });

            window.localStorage.removeItem('mintme_loggedin_from_quick_trade');
            window.localStorage.removeItem('mintme_quick_trade_currency');
        }

        this.$root.$on('market-changed', (market) => {
            return this.isBuyMode
                ? this.topCurrency = market.base.symbol
                : this.bottomCurrency = market.base.symbol;
        });

        if (this.loggedIn) {
            this.sendMessage(JSON.stringify({
                method: 'order.subscribe',
                params: [this.currentMarket.identifier],
                id: parseInt(Math.random().toString().replace('0.', '')),
            }));

            this.addMessageHandler((response) => {
                if (!this.ordersUpdated && 'order.update' === response.method) {
                    this.ordersUpdated = true;
                }
            }, null, 'QuickTrade');
        }

        this.debouncedCheck = debounce(this.checkTrade, 500);
        this.debouncedCheckReversed = debounce(this.checkTradeReversed, 500);
    },
    methods: {
        goToTrade: function() {
            this.$emit('go-to-trade');
        },
        getDepositUrl: function(currency) {
            const depositUrl = this.$routing.generate('wallet', {
                depositMore: this.rebrandingFunc(currency),
            });

            return this.isBuyMode
                ? this.$sanitize(this.$t('quick_trade.deposit', {depositUrl}))
                : this.$sanitize(this.$t('quick_trade.add_more_funds', {depositUrl}));
        },
        canDeposit: function(currency) {
            const cryptoMarkets = this.isToken
                ? Object.keys(this.enabledCryptosMap || {})
                : this.availableMarketsOptions;
            const isTokenCurrency = this.currentMarket.quote.symbol === currency;

            return cryptoMarkets.includes(currency)
                || (isTokenCurrency && (!this.isToken || this.deploymentStatus === tokenDeploymentStatus.deployed));
        },
        setDefaultCurrencies: function() {
            if (this.isBuyMode) {
                this.topCurrency = this.currentMarket.base.symbol;
                this.bottomCurrency = this.currentMarket.quote.symbol;
            } else {
                this.topCurrency = this.currentMarket.quote.symbol;
                this.bottomCurrency = this.currentMarket.base.symbol;
            }
        },
        toggleTradeMode: function() {
            this.setTradeMode(this.isBuyMode ? SELL_MODE : BUY_MODE);
        },
        setTradeMode: function(mode) {
            this.showForms = false;
            this.firstInteraction = false;
            this.firstCheck = false;

            this.tradeMode = mode;

            const oldTopCurrency = this.topCurrency;

            this.topCurrency = this.topOptions.includes(this.bottomCurrency)
                ? this.bottomCurrency
                : this.topOptions[0];

            this.bottomCurrency = this.bottomOptions.includes(oldTopCurrency)
                ? oldTopCurrency
                : this.bottomOptions[0];

            this.resetAmount();
        },
        checkAmountInput: function() {
            this.firstInteraction = true;

            return this.checkInput(this.topSubunit);
        },
        checkAmountReversedInput: function() {
            this.firstInteraction = true;

            return this.checkInput(this.bottomSubunit);
        },
        onKeyup: function() {
            this.debouncedCheck();
        },
        onKeyupReversed: function() {
            this.debouncedCheckReversed();
        },
        checkTrade: function() {
            if (!this.isAmountValid) {
                this.amountToReceive = '';
            }
            if (!this.isAmountValid || this.serviceUnavailable) {
                return;
            }

            this.isCheckingTrade = true;
            this.isCheckingTradeReversed = false;
            this.isCheckingHasError = false;
            this.left = 0;

            this.$axios.retry.get(this.$routing.generate('check_quick_trade', {
                base: this.selectedBase,
                quote: this.currentMarket.quote.symbol,
                mode: this.tradeMode,
                amount: this.amount,
            }))
                .then((res) => {
                    this.amountToReceive = toMoney(res.data.amountToReceive || 0, this.bottomSubunit);
                    this.worth = res.data.worth;
                    this.ordersSummary = res.data.ordersSummary;
                    this.ordersUpdated = false;
                    this.topTilde = ' ';
                    this.bottomTilde = '~';
                })
                .catch((error) => {
                    if (HTTP_BAD_REQUEST !== error.response.status) {
                        this.$logger.error(
                            'Can not to calculate approximate amount of tokens.',
                            error,
                        );
                    }

                    if (error.response?.data?.message) {
                        this.notifyError(error.response.data.message);
                        this.isCheckingHasError = true;
                    }
                })
                .then(() => {
                    this.firstCheck = true;
                    this.isCheckingTrade = false;
                });
        },
        checkTradeReversed: async function() {
            if (!this.isAmountToReceiveValid) {
                this.amount = '';
                return;
            }

            this.isCheckingTrade = false;
            this.isCheckingTradeReversed = true;
            this.isCheckingHasError = false;
            this.worth = -1;

            try {
                const response = await this.$axios.retry.get(this.$routing.generate('check_quick_trade_reversed', {
                    base: this.selectedBase,
                    quote: this.currentMarket.quote.symbol,
                    mode: this.tradeMode,
                    amountToReceive: this.amountToReceive,
                }));

                this.amount = toMoney(response.data.amount || 0, this.topSubunit);
                this.left = response.data.left;
                this.worth = response.data.amount || 0;
                this.ordersSummary = response.data.ordersSummary;
                this.ordersUpdated = false;
                this.topTilde = '~';
                this.bottomTilde = ' ';
            } catch (error) {
                if (HTTP_BAD_REQUEST !== error.response.status) {
                    this.$logger.error(
                        'Can not to calculate approximate amount of tokens.',
                        error,
                    );
                }

                if (error.response?.data?.message) {
                    this.notifyError(error.response.data.message);
                    this.isCheckingHasError = true;
                }
            } finally {
                this.firstCheck = true;
                this.isCheckingTradeReversed = false;
            }
        },
        makeTrade: function() {
            if (this.buttonDisabled) {
                return;
            }

            this.isTradeInProgress = true;
            this.shouldShowConfirmModal = false;

            this.$axios.single.post(this.$routing.generate('make_quick_trade', {
                base: this.selectedBase,
                quote: this.currentMarket.quote.symbol,
                mode: this.tradeMode,
            }), {
                amount: this.amount,
                expected_count_to_receive: this.amountToReceive,
            })
                .then((response) => {
                    if (
                        response.data.hasOwnProperty('message') &&
                        response.data.hasOwnProperty('notified') &&
                        'token.not_deployed_response' === response.data.message
                    ) {
                        this.showTokenNotDeployedNotification(this.currentMarket.quote.symbol, response.data.notified);

                        return;
                    }

                    if (
                        response.data.hasOwnProperty('error') &&
                        response.data.hasOwnProperty('type')
                    ) {
                        this.addPhoneModalMessageType = response.data.type;
                        this.addPhoneModalVisible = true;
                        return;
                    }

                    this.notifySuccess(this.$t('quick_trade.successfully_made', this.translationsContext));
                    this.resetAmount();
                })
                .catch((error) => {
                    if (error.response.data.message) {
                        if (error.response.data.availabilityChanged) {
                            this.notifyError(error.response.data.message, 4000);
                            this.debouncedCheck();
                        } else {
                            this.notifyError(error.response.data.message);
                        }
                    } else {
                        this.notifyError('An error has occurred, please try again later.');
                    }
                    this.$logger.error('Can not make donation.', error);
                })
                .then(() => this.isTradeInProgress = false);
        },
        all: function() {
            this.amount = toMoney(this.balance, this.topSubunit);
            this.checkTrade();
        },
        resetAmount: function() {
            this.amount = '';
            this.amountToReceive = '';
        },
        showConfirmationModal: function() {
            if (
                this.disabledServices.allServicesDisabled ||
                this.disabledServices.newTradesDisabled ||
                this.disabledServices.tradingDisabled
            ) {
                this.notifyError(this.$t('donate.disabled'));

                return;
            }

            if (!this.loggedIn) {
                if (window.history.replaceState) {
                    // prevents browser from storing history with each change:
                    this.updateBrowserHistory('signup');
                }
                this.showForms = true;

                return;
            }

            if (this.ordersUpdated) {
                this.notifyError(this.$t('quick_trade.availability_changed'), 4000);
                this.ordersUpdated = false;
                this.debouncedCheck();
                return;
            }

            if (this.isDonationMode && this.amountExceedsOrders) {
                this.shouldShowConfirmModal = true;
            } else {
                this.makeTrade();
            }
        },
        cancelTrade: function() {
            this.shouldShowConfirmModal = false;
            this.resetAmount();
        },
        updateBrowserHistory(modal = null) {
            const routeParams = this.isToken
                ? {
                    name: this.currentMarket.quote.symbol,
                    crypto: this.rebrandingFunc(this.currentMarket.base.symbol),
                    tab: this.tab,
                }
                : {base: this.currentMarket.base.symbol};

            if (modal) {
                routeParams.modal = modal;
            }

            window.history.replaceState(
                {}, document.title, this.$routing.generate(this.routeName, routeParams)
            );
        },
        onLogin() {
            window.localStorage.setItem('mintme_quick_trade_currency', this.topCurrency);
            window.localStorage.setItem('mintme_quick_trade_amount', this.amount);
            window.localStorage.setItem('mintme_loggedin_from_quick_trade', true);
            this.updateBrowserHistory();
        },
        onSignup() {
            window.localStorage.setItem('mintme_quick_trade_currency', this.topCurrency);
            window.localStorage.setItem('mintme_signedup_from_quick_trade', true);
            this.updateBrowserHistory();
        },
        getCoinAvatar(symbol) {
            return (this.currentMarket.quote.symbol === symbol && webSymbol !== symbol)
                ? this.currentMarket.quote.image
                : null;
        },
        updateCurrency(option) {
            this.topCurrency = option;
            this.resetAmount();
        },
        tooltipConfig: function(rebranded) {
            return {
                title: rebranded,
                boundary: 'window',
                customClass: 'tooltip-custom',
            };
        },
        onPhoneVerified() {
            this.addPhoneModalVisible = false;
            this.showConfirmationModal();
        },
        currencyConvert: function(val, rate, subunit) {
            return this.currencyConversion(
                removeSpaces(val),
                rate,
                usdSign,
                subunit,
                this.currentMarket.base.subunit,
                true

            );
        },
    },
    watch: {
        topCurrency: function() {
            this.debouncedCheck();
        },
        bottomCurrency: function() {
            this.debouncedCheck();
        },
    },
};
</script>
