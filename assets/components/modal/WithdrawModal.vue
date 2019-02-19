<template>
    <modal
        :visible="visible"
        @close="closeModal">
        <template slot="body">
            <div class="text-center">
                <h3>WITHDRAW({{ currency }})</h3>
                <div class="col-12 pt-3">
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
                        Address can't be empty and must contain alphanumeric letters only.
                    </div>
                </div>
                <div class="col-12 pt-3">
                    <label for="wamount"  class="d-block text-left">
                        Amount (balance):
                    </label>
                    <div class="text-right">
                        <input
                            id="wamount"
                            v-model.number="$v.amount.$model"
                            type="text"
                            :class="{ 'is-invalid': $v.amount.$error }"
                            class="form-control text-left input-custom-padding">
                        <button
                            class="btn btn-primary btn-input"
                            type="button"
                            @click="setMaxAmount">
                            All
                        </button>
                    </div>
                    <div v-if="$v.amount.$error" class="invalid-feedback">
                        You can't set bigger amount than your own balance. Amount must be decimal.
                    </div>
                </div>
                <div class="col-12 pt-3 text-left">
                    <label>
                        Amount {{ currency }}:
                    </label>
                    <span class="float-right">{{ fullAmount | toMoney }}</span>
                </div>
                <div class="pt-3">
                    <button
                        class="btn btn-primary"
                        @click="onWithdraw">
                        <font-awesome-icon v-if="submitting" icon="circle-notch" spin class="loading-spinner" fixed-width />
                        WITHDRAW
                    </button>
                    <a
                        href="#"
                        class="ml-3"
                        @click="onCancel">
                        <slot name="cancel">CANCEL</slot>
                    </a>
                </div>
            </div>
        </template>
    </modal>
</template>

<script>
import Decimal from 'decimal.js';
import Modal from './Modal.vue';
import {required, minLength, maxValue, decimal, alphaNum, minValue} from 'vuelidate/lib/validators';
import {toMoney} from '../../js/utils';

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
    },
    data() {
        return {
            submitting: false,
            amount: 0,
            address: '',
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
        closeModal: function() {
            this.$v.$reset();
            this.amount = 0;
            this.address = '';
            this.$emit('close');
        },
        onWithdraw: function() {
            if (this.submitting) {
                return;
            }

            if (this.$v.address.$error || this.$v.amount.$error) {
                this.$toasted.error('Correct your form fields');
                return;
            }

            this.submitting = true;
            this.$axios.single.post(this.withdrawUrl, {
                'crypto': this.currency,
                'amount': this.amount,
                'address': this.address,
            })
            .then(() => {
                this.$toasted.success('Paid');
                this.closeModal();
            })
            .catch(({response}) => this.$toasted.error(
                !response
                    ? 'Network error'
                    : response.data.error
                    ? response.data.error
                    : 'Service unavailable now. Try later')
            )
            .then(() => this.submitting = false);

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
                minValue: minValue(0.00001),
            },
            address: {
                required,
                alphaNum,
                minLength: minLength(1),
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

