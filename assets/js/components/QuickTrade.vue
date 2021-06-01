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
                                {{ $t('donation.buy') }}
                            </a>
                        </li>
                        <li class="nav-item">
                            <a
                            class="nav-link"
                            :class="{'active': isSellMode}"
                            href="#"
                            @click.prevent="setTradeMode(SELL_MODE)"
                            >
                                {{ $t('donation.sell') }}
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
                            class="col-xl-5"
                            :class="isCurrencySelected && loggedIn ? 'col-lg-8' : 'col-lg-12'"
                        >
                            <div class="d-sm-flex">
                                <b-dropdown
                                    v-if="isBuyMode"
                                    id="donation_currency"
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
                                        v-model="amountToDonate"
                                        input-id="amount-to-donate"
                                        @keypress="checkAmountInput"
                                        @paste="checkAmountInput"
                                        @keyup="onKeyup"
                                        :from="selectedCurrency"
                                        :to="USD.symbol"
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
                                            {{ $t('donation.button_all') }}
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
                                                {{ $t('donation.buy') }}
                                            </template>
                                            <template v-if="isSellMode">
                                                {{ $t('donation.sell') }}
                                            </template>
                                        </span>
                                    </button>
                                    <confirm-modal
                                        :visible="showModal"
                                        :show-image="false"
                                        @confirm="makeDonation"
                                        @cancel="cancelDonation"
                                        @close="showModal = false">
                                        <p class="text-white modal-title pt-2 pb-4">
                                            {{ $t('donation.modal.1') }}
                                            <br>
                                            {{ $t('donation.modal.2', translationsContext) }}
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
                                <div
                                    v-if="insufficientFundsError"
                                    class="mt-1 text-danger">
                                    {{
                                        $t('donation.min_amount', {
                                          donationCurrency:donationCurrency,
                                          currencyMinAmount:currencyMinAmount
                                        })
                                    }}
                                </div>
                                <p class="m-0 mt-1">
                                    {{ $t('donation.receive') }}
                                    <font-awesome-icon
                                        v-if="donationChecking"
                                        icon="circle-notch"
                                        spin
                                        class="loading-spinner"
                                        fixed-width
                                    />
                                    <span v-else class="text-nowrap">
                                        {{ amountToReceive }} tokens
                                        <guide
                                            :placement="'right-start'"
                                            :max-width="'200px'"
                                        >
                                            <template slot="body">
                                                {{ $t('donation.diff_number') }}
                                            </template>
                                        </guide>
                                    </span>
                                </p>
                            </div>
                        </div>
                        <div
                            v-if="isCurrencySelected && loggedIn"
                            class="col-lg-4 col-xl-auto col-donation-balance mt-3 mt-lg-0 pl-lg-0"
                            id="show-balance"
                        >
                            <p class="m-0">
                                <span>
                                    {{ $t('donation.balance') }}
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
                                <span class="d-block text-danger font-size-90">
                                    {{ $t('donation.insufficient_funds') }}
                                </span>
                                <span class="d-block">
                                    {{ $t('donation.make') }}
                                    <a :href="getDepositLink">{{ $t('donation.deposit') }}</a>
                                    {{ $t('donation.first') }}
                                </span>
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
    HTTP_BAD_REQUEST,
    BTC,
    MINTME,
    USD,
    ETH,
    USDC,
    digitsLimits,
    currencyModes,
} from '../utils/constants';
import PriceConverterInput from './PriceConverterInput';

library.add(faCircleNotch);

const BUY_MODE = 1;
const SELL_MODE = 2;

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
        donationParams: Object,
        disabledServicesConfig: String,
        profileNickname: String,
        isToken: Boolean,
    },
    data() {
        return {
            options: {
                webSymbol,
                btcSymbol,
                ethSymbol,
                usdcSymbol,
            },
            currencyModes,
            selectedCurrency: null,
            amountToDonate: 0,
            amountToReceive: 0,
            tokensWorth: 0,
            sellOrdersSummary: 0,
            donationChecking: false,
            balanceLoaded: false,
            balance: 0,
            donationInProgress: false,
            showModal: false,
            tokensAvailabilityChanged: false,
            USD,
            showForms: false,
            addPhoneModalMessageType: 'donation',
            addPhoneModalProfileNickName: this.profileNickname,
            tradeMode: BUY_MODE,
        };
    },
    computed: {
        isBuyMode: function() {
            return this.tradeMode === BUY_MODE;
        },
        isSellMode: function() {
            return this.tradeMode === SELL_MODE;
        },
        currencyMode: function() {
            return localStorage.getItem('_currency_mode');
        },
        translationsContext: function() {
          return {
            amountToDonate: this.amountToDonate + ' ' + this.donationCurrency,
            amountToReceive: this.amountToReceive + ' ' + this.market.quote.name,
            worth: formatMoney(toMoney(this.tokensWorth, this.currencySubunit)),
          };
        },
        donationCurrency: function() {
            return this.rebrandingFunc(this.selectedCurrency);
        },
        getDepositLink: function() {
            return this.$routing.generate('wallet', {
                depositMore: this.donationCurrency,
            });
        },
        isCurrencySelected: function() {
            return Object.values(this.options).includes(this.selectedCurrency);
        },
        dropdownText: function() {
            return this.isCurrencySelected
                ? this.donationCurrency
                : this.$t('donation.currency.select');
        },
        currencySubunit: function() {
            return ({BTC, MINTME, ETH, USDC}[this.selectedCurrency] || MINTME).subunit;
        },
        currencyMinAmount: function() {
            switch (this.selectedCurrency) {
              case btcSymbol:
                return this.donationParams.minBtcAmount;
              case ethSymbol:
                return this.donationParams.minEthAmount;
              case usdcSymbol:
                return this.donationParams.minUsdcAmount;
              default:
                return this.donationParams.minMintmeAmount;
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
                    (this.amountToDonate > 0 && (new Decimal(this.amountToDonate)).greaterThan(this.balance))
                );
        },
        insufficientFundsError: function() {
            return this.loggedIn && this.balanceLoaded && !this.isAmountValid && !this.insufficientFunds;
        },
        isAmountValid: function() {
            return !!parseFloat(this.amountToDonate)
                && (new Decimal(this.amountToDonate)).greaterThanOrEqualTo(this.currencyMinAmount);
        },
        buttonDisabled: function() {
            return (this.loggedIn &&
                (this.insufficientFunds || this.insufficientFundsError || !parseFloat(this.balance)))
                || !this.isCurrencySelected
                || !parseFloat(this.amountToDonate)
                || this.donationChecking
                || this.donationInProgress;
        },
        nonrefundHtml: function() {
            return this.$t('donation.nonrefund', {
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
        // non-reactive data (constants)
        this.BUY_MODE = BUY_MODE;
        this.SELL_MODE = SELL_MODE;
    },
    mounted() {
        if (window.localStorage.getItem('mintme_loggedin_from_donation') !== null) {
            this.selectedCurrency = window.localStorage.getItem('mintme_donation_currency');
            this.$nextTick(() => {
                this.amountToDonate = window.localStorage.getItem('mintme_donation_amount');
                window.localStorage.removeItem('mintme_donation_amount');
            });

            window.localStorage.removeItem('mintme_loggedin_from_donation');
            window.localStorage.removeItem('mintme_donation_currency');
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
            }, null, 'Donation');
        }

        this.selectedCurrency = webSymbol;
        this.debouncedCheck = debounce(this.checkDonation, 500);
    },
    methods: {
        setTradeMode: function(mode) {
            this.tradeMode = mode;
        },
        onSelect: function(newCurrency) {
            if (this.selectedCurrency !== newCurrency) {
                this.balanceLoaded = false;
                this.selectedCurrency = newCurrency;
            }
        },
        getTokenBalance: function() {
            this.$axios.retry.get(this.$routing.generate('crypto_balance', {symbol: this.selectedCurrency}))
                .then((res) => {
                    this.balance = res.data;
                    this.balanceLoaded = true;
                })
                .catch((error) => {
                    this.sendLogs('error', 'Can not load crypto balance.', error);
                });
        },
        checkAmountInput: function() {
            return this.checkInput(this.currencySubunit, digitsLimits[this.selectedCurrency]);
        },
        onKeyup: function() {
            this.debouncedCheck();
        },
        checkDonation: function() {
            if (!this.isAmountValid) {
                return;
            }

            this.donationChecking = true;

            this.$axios.retry.get(this.$routing.generate('check_donation', {
                base: this.market.base.symbol,
                quote: this.market.quote.symbol,
                currency: this.selectedCurrency,
                amount: this.amountToDonate,
            }))
                .then((res) => {
                    this.amountToReceive = res.data.amountToReceive;
                    this.tokensWorth = res.data.tokensWorth;
                    this.sellOrdersSummary = res.data.sellOrdersSummary;
                })
                .catch((error) => {
                    this.sendLogs('error', 'Can not to calculate approximate amount of tokens.', error);
                })
                .then(() => this.donationChecking = false);
        },
        makeDonation: function() {
            this.donationInProgress = true;
            this.showModal = false;

            this.$axios.single.post(this.$routing.generate('make_donation', {
                base: this.market.base.symbol,
                quote: this.market.quote.symbol,
            }), {
                currency: this.selectedCurrency,
                amount: this.amountToDonate,
                expected_count_to_receive: this.amountToReceive,
            })
                .then((response) => {
                    if (
                        response.data.hasOwnProperty('error') &&
                        response.data.hasOwnProperty('type')
                    ) {
                        this.errorType = response.data.type;
                        this.addPhoneModalVisible = true;
                        return;
                    }
                    this.notifySuccess(
                        this.$t('donation.successfully_made', {
                            amount: this.amountToReceive,
                        })
                    );

                    this.resetAmount();
                    this.balanceLoaded = false;
                    this.getTokenBalance();
                })
                .catch((error) => {
                    if (HTTP_BAD_REQUEST === error.response.status && error.response.data.message) {
                        this.notifyError(error.response.data.message);

                        if ('Tokens availability changed. Please adjust donation amount.' ===
                            error.response.data.message
                        ) {
                            location.reload();
                        }
                    } else if (error.response.data.message) {
                        this.notifyError(error.response.data.message);
                    } else {
                        this.notifyError('An error has occurred, please try again later.');
                    }
                    this.sendLogs('error', 'Can not make donation.', error);
                })
                .then(() => this.donationInProgress = false);
        },
        all: function() {
            this.amountToDonate = toMoney(this.balance, this.currencySubunit);
            if (!this.insufficientFunds) {
                this.checkDonation();
            }
        },
        resetAmount: function() {
            this.amountToDonate = 0;
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
                this.notifyError(this.$t('donation.tokens_availability_changed'));
                this.tokensAvailabilityChanged = false;
                location.reload();
                return;
            }

            if ((new Decimal(this.amountToDonate)).greaterThan(this.sellOrdersSummary)) {
                this.showModal = true;
            } else {
                this.makeDonation();
            }
        },
        cancelDonation: function() {
            this.showModal = false;
            this.resetAmount();
        },
        onLogin() {
            window.localStorage.setItem('mintme_donation_currency', this.selectedCurrency);
            window.localStorage.setItem('mintme_donation_amount', this.amountToDonate);
            window.localStorage.setItem('mintme_loggedin_from_donation', true);
        },
        onSignup() {
            window.localStorage.setItem('mintme_donation_currency', this.selectedCurrency);
            window.localStorage.setItem('mintme_signedup_from_donation', true);
        },
    },
    watch: {
        selectedCurrency: function() {
            if (this.isCurrencySelected) {
                this.getTokenBalance();
                this.resetAmount();
            }
        },
        amountToDonate: function() {
            if (!parseFloat(this.amountToDonate)) {
                this.amountToReceive = 0;
            }
        },
    },
};
</script>
