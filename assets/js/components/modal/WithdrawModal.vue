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
                <div class="col-12 text-left">
                    <label>
                        Withdrawal fee:
                    </label>
                    <span class="float-right">{{ fee | toMoney }}</span>
                </div>
                <div class="col-12 pt-3 text-left">
                    <label>
                        Total to be withdrawn:
                    </label>
                    <span class="float-right">{{ fullAmount | toMoney }}</span>
                </div>
                <div class="col-12 pt-2 text-center">
                    <button
                        class="btn btn-primary"
                        @click="onWithdraw">
                        Withdraw
                    </button>
                    <a
                        href="#"
                        class="ml-3"
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
import {required, minLength, maxLength, maxValue, decimal, alphaNum, minValue} from 'vuelidate/lib/validators';
import {toMoney} from '../../utils';
import {GENERAL} from '../../utils/constants';

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
    },
    data() {
        return {
            amount: 0,
            address: '',
            minAmount: toMoney('1e-' + GENERAL.precision),
        };
    },
    computed: {
        fullAmount: function() {
            Decimal.set({precision: 36});

            let amount = new Decimal(
                new RegExp(/^[0-9]+(\.?[0-9]+)?$/).test(this.amount) ? this.amount : 0
            );

            return toMoney(amount.add(amount.greaterThanOrEqualTo(this.fee) ? this.fee : 0).toString());
        },
    },
    methods: {
        checkAmount: function(event) {
            let inputPos = event.target.selectionStart;
            let amount = this.$v.amount.$model.toString();
            let selected = event.view.getSelection().toString();
            let regex = new RegExp(`^([0-9]?)+(\\.?([0-9]?){1,${GENERAL.precision}})?$`);
            let key = String.fromCharCode(!event.charCode ? event.which : event.charCode);

            if (selected && regex.test(amount.slice(0, inputPos) + key + amount.slice(inputPos + selected.length))) {
                return true;
            }
            if (!regex.test(amount.slice(0, inputPos) + key + amount.slice(inputPos))) {
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

            this.$axios.single.post(this.withdrawUrl, {
                'crypto': this.currency,
                'amount': this.amount,
                'address': this.address,
            })
            .then((response) => {
                this.$toasted.success('Paid');
                this.closeModal();
            })
            .catch((error) => {
                this.$toasted.error(error.response.data.error);
            });

            this.$emit('withdraw', this.currency, this.amount, this.address);
        },
        onCancel: function() {
            this.$emit('cancel');
            this.closeModal();
        },
        setMaxAmount: function() {
            let amount = new Decimal(this.maxAmount);
            this.amount = amount.greaterThan(this.fee) ?
                toMoney(amount.sub(this.fee).toString()) : toMoney(0);
        },
    },
    validations() {
        return {
            amount: {
                required,
                decimal,
                maxValue: maxValue(
                    toMoney(new Decimal(this.maxAmount).sub(this.fee).toString())
                ),
                minValue: minValue(this.minAmount),
            },
            address: {
                required,
                alphaNum,
                minLength: minLength(this.addressLength),
                maxLength: maxLength(this.addressLength),
            },
        };
    },
    filters: {
        toMoney: function(val) {
            return toMoney(val);
        },
    },
};
</script>

