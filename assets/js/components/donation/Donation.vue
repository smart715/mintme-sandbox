<template>
    <div class="container-fluid px-0">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-6 pr-lg-2 mt-3">
                <div class="h-100">
                    <div class="h-100 donation">
                        <div class="donation-header text-left">
                            <span v-if="loggedIn">Donations</span>
                            <span v-else>To make a donation you have to be logged in</span>
                        </div>
                        <div class="donation-body">
                            <div v-if="loggedIn" class="h-100">
                                <div class="p-md-4">
                                    <div>
                                        <p>Donation is non-refundable</p>
                                    </div>
                                    <div class="row currency-container">
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
                                                    @click="selectedCurrency = option; balanceLoaded = false;"
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
                                                {{ cryptoBalance | toMoney(marketSubunit) | formatMoney }}
                                            </span>
                                            <font-awesome-icon v-else icon="circle-notch" spin class="loading-spinner" fixed-width />
                                            <div v-if="showWarning">
                                                <span class="d-block text-danger font-size-90">
                                                    Insufficient funds for donation,
                                                </span>
                                                <span class="d-block">please make <a :href="getDepositLink">deposit</a> first</span>
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
                                                    :disabled="!loggedIn"
                                                    @keypress="checkAmountInput"
                                                    @paste="checkAmountInput"
                                                >
                                                <div class="input-group-append">
                                                    <button class="btn btn-primary" type="button">All</button>
                                                </div>
                                            </div>
                                            <p class="mt-2 mb-4">
                                                You will receive approximately: {{ getAmountToReceive }} tokens
                                                <guide
                                                    :placement="'right-start'"
                                                    :max-width="'200px'"
                                                >
                                                    <template slot="body">
                                                        Amount of tokens you will receive may vary greatly depending on the current situation.
                                                        It can be much less than suggested here.
                                                    </template>
                                                </guide>
                                            </p>
                                        </div>
                                        <div>
                                            <p class="mb-2">Fee for donation: {{ fee }}%</p>
                                            <button
                                                :disabled="buttonDisabled"
                                                @click="donateDonation"
                                                class="btn btn-primary"
                                            >
                                                Donate {{ donationCurrency }}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div v-if="!contentLoaded" class="p-5 text-center">
                                <font-awesome-icon icon="circle-notch" spin class="loading-spinner" fixed-width />
                            </div>
                            <div
                                id="tab-login-form-container"
                                :class="loginFormContainerClass"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import {
    MoneyFilterMixin,
    NotificationMixin,
    LoggerMixin,
    RebrandingFilterMixin,
    InputValidationMixin,
} from '../../mixins';
import Guide from '../Guide';
import Decimal from 'decimal.js';
import {toMoney} from '../../utils';
import {webSymbol, btcSymbol} from '../../utils/constants';

export default {
    name: 'Donation',
    mixins: [
        MoneyFilterMixin,
        NotificationMixin,
        LoggerMixin,
        RebrandingFilterMixin,
        InputValidationMixin,
    ],
    components: {
        Guide,
    },
    props: {
        market: Object,
        loggedIn: Boolean,
        isOwner: Boolean,
        userId: Number,
        googleRecaptchaSiteKey: String,
        donationFee: Number,
    },
    data() {
        return {
            options: {
                webSymbol,
                btcSymbol,
            },
            selectedCurrency: null,
            contentLoaded: false,
            amountToDonate: 0,
            amountToReceive: 0,
            balanceLoaded: false,
            balance: 0,
        };
    },
    computed: {
        loginFormContainerClass: function() {
            if (!this.loggedIn) {
                return 'p-md-4';
            }

            return '';
        },
        donationCurrency: function() {
            return this.rebrandingFunc(this.selectedCurrency);
        },
        marketSubunit: function() {
            return this.market.base.subunit;
        },
        cryptoBalance: function() {
            return this.balance;
        },
        fee: function() {
            return this.donationFee;
        },
        getAmountToReceive: function() {
            return this.amountToReceive;
        },
        getDepositLink: function() {
            return this.$routing.generate('wallet', {
                depositMore: this.donationCurrency,
            });
        },
        isCurrencySelected: function() {
            return [webSymbol, btcSymbol].includes(this.selectedCurrency);
        },
        dropdownText: function() {
            return this.isCurrencySelected
                ? this.donationCurrency
                : 'Select currency';
        },
        minTotalPrice: function() {
            return toMoney('1e-' + this.market.base.subunit, this.market.base.subunit);
        },
        showWarning: function() {
            return this.balanceLoaded && (new Decimal(this.balance)).lessThan(this.minTotalPrice);
        },
        buttonDisabled: function() {
            return !this.isCurrencySelected || this.showWarning;
        },
    },
    mounted() {
        if (!this.loggedIn) {
            this.loadLoginForm();
        } else {
            this.contentLoaded = true;
        }
    },
    methods: {
        loadLoginForm: function() {
            this.$axios.retry.get(this.$routing.generate('login', {
                formContentOnly: true,
            }))
                .then((res) => {
                    let formContainer = document.getElementById('tab-login-form-container');
                    formContainer.innerHTML = res.data;

                    this.contentLoaded = true;

                    let captchaContainer = document.querySelector('.g-recaptcha');
                    grecaptcha.render(captchaContainer, {
                        'sitekey': this.googleRecaptchaSiteKey,
                    });
                })
                .catch((err) => {
                    this.notifyError('Something went wrong. Try to reload the page.');
                    this.sendLogs('error', 'Can not load tab content.', err);
                });
        },
        getTokenBalance: function() {
            this.$axios.retry.get(this.$routing.generate('crypto_balance', {symbol: this.selectedCurrency}))
                .then((res) => {
                    this.balance = res.data;
                    this.balanceLoaded = true;
                })
                .catch((err) => {
                    this.notifyError('Can not load balance. Try again later.');
                    this.sendLogs('error', 'Can not load crypto balance.', err);
                });
        },
        checkAmountInput: function() {
            return this.checkInput(this.market.base.subunit);
        },
        checkDonation: function() {
            this.$axios.retry.get(this.$routing.generate('donation_check', {
                market: this.selectedCurrency,
                amount: this.amountToDonate,
                fee: this.donationFee,
            }))
                .then((res) => this.amountToReceive = res.data)
                .catch((err) => {
                    this.notifyError('Can not to calculate approximate amount of tokens. Try again later.');
                    this.sendLogs('error', 'Can not to calculate approximate amount of tokens.', err);
                });
        },
        donateDonation: function() {
            this.$axios.single.post(this.$routing.generate('donation_donate', {
                market: this.selectedCurrency,
                amount: this.amountToDonate,
                fee: this.donationFee,
                expected_count_to_receive: this.amountToReceive,
            }))
                .then((response) => {
                    if (HTTP_ACCEPTED === response.status && null !== response.data.message) {
                        this.notifySuccess(response.data.message);
                    }
                }, (error) => {
                    if (!error.response) {
                        this.notifyError('Network error');
                        this.sendLogs('error', 'Make donation code network error.', error);
                    } else if (error.response.data.message) {
                        this.notifyError(error.response.data.message);
                        this.sendLogs('error', 'Can not make donation.', error);
                    } else {
                        this.notifyError('An error has occurred, please try again later.');
                        this.sendLogs('error', 'An error has occurred, please try again later.', error);
                    }
                });
        },
    },
    watch: {
        selectedCurrency: function() {
            if (this.isCurrencySelected) {
                this.getTokenBalance();
            }
        },
    },
};
</script>
