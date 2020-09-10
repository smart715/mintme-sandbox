<template>
    <modal
        :visible="visible"
        :no-close="noClose"
        @close="closeModal">
        <template slot="body">
            <div class="text-center overflow-wrap-break-word word-break-all">
                <h3 class="col-12 modal-title">WITHDRAW ({{ currency | rebranding }})</h3>
                <div class="col-12 pt-2">
                    <label for="address" class="d-block text-left">
                        Address:
                    </label>
                    <input
                        v-model="$v.address.$model"
                        type="text"
                        id="address"
                        @change="setFirstTimeOpen"
                        :class="{ 'is-invalid': $v.address.$error }"
                        class="form-control">
                    <div v-if="$v.address.$error" class="invalid-feedback">
                        {{ 'WEB' === currency || true === isToken ? 'Wallet address has to be 42 characters long with leading 0x' : 'Invalid wallet address'}}
                    </div>
                </div>
                <div class="col-12 pt-2 pb-5 withdraw-amount">
                    <label for="wamount"  class="d-block text-left">
                        Amount (balance):
                    </label>
                        <input
                            id="wamount"
                            v-model="$v.amount.$model"
                            type="text"
                            @change="setFirstTimeOpen"
                            @keypress="checkAmount"
                            @paste="checkAmount"
                            :class="{ 'is-invalid': $v.amount.$error }"
                            class="form-control form-control-btn text-left input-custom-padding">
                        <button
                            class="btn btn-primary btn-input float-right"
                            type="button"
                            @click="setMaxAmount">
                            All
                        </button>
                        <div v-if="!$v.amount.maxValue && $v.amount.decimal" class="invalid-feedback text-center">
                            You don't have enough {{ currency|rebranding }}
                        </div>
                        <div v-if="!$v.amount.minValue && $v.amount.decimal" class="invalid-feedback text-center">
                            Minimum withdraw amount is {{ minAmount }} {{ currency|rebranding }}
                        </div>
                        <div v-if="!$v.amount.decimal" class="invalid-feedback text-center">
                            Invalid amount.
                        </div>
                </div>
                <div v-if="twofa" class="col-12 pb-3">
                    <label for="twofactor" class="d-block text-left">
                        Two Factor Authentication Code:
                    </label>
                    <input
                        autocomplete="off"
                        v-model="code"
                        type="text"
                        id="twofactor"
                        class="form-control">
                </div>
                <div class="col-12 text-left">
                    <label>
                        Withdrawal fee:
                    </label>
                    <span class="float-right">{{ feeAmount }} {{ feeCurrency|rebranding }}</span>
                </div>
                <div class="col-12 pt-3 text-left">
                    <label>
                        Total to be withdrawn:
                    </label>
                    <span class="overflow-wrap-break-word word-break-all float-right">
                        {{ fullAmount | toMoney(subunit) }} {{ currency|rebranding }}
                    </span>
                </div>
                <div class="input-group col-12 pt-2 justify-content-center">
                    <button
                        class="btn btn-primary"
                        :disabled="$v.$anyError || withdrawing"
                        @click="onWithdraw">
                        Withdraw
                    </button>
                    <span
                        class="btn-cancel pl-3 c-pointer"
                        @click="onCancel">
                        <slot name="cancel">Cancel</slot>
                    </span>
                </div>
            </div>
        </template>
    </modal>
</template>

<script>
import Decimal from 'decimal.js';
import Modal from './Modal.vue';
import {required, minLength, maxLength, maxValue, decimal, minValue} from 'vuelidate/lib/validators';
import {toMoney} from '../../utils';
import {MoneyFilterMixin, RebrandingFilterMixin, NotificationMixin, LoggerMixin} from '../../mixins/';
import {addressLength, webSymbol, addressContain, addressFirstSymbol, twoFACode} from '../../utils/constants';

export default {
    name: 'WithdrawModal',
    mixins: [MoneyFilterMixin, RebrandingFilterMixin, NotificationMixin, LoggerMixin],
    components: {
        Modal,
    },
    props: {
        visible: Boolean,
        currency: String,
        isToken: Boolean,
        fee: String,
        webFee: String,
        withdrawUrl: String,
        maxAmount: String,
        availableWeb: String,
        subunit: Number,
        twofa: String,
        noClose: Boolean,
        expirationTime: Number,
    },
    data() {
        return {
            code: '',
            amount: 0,
            address: '',
            withdrawing: true,
            flag: true,
        };
    },
    computed: {
        minAmount: function() {
            return toMoney('1e-' + this.subunit, this.subunit);
        },
        fullAmount: function() {
            Decimal.set({precision: 36});

            let amount = new Decimal(
                new RegExp(/^[0-9]+(\.?[0-9]+)?$/).test(this.amount) ? this.amount : 0
            );

            return toMoney(
                amount.add(amount.greaterThanOrEqualTo(this.fee) ? this.fee : 0).toString(),
                this.subunit
            );
        },
        feeAmount: function() {
            return this.isToken ? this.webFee : this.fee;
        },
        feeCurrency: function() {
            return this.isToken ? webSymbol : this.currency;
        },
    },
    methods: {
        checkAmount: function(event) {
            let inputPos = event.target.selectionStart;
            let amount = this.$v.amount.$model.toString();
            let selected = getSelection().toString();
            let regex = new RegExp(`^([0-9]?)+(\\.?([0-9]?){1,${this.subunit}})?$`);
            let input = event instanceof ClipboardEvent
                ? event.clipboardData.getData('text')
                : String.fromCharCode(!event.charCode ? event.which : event.charCode);

            if (selected && regex.test(amount.slice(0, inputPos) + input + amount.slice(inputPos + selected.length))) {
                return true;
            }
            if (!regex.test(amount.slice(0, inputPos) + input + amount.slice(inputPos))) {
                event.preventDefault();
                return false;
            }
        },
        closeModal: function() {
            this.$v.$reset();
            this.amount = 0;
            this.address = '';
            this.code = '';
            this.$emit('close');
        },
        onWithdraw: function() {
            this.$v.$touch();
            if (this.$v.$error) {
                this.notifyError('Correct your form fields');
                return;
            }

            if (this.isToken && new Decimal(this.availableWeb).lessThan(this.webFee)) {
                this.notifyError('You do not have enough ' + this.rebrandingFunc(this.feeCurrency) + ' to pay the fee');
                return;
            }

            this.withdrawing = true;

            this.$axios.single.post(this.withdrawUrl, {
                'crypto': this.currency,
                'amount': this.amount,
                'address': this.address,
                'code': this.code || null,
            })
            .then((response) => {
                if (!this.twofa) {
                    this.notifySuccess(`Confirmation email has been sent to your email. It will expire in ${Math.floor(this.expirationTime / 3600)} hours.`);
                } else {
                    this.notifySuccess('Withdrawal request successfully confirmed and added to queue.');
                }
                this.closeModal();
            })
            .catch((error) => {
                this.notifyError(error.response.data.message);
                this.sendLogs('error', 'Withdraw response error', error);
            })
            .then(() => this.withdrawing = false);

            this.$emit('withdraw', this.currency, this.amount, this.address);
        },
        onCancel: function() {
            this.$emit('cancel');
            this.closeModal();
        },
        setMaxAmount: function() {
            let amount = new Decimal(this.maxAmount);
            this.amount = amount.greaterThan(this.fee) ?
                toMoney(amount.sub(this.fee).toString(), this.subunit) : toMoney(0, this.subunit);
        },
        setFirstTimeOpen: function() {
            if (this.flag) {
                this.withdrawing = false;
            }
            this.flag = false;
        },
    },
    validations() {
        return {
            amount: {
                required,
                decimal,
                maxValue: maxValue(
                    toMoney(new Decimal(this.maxAmount).sub(this.fee).toString(), this.subunit)
                ),
                minValue: minValue(this.minAmount),
            },
            address: {
                required,
                addressContain,
                minLength: minLength(
                    addressLength[this.currency] ? addressLength[this.currency].min : addressLength.WEB.min
                ),
                maxLength: maxLength(
                    addressLength[this.currency] ? addressLength[this.currency].max : addressLength.WEB.max
                ),
                addressFirstSymbol:
                    addressFirstSymbol[this.currency] ? addressFirstSymbol[this.currency] : addressFirstSymbol['WEB'],
            },
            code: {
              twoFACode,
            },
        };
    },
};
</script>

