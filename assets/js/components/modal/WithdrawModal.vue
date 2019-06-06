<template>
    <modal
        :visible="visible"
        @close="closeModal">
        <template slot="body">
            <div class="text-center">
                <h3 class="modal-title">WITHDRAW({{ currency }})</h3>
                <div class="col-12 pt-2">
                    <label for="address" class="d-block text-left">
                        Address:
                    </label>
                    <input
                        v-model="$v.address.$model"
                        type="text"
                        id="address"
                        :class="{ 'is-invalid': $v.address.$error }"
                        class="form-control">
                    <div v-if="$v.address.$error" class="invalid-feedback">
                        Wrong address
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
                            @keypress="checkAmount"
                            @paste="checkAmount"
                            :class="{ 'is-invalid': $v.amount.$error }"
                            class="form-control text-left input-custom-padding">
                        <button
                            class="btn btn-primary btn-input float-right"
                            type="button"
                            @click="setMaxAmount">
                            All
                        </button>
                        <div v-if="!$v.amount.maxValue && $v.amount.decimal" class="invalid-feedback text-center mt-n4">
                            You don't have enough {{ currency }}
                        </div>
                        <div v-if="!$v.amount.minValue && $v.amount.decimal" class="invalid-feedback text-center mt-n4">
                            Minimum withdraw amount is {{ minAmount }} {{ currency }}
                        </div>
                        <div v-if="!$v.amount.decimal" class="invalid-feedback text-center mt-n4">
                            Invalid amount.
                        </div>
                </div>
                <div v-if="twofa" class="col-12 pb-3">
                    <label for="twofactor" class="d-block text-left">
                        Two Factor Authentication Code:
                    </label>
                    <input
                        v-model="code"
                        type="text"
                        id="twofactor"
                        class="form-control">
                </div>
                <div class="col-12 text-left">
                    <label>
                        Withdrawal fee:
                    </label>
                    <span class="float-right">{{ fee | toMoney(subunit) }}</span>
                </div>
                <div class="col-12 pt-3 text-left">
                    <label>
                        Total to be withdrawn:
                    </label>
                    <span class="float-right">{{ fullAmount | toMoney(subunit) }}</span>
                </div>
                <div class="col-12 pt-2 text-center">
                    <button
                        class="btn btn-primary"
                        :disabled="withdrawing"
                        @click="onWithdraw">
                        Withdraw
                    </button>
                    <a
                        href="#"
                        class="btn-cancel pl-3"
                        @click="onCancel">
                        <slot name="cancel">Cancel</slot>
                    </a>
                </div>
            </div>
        </template>
    </modal>
</template>

<script>
import Decimal from 'decimal.js';
import Modal from './Modal.vue';
import {required, minLength, maxLength, maxValue, decimal, minValue, helpers} from 'vuelidate/lib/validators';
import {toMoney} from '../../utils';

const tokenContain = helpers.regex('address', /^[a-zA-Z0-9]+$/u);

export default {
    name: 'WithdrawModal',
    components: {
        Modal,
    },
    props: {
        visible: Boolean,
        currency: String,
        fee: String,
        withdrawUrl: String,
        maxAmount: String,
        addressLength: Number,
        subunit: Number,
        twofa: String,
    },
    data() {
        return {
            withdrawing: false,
            code: '',
            amount: 0,
            address: '',
            minAmount: toMoney('1e-' + this.subunit),
        };
    },
    computed: {
        fullAmount: function() {
            Decimal.set({precision: 36});

            let amount = new Decimal(
                new RegExp(/^[0-9]+(\.?[0-9]+)?$/).test(this.amount) ? this.amount : 0
            );

            return toMoney(amount.add(amount.greaterThanOrEqualTo(this.fee) ? this.fee : 0).toString(), this.subunit);
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
            this.$emit('close');
        },
        onWithdraw: function() {
            this.$v.$touch();
            if (this.$v.$error) {
                this.$toasted.error('Correct your form fields');
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
                this.$toasted.success('Confirmation email has been sent to your email. It will expire in 4 hours.');
                this.closeModal();
            })
            .catch((error) => {
                this.$toasted.error(error.response.data.error);
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
                tokenContain: tokenContain,
                minLength: minLength(this.addressLength),
                maxLength: maxLength(this.addressLength),
            },
        };
    },
    filters: {
        toMoney: function(val, subunit) {
            return toMoney(val, subunit);
        },
    },
};
</script>

