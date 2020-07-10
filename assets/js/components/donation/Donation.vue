<template>
    <div class="container-fluid px-0">
        <div class="row justify-content-center">
            <div class="width-100 col-9 col-sm-10 col-md-9 col-lg-7 col-xl-6 mt-3">
                <div class="card h-100">
                    <div class="h-100 donation">
                        <div class="donation-header text-left">
                            <span v-if="loggedIn">Donations</span>
                            <span v-else>To make a donation you have to be logged in</span>
                        </div>
                        <div class="card-body donation-body">
                            <div v-if="loggedIn" class="h-100">
                                <div>
                                    <div>
                                        <p class="info">Donation is non-refundable</p>
                                    </div>
                                    <div class="row" v-bind:class="{ 'currency-container': isCurrencySelected }">
                                        <div class="col">
                                            <p class="mb-2">Currency</p>
                                            <b-dropdown
                                                id="donation_currency"
                                                :text="dropdownText"
                                                variant="primary"
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
                                        </div>
                                        <div
                                            v-if="isCurrencySelected"
                                            class="col"
                                        >
                                            <p class="mb-2">Your balance:</p>
                                            <span v-if="balanceLoaded" class="d-block">
                                                {{ balance | toMoney(currencySubunit) | formatMoney }}
                                            </span>
                                            <font-awesome-icon
                                                v-else
                                                icon="circle-notch"
                                                spin
                                                class="loading-spinner" fixed-width
                                            />
                                            <div v-if="insufficientFunds">
                                                <span class="d-block text-danger font-size-90">
                                                    Insufficient funds for donation,
                                                </span>
                                                <span class="d-block">
                                                    please make <a :href="getDepositLink">deposit</a> first
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div
                                        v-if="isCurrencySelected"
                                        class="w-100"
                                    >
                                        <div>
                                            <label for="amount-to-donate">Amount:</label>
                                            <div class="input-group">
                                                <input
                                                    v-model="amountToDonate"
                                                    id="amount-to-donate"
                                                    type="text"
                                                    class="form-control"
                                                    @keypress="checkAmountInput"
                                                    @paste="checkAmountInput"
                                                    @keyup="onKeyup"
                                                >
                                                <div class="input-group-append">
                                                    <button
                                                        @click="all"
                                                        class="btn btn-primary"
                                                        type="button"
                                                    >All</button>
                                                </div>
                                            </div>
                                            <div
                                                v-if="insufficientFundsError"
                                                class="w-100 mt-1 text-danger">
                                                Minimum amount of {{ donationCurrency }} {{ currencyMinAmount }}.
                                            </div>
                                            <p class="mt-2 mb-4 text-nowrap">
                                                You will receive approximately:
                                                <font-awesome-icon
                                                    v-if="donationChecking"
                                                    icon="circle-notch"
                                                    spin
                                                    class="loading-spinner"
                                                    fixed-width
                                                />
                                                <span v-else>{{ amountToReceive }}</span>
                                                <span class="text-nowrap">
                                                    tokens
                                                    <guide
                                                        :placement="'right-start'"
                                                        :max-width="'200px'"
                                                    >
                                                        <template slot="body">
                                                            The number of tokens you will receive may vary slightly, because of other traders' activity.
                                                        </template>
                                                    </guide>
                                                </span>
                                            </p>
                                        </div>
                                        <div>
                                            <p class="mb-2">Fee for donation: {{ donationParams.fee }}%</p>
                                            <button
                                                :disabled="buttonDisabled"
                                                @click="showConfirmationModal"
                                                class="btn btn-primary btn-donate"
                                            >
                                                Donate {{ donationCurrency }}
                                            </button>
                                            <confirm-modal
                                                :visible="showModal"
                                                :show-image="false"
                                                @confirm="makeDonation"
                                                @cancel="cancelDonation"
                                                @close="showModal = false">
                                                <p class="text-white modal-title pt-2 pb-4">
                                                    Amount donated exceeds the worth of tokens available for sell.
                                                    You can continue or cancel and adjust donation amount.
                                                    Alternatively, you could ask token creator to set more sell orders on the market and then retry donation.
                                                    <br />
                                                    Do you want to donate {{ amountToDonate }} {{ donationCurrency }}
                                                    for {{ amountToReceive }} {{ market.quote.name }}
                                                    worth {{ tokensWorth | toMoney(currencySubunit) | formatMoney }} {{ donationCurrency }}?
                                                </p>
                                                <template v-slot:confirm>Continue</template>
                                            </confirm-modal>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div v-if="!loginFormLoaded" class="p-5 text-center">
                                <font-awesome-icon icon="circle-notch" spin class="loading-spinner" fixed-width />
                            </div>
                            <div v-if="!loggedIn" ref="tab-login-form-container"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import debounce from 'lodash/debounce';
import {
    CheckInputMixin,
    MoneyFilterMixin,
    NotificationMixin,
    LoggerMixin,
    RebrandingFilterMixin,
} from '../../mixins';
import ConfirmModal from '../modal/ConfirmModal';
import Guide from '../Guide';
import Decimal from 'decimal.js';
import {toMoney} from '../../utils';
import {webSymbol, btcSymbol, HTTP_BAD_REQUEST, BTC, MINTME} from '../../utils/constants';

export default {
    name: 'Donation',
    mixins: [
        CheckInputMixin,
        MoneyFilterMixin,
        NotificationMixin,
        LoggerMixin,
        RebrandingFilterMixin,
    ],
    components: {
        Guide,
        ConfirmModal,
    },
    props: {
        market: Object,
        loggedIn: Boolean,
        googleRecaptchaSiteKey: String,
        donationParams: Object,
    },
    data() {
        return {
            options: {
                webSymbol,
                btcSymbol,
            },
            selectedCurrency: null,
            loginFormLoaded: false,
            amountToDonate: 0,
            amountToReceive: 0,
            tokensWorth: 0,
            sellOrdersSummary: 0,
            donationChecking: false,
            balanceLoaded: false,
            balance: 0,
            donationInProgress: false,
            showModal: false,
        };
    },
    computed: {
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
                : 'Select currency';
        },
        currencySubunit: function() {
            return btcSymbol === this.selectedCurrency
                ? BTC.subunit
                : MINTME.subunit;
        },
        currencyMinAmount: function() {
            return btcSymbol === this.selectedCurrency
                ? this.donationParams.minBtcAmount
                : this.donationParams.minMintmeAmount;
        },
        minTotalPrice: function() {
            return toMoney('1e-' + this.currencySubunit, this.currencySubunit);
        },
        insufficientFunds: function() {
            return this.balanceLoaded &&
                (
                    (new Decimal(this.balance)).lessThan(this.minTotalPrice)
                    ||
                    (this.amountToDonate > 0 && (new Decimal(this.amountToDonate)).greaterThan(this.balance))
                );
        },
        insufficientFundsError: function() {
            return this.balanceLoaded && !this.isAmountValid && !this.insufficientFunds;
        },
        isAmountValid: function() {
            return !!parseFloat(this.amountToDonate)
                && (new Decimal(this.amountToDonate)).greaterThanOrEqualTo(this.currencyMinAmount);
        },
        buttonDisabled: function() {
            return !this.loggedIn
                || !this.isCurrencySelected
                || this.insufficientFunds
                || !parseFloat(this.balance)
                || !parseFloat(this.amountToDonate)
                || this.donationChecking
                || this.donationInProgress
                || this.insufficientFundsError;
        },
    },
    mounted() {
        if (!this.loggedIn) {
            this.loadLoginForm();
        } else {
            this.loginFormLoaded = true;
        }

        this.debouncedCheck = debounce(this.checkDonation, 500);
    },
    methods: {
        onSelect: function(newCurrency) {
            if (this.selectedCurrency !== newCurrency) {
                this.balanceLoaded = false;
                this.selectedCurrency = newCurrency;
            }
        },
        loadLoginForm: function() {
            this.$axios.retry.get(this.$routing.generate('login', {
                formContentOnly: true,
            }))
                .then((res) => {
                    this.$refs['tab-login-form-container'].innerHTML = res.data;
                    this.loginFormLoaded = true;

                    let captchaContainer = document.querySelector('.g-recaptcha');
                    let googleRecaptchaSiteKey = this.googleRecaptchaSiteKey;
                    grecaptcha.ready(function() {
                        grecaptcha.render(captchaContainer, {
                            'sitekey': googleRecaptchaSiteKey,
                        });
                    });
                })
                .catch((error) => {
                    this.notifyError('Something went wrong. Try to reload the page.');
                    this.sendLogs('error', 'Can not load tab content.', error);
                });
        },
        getTokenBalance: function() {
            this.$axios.retry.get(this.$routing.generate('crypto_balance', {symbol: this.selectedCurrency}))
                .then((res) => {
                    this.balance = res.data;
                    this.balanceLoaded = true;
                })
                .catch((error) => {
                    this.notifyError('Can not load balance. Try again later.');
                    this.sendLogs('error', 'Can not load crypto balance.', error);
                });
        },
        checkAmountInput: function() {
            return this.checkInput(this.currencySubunit);
        },
        onKeyup: function() {
            this.debouncedCheck();
        },
        checkDonation: function() {
            if (this.insufficientFunds || !this.isAmountValid) {
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
                    if (HTTP_BAD_REQUEST === error.response.status && error.response.data.message) {
                        this.notifyError(error.response.data.message);
                    } else {
                        this.notifyError('Can not to calculate amount of tokens. Try again later.');
                    }

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
                    this.notifySuccess(
                        'Congratulations! Donation has been successfully made. '
                        + 'You have received ' + this.amountToReceive + ' tokens.'
                    );

                    this.resetAmount();
                    this.balanceLoaded = false;
                    this.getTokenBalance();
                })
                .catch((error) => {
                    if (HTTP_BAD_REQUEST === error.response.status && error.response.data.message) {
                        this.notifyError(error.response.data.message);
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
