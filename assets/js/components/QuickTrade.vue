<template>
    <div v-if="!disabledServices.allServicesDisabled && !disabledServices.tradingDisabled">
        <div class="card h-100">
                <div class="card-header">
                    <ul class="nav quick-trade-nav">
                        <li class="nav-item">
                            <a
                                class="nav-link"
                                :class="{'active': isBuyMode}"
                                href="#"
                                @click.prevent="setTradeMode(BUY_MODE)"
                            >
                                {{ $t('buy') }}
                            </a>
                        </li>
                        <li class="nav-item">
                            <a
                                class="nav-link"
                                :class="{'active': isSellMode}"
                                href="#"
                                @click.prevent="setTradeMode(SELL_MODE)"
                            >
                                {{ $t('sell') }}
                            </a>
                        </li>
                        <guide v-if="isToken" class="ml-auto">
                            <template slot="body">
                                <span v-html="$sanitize(nonrefundHtml)"></span>
                            </template>
                        </guide>
                    </ul>
                </div>
                <div class="card-body">
                    <div v-show="!showForms" class="row">
                        <div
                            :class="isCurrencySelected && loggedIn ? 'col-lg-8' : 'col-lg-12'"
                        >
                            <div class="d-sm-flex">
                                <b-dropdown
                                    v-show="isBuyMode"
                                    id="quick_trade_currency"
                                    :text="dropdownText"
                                    variant="primary"
                                    class="mr-2"
                                >
                                    <b-dropdown-item
                                        v-for="option in options"
                                        :key="option"
                                        :value="option"
                                        @click="onSelect(option)"
                                    >
                                        {{ option | rebranding }}
                                    </b-dropdown-item>
                                </b-dropdown>
                                <div class="input-group flex-nowrap my-3 my-sm-0">
                                    <price-converter-input
                                        class="d-block flex-grow-1"
                                        v-model="amount"
                                        input-id="amount-to-donate"
                                        @keypress="checkAmountInput"
                                        @paste="checkAmountInput"
                                        @keyup="onKeyup"
                                        :from="selectedCurrency"
                                        :to="currencies.USD.symbol"
                                        :subunit="4"
                                        symbol="$"
                                        :show-converter="currencyMode === currencyModes.usd.value"
                                    />
                                    <div v-if="loggedIn" class="input-group-append">
                                        <button
                                            @click="all"
                                            class="btn btn-primary all-button"
                                            type="button"
                                        >
                                            {{ $t('quick_trade.button_all') }}
                                        </button>
                                    </div>
                                </div>
                                <div>
                                    <button
                                        :disabled="buttonDisabled"
                                        @click="showConfirmationModal"
                                        class="btn btn-primary btn-donate ml-sm-2"
                                    >
                                        <span :class="{'text-muted': disabledServices.newTradesDisabled}">
                                            <template v-if="isBuyMode">
                                                {{ $t('buy') }}
                                            </template>
                                            <template v-if="isSellMode">
                                                {{ $t('sell') }}
                                            </template>
                                        </span>
                                    </button>
                                    <confirm-modal
                                        :visible="showModal"
                                        :show-image="false"
                                        @confirm="makeTrade"
                                        @cancel="cancelTrade"
                                        @close="showModal = false">
                                        <p class="text-white modal-title pt-2 pb-4">
                                            {{ $t('quick_trade.donation.modal.1') }}
                                            <br>
                                            {{ $t('quick_trade.donation.modal.2', translationsContext) }}
                                        </p>
                                        <template v-slot:confirm>
                                            {{ $t('confirm_modal.continue') }}
                                        </template>
                                    </confirm-modal>
                                    <add-phone-alert-modal
                                        :visible="addPhoneModalVisible"
                                        :message="addPhoneModalMessage"
                                        @close="addPhoneModalVisible = false"
                                    />
                                </div>
                            </div>
                            <div class="mt-1">
                                <template v-if="firstInteraction">
                                    <div
                                        v-if="!isAmountValid"
                                        class="mt-1 text-danger">
                                        {{ $t('quick_trade.min_amount', translationsContext) }}
                                    </div>
                                    <div
                                        v-if="sellAmountExceeds"
                                        class="mt-1 text-danger"
                                    >
                                        <template v-if="isOrdersSummaryZero">
                                            {{ $t('quick_trade.order.empty') }}
                                        </template>
                                        <template v-else>
                                            {{ $t('quick_trade.sell_exceeds', translationsContext) }}
                                        </template>
                                    </div>
                                </template>
                                <p class="m-0 mt-1">
                                    {{ $t('quick_trade.receive') }}
                                    <font-awesome-icon
                                        v-if="isCheckingTrade"
                                        icon="circle-notch"
                                        spin
                                        class="loading-spinner"
                                        fixed-width
                                    />
                                    <span v-else class="text-nowrap">
                                        {{ amountToReceive | toMoney(assetToReceiveSubunit) }}
                                        {{ assetToReceive | rebranding }}
                                        <guide
                                            :placement="'right-start'"
                                            :max-width="'200px'"
                                        >
                                            <template slot="body">
                                                {{ $t('quick_trade.diff_number') }}
                                            </template>
                                        </guide>
                                    </span>
                                </p>
                            </div>
                        </div>
                        <div
                            v-if="isCurrencySelected && loggedIn"
                            id="show-balance"
                            class="col-lg-4 col-xl-auto col-donation-balance mt-3 mt-lg-0 pl-lg-0"
                        >
                            <p class="m-0">
                                <span>
                                    {{ $t('quick_trade.balance') }}
                                </span>
                                <span v-if="balanceLoaded">
                                    {{ balance | toMoney(currencySubunit) | formatMoney }}
                                </span>
                                <font-awesome-icon
                                    v-else
                                    icon="circle-notch"
                                    spin
                                    class="loading-spinner" fixed-width
                                />
                            </p>
                            <div v-if="insufficientFunds">
                                <div class="text-danger font-size-90">
                                    {{ $t('quick_trade.insufficient_funds') }}
                                </div>
                                <div v-if="shouldShowDepositMore" v-html="$sanitize(makeDepositHtml)"></div>
                            </div>
                        </div>
                    </div>
                    <div v-if="!loggedIn" class="d-flex justify-content-center">
                        <login-signup-switcher
                            v-show="showForms"
                            :google-recaptcha-site-key="googleRecaptchaSiteKey"
                            @login="onLogin"
                            @signup="onSignup"
                        />
                    </div>
                </div>
        </div>
    </div>
    <div v-else>
        <div class="h1 text-center pt-5 mt-5">
          {{ $t('donate.page.disabled') }}
        </div>
    </div>
</template>

<script>
import {library} from '@fortawesome/fontawesome-svg-core';
import {faCircleNotch} from '@fortawesome/free-solid-svg-icons';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import debounce from 'lodash/debounce';
import {BDropdown, BDropdownItem} from 'bootstrap-vue';
import {mapGetters} from 'vuex';
import Decimal from 'decimal.js';
import {
    CheckInputMixin,
    MoneyFilterMixin,
    NotificationMixin,
    LoggerMixin,
    RebrandingFilterMixin,
    WebSocketMixin,
    AddPhoneAlertMixin,
} from '../mixins';
import ConfirmModal from './modal/ConfirmModal';
import AddPhoneAlertModal from './modal/AddPhoneAlertModal';
import Guide from './Guide';
import {formatMoney, toMoney} from '../utils';
import {
    webSymbol,
    btcSymbol,
    ethSymbol,
    usdcSymbol,
    bnbSymbol,
    currencies,
    digitsLimits,
    currencyModes,
    tokenDeploymentStatus,
} from '../utils/constants';
import PriceConverterInput from './PriceConverterInput';

library.add(faCircleNotch);

const BUY_MODE = 'buy';
const SELL_MODE = 'sell';

export default {
    name: 'QuickTrade',
    components: {
        BDropdown,
        BDropdownItem,
        PriceConverterInput,
        Guide,
        ConfirmModal,
        LoginSignupSwitcher: () => import('./LoginSignupSwitcher').then((data) => data.default),
        AddPhoneAlertModal,
        FontAwesomeIcon,
    },
    mixins: [
        CheckInputMixin,
        MoneyFilterMixin,
        NotificationMixin,
        LoggerMixin,
        RebrandingFilterMixin,
        WebSocketMixin,
        AddPhoneAlertMixin,
    ],
    props: {
        market: Object,
        loggedIn: Boolean,
        googleRecaptchaSiteKey: String,
        params: Object,
        disabledServicesConfig: String,
        profileNickname: String,
        isToken: Boolean,
        deploymentStatus: {
            type: String,
            default: null,
        },
    },
    data() {
        return {
            options: {
                webSymbol,
                btcSymbol,
                ethSymbol,
                usdcSymbol,
                bnbSymbol,
            },
            currencyModes,
            selectedCurrency: null,
            amount: 0,
            amountToReceive: 0,
            worth: 0,
            ordersSummary: 0,
            isCheckingTrade: false,
            isTradeInProgress: false,
            showModal: false,
            tokensAvailabilityChanged: false,
            showForms: false,
            firstInteraction: false,
            addPhoneModalMessageType: 'action',
            addPhoneModalProfileNickName: this.profileNickname,
            tradeMode: BUY_MODE,
        };
    },
    computed: {
        ...mapGetters('tradeBalance', {
            balances: 'getBalances',
            hasQuoteRelation: 'hasQuoteRelation',
        }),
        balance: function() {
            return this.balances
                ? this.balances[this.selectedCurrency].available
                : null;
        },
        balanceLoaded: function() {
            return null !== this.balance;
        },
        assetToReceive: function() {
             return this.isBuyMode
                ? this.market.quote.symbol
                : this.market.base.symbol;
        },
        isBuyMode: function() {
            return BUY_MODE === this.tradeMode;
        },
        isSellMode: function() {
            return SELL_MODE === this.tradeMode;
        },
        isOrdersSummaryZero: function() {
            const summary = new Decimal(this.ordersSummary);

            return summary.isZero();
        },
        currencyMode: function() {
            return localStorage.getItem('_currency_mode');
        },
        translationsContext: function() {
          return {
            amount: toMoney(this.amount || 0, this.currencySubunit),
            amountToReceive: toMoney(this.amountToReceive, this.assetToReceiveSubunit),
            assetToReceive: this.rebrandingFunc(this.assetToReceive),
            worth: formatMoney(toMoney(this.worth, currencies.WEB.subunit)),
            ordersSummary: toMoney(this.ordersSummary, this.assetToReceiveSubunit),
            currency: this.rebrandedCurrency,
            currencyMinAmount: this.currencyMinAmount,
          };
        },
        rebrandedCurrency: function() {
            return this.rebrandingFunc(this.selectedCurrency);
        },
        isCurrencySelected: function() {
            return this.isBuyMode
                ? Object.values(this.options).includes(this.selectedCurrency)
                : this.selectedCurrency === this.market.quote.symbol;
        },
        dropdownText: function() {
            return this.isCurrencySelected
                ? this.rebrandedCurrency
                : this.$t('quick_trade.currency.select');
        },
        currencySubunit: function() {
            const symbol = currencies[this.selectedCurrency];

            return symbol ? symbol.subunit : currencies.WEB.subunit;
        },
        assetToReceiveSubunit: function() {
            const symbol = currencies[this.assetToReceive];

            return symbol ? symbol.subunit : currencies.WEB.subunit;
        },
        currencyMinAmount: function() {
            switch (this.selectedCurrency) {
              case btcSymbol:
                return this.params.minBtcAmount;
              case ethSymbol:
                return this.params.minEthAmount;
              case usdcSymbol:
                return this.params.minUsdcAmount;
              default:
                return this.params.minMintmeAmount;
            }
        },
        minTotalPrice: function() {
            return toMoney('1e-' + this.currencySubunit, this.currencySubunit);
        },
        insufficientFunds: function() {
            return this.loggedIn && this.balanceLoaded &&
                (
                    (new Decimal(this.balance)).lessThan(this.minTotalPrice)
                    ||
                    (this.amount > 0 && (new Decimal(this.amount)).greaterThan(this.balance))
                );
        },
        sellAmountExceeds: function() {
            const amount = new Decimal(this.amount || 0);

            return this.isSellMode
                && !this.isCheckingTrade
                && !amount.isZero()
                && amount.greaterThan(this.ordersSummary);
        },
        isAmountValid: function() {
            const amount = new Decimal(this.amount || 0);

            return !amount.isZero()
                && amount.greaterThanOrEqualTo(this.currencyMinAmount);
        },
        buttonDisabled: function() {
            return this.insufficientFunds
                || !this.isAmountValid
                || !this.isCurrencySelected
                || !parseFloat(this.amount)
                || this.sellAmountExceeds
                || this.isCheckingTrade
                || this.isTradeInProgress;
        },
        shouldShowDepositMore: function() {
            return this.isBuyMode
                || !this.isToken
                || (this.deploymentStatus === tokenDeploymentStatus.deployed && this.hasQuoteRelation);
        },
        makeDepositHtml: function() {
            const depositUrl = this.$routing.generate('wallet', {
                depositMore: this.rebrandedCurrency,
            });

            return this.$t('quick_trade.make_deposit', {depositUrl});
        },
        nonrefundHtml: function() {
            return this.$t('quick_trade.donation.nonrefund', {
                path: this.$routing.generate('token_show', {
                    name: this.market.quote.name,
                    tab: 'trade',
                }),
            });
        },
        disabledServices: function() {
            return JSON.parse(this.disabledServicesConfig);
        },
    },
    created() {
        // non-reactive data (constants accesible from template)
        this.BUY_MODE = BUY_MODE;
        this.SELL_MODE = SELL_MODE;
        this.currencies = currencies;

        this.selectedCurrency = this.isToken ? webSymbol : this.market.base.symbol;
    },
    mounted() {
        if (window.localStorage.getItem('mintme_loggedin_from_quick_trade') !== null) {
            this.selectedCurrency = window.localStorage.getItem('mintme_quick_trade_currency');
            this.$nextTick(() => {
                this.amount = window.localStorage.getItem('mintme_quick_trade_amount');
                window.localStorage.removeItem('mintme_quick_trade_amount');
            });

            window.localStorage.removeItem('mintme_loggedin_from_quick_trade');
            window.localStorage.removeItem('mintme_quick_trade_currency');
        }

        if (this.loggedIn) {
            this.sendMessage(JSON.stringify({
                method: 'order.subscribe',
                params: [this.market.identifier],
                id: parseInt(Math.random().toString().replace('0.', '')),
            }));

            this.addMessageHandler((response) => {
                if (!this.tokensAvailabilityChanged && 'order.update' === response.method) {
                    this.tokensAvailabilityChanged = true;
                }
            }, null, 'QuickTrade');
        }

        this.debouncedCheck = debounce(this.checkTrade, 500);
    },
    methods: {
        setTradeMode: function(mode) {
            if (mode === this.tradeMode) {
                return;
            }

            this.tradeMode = mode;
            this.showForms = false;
            this.firstInteraction = false;

            if (mode === BUY_MODE) {
                this.onSelect(this.isToken ? webSymbol : this.market.base.symbol);
            } else {
                this.onSelect(this.market.quote.symbol);
            }
        },
        onSelect: function(newCurrency) {
            this.selectedCurrency = newCurrency;
        },
        checkAmountInput: function() {
            const digitLimits = digitsLimits[this.selectedCurrency] || currencies.WEB.digits;

            return this.checkInput(this.currencySubunit, digitLimits);
        },
        onKeyup: function() {
            this.debouncedCheck();
        },
        checkTrade: function() {
            if (!this.isAmountValid) {
                return;
            }

            this.isCheckingTrade = true;

            this.$axios.retry.get(this.$routing.generate('check_quick_trade', {
                base: this.market.base.symbol,
                quote: this.market.quote.symbol,
                mode: this.tradeMode,
                currency: this.selectedCurrency,
                amount: this.amount,
            }))
                .then((res) => {
                    this.amountToReceive = res.data.amountToReceive;
                    this.worth = res.data.worth;
                    this.ordersSummary = res.data.ordersSummary;
                })
                .catch((error) => {
                    this.sendLogs('error', 'Can not to calculate approximate amount of tokens.', error);
                })
                .then(() => {
                    this.firstInteraction = true;
                    this.isCheckingTrade = false;
                });
        },
        makeTrade: function() {
            this.isTradeInProgress = true;
            this.showModal = false;

            this.$axios.single.post(this.$routing.generate('make_quick_trade', {
                base: this.market.base.symbol,
                quote: this.market.quote.symbol,
                mode: this.tradeMode,
            }), {
                currency: this.selectedCurrency,
                amount: this.amount,
                expected_count_to_receive: this.amountToReceive,
            })
                .then((response) => {
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
                        this.notifyError(error.response.data.message);

                        if (error.response.data.reload) {
                            location.reload();
                        }
                    } else {
                        this.notifyError('An error has occurred, please try again later.');
                    }
                    this.sendLogs('error', 'Can not make donation.', error);
                })
                .then(() => this.isTradeInProgress = false);
        },
        all: function() {
            this.amount = toMoney(this.balance, this.currencySubunit);
            this.checkTrade();
        },
        resetAmount: function() {
            this.amount = 0;
            this.amountToReceive = 0;
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
                    window.history.replaceState(
                        {}, document.title, this.$routing.generate('token_show', {
                            name: this.market.quote.symbol,
                            tab: 'intro',
                            modal: 'signup',
                        })
                    );
                }
                this.showForms = true;

                return;
            }

            if (this.tokensAvailabilityChanged) {
                this.notifyError(this.$t('quick_trade.availability_changed'));
                this.tokensAvailabilityChanged = false;
                location.reload();
                return;
            }

            if ((new Decimal(this.amount)).greaterThan(this.ordersSummary)) {
                this.showModal = true;
            } else {
                this.makeTrade();
            }
        },
        cancelTrade: function() {
            this.showModal = false;
            this.resetAmount();
        },
        onLogin() {
            window.localStorage.setItem('mintme_quick_trade_currency', this.selectedCurrency);
            window.localStorage.setItem('mintme_quick_trade_amount', this.amount);
            window.localStorage.setItem('mintme_loggedin_from_quick_trade', true);
        },
        onSignup() {
            window.localStorage.setItem('mintme_quick_trade_currency', this.selectedCurrency);
            window.localStorage.setItem('mintme_signedup_from_quick_trade', true);
        },
    },
    watch: {
        selectedCurrency: function() {
            if (this.isCurrencySelected) {
                this.resetAmount();
            }
        },
        amount: function() {
            if (!parseFloat(this.amount)) {
                this.amountToReceive = 0;
            }
        },
    },
};
</script>
