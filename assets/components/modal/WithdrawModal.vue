<template>
    <modal
        :visible="visible"
        @close="closeModal">
        <template slot="body" v-if="formLoaded">
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
                    <span class="float-right">{{ fullAmount }}</span>
                </div>
                <div class="pt-3">
                    <button
                        class="btn btn-primary"
                        @click="onWithdraw">
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
        <template slot="body" v-else>
            <div class="row mb-3">
                <div class="col text-center">
                    <font-awesome-layers class="fa-3x">
                        <font-awesome-icon icon="circle-notch" spin class="loading-spinner" fixed-width  />
                    </font-awesome-layers>
                </div>
            </div>
        </template>
    </modal>
</template>

<script>
import Decimal from 'decimal.js';
import Modal from './Modal.vue';
import axios from 'axios';
import {required, minLength, maxValue, decimal, alphaNum, minValue} from 'vuelidate/lib/validators';

export default {
    name: 'WithdrawModal',
    components: {
        Modal,
    },
    props: {
        visible: Boolean,
        currency: String,
        precision: Number,
        fee: String,
        withdrawUrl: String,
    },
    data() {
        return {
            formLoaded: false,
            amount: 0,
            maxAmount: 0,
            address: '',
        };
    },
    computed: {
        fullAmount: function() {
            Decimal.set({precision: 36});

            let amount = new Decimal(
                new RegExp(/^[0-9]+(\.?[0-9]+)?$/).test(this.amount) ? this.amount : 0
            );

            return amount.add(amount.greaterThanOrEqualTo(this.fee) ? this.fee : 0).toFixed(this.precision);
        },
    },
    methods: {
        closeModal: function() {
            this.amount = 0;
            this.address = '';
            this.$emit('close');
        },
        onWithdraw: function() {
            if (this.$v.address.$error || this.$v.amount.$error) {
                this.$toasted.error('Correct your form fields');
                return;
            }

            axios.post(this.withdrawUrl, {
                crypto: this.currency,
                amount: this.amount,
                address: this.address,
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
        fetchMaxAmount: function() {
            return axios.get(this.$routing.generate('fetch_balance_token', {tokenName: this.currency}));
        },
        setMaxAmount: function() {
            let amount = new Decimal(this.maxAmount);
            this.amount = amount.greaterThan(this.fee) ?
                amount.sub(this.fee).toFixed(this.precision) : 0;
        },
    },
    watch: {
        visible: function(value) {
            if (!value) {
                this.formLoaded = false;
                return;
            }

            this.fetchMaxAmount()
                .then((response) => {
                    this.maxAmount = response.data.available;
                    this.formLoaded = true;
                })
                .catch((error) => {
                    this.$emit('close');
                    this.$toasted.error('Service unavailable now. Try later');
                });
        },
    },
    validations() {
        return {
            amount: {
                required,
                decimal,
                maxValue: maxValue(
                    new Decimal(this.maxAmount).sub(this.fee).toDP(this.precision)
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
};
</script>

