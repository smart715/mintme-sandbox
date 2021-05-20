<template>
    <modal
        :visible="visible"
        :no-close="noClose"
        @close="closeModal">
        <template slot="body">
            <div class="text-center overflow-wrap-break-word word-break-all">
                <h3 class="col-12 modal-title">{{ $t('withdraw_modal.title') }}({{ currency | rebranding}})</h3>
                <div class="col-12 pt-2">
                    <label for="address" class="d-block text-left">
                        {{ $t('withdraw_modal.address') }}
                    </label>
                    <input
                        v-model="$v.address.$model"
                        type="text"
                        id="address"
                        @change="setFirstTimeOpen"
                        :class="{ 'is-invalid': $v.address.$error }"
                        class="form-control">
                    <div v-if="$v.address.$error" class="invalid-message">
                        {{ ('WEB' === currency || true === isToken ? $t('withdraw_modal.length') : $t('withdraw_modal.invalid_addr'))}}
                    </div>
                </div>
                <div class="col-12 pt-2 pb-5 withdraw-amount">
                    <label for="wamount"  class="d-block text-left">
                        {{ $t('withdraw_modal.amount') }}
                    </label>
                    <div class="d-flex">
                        <price-converter-input class="d-inline-block flex-grow-1"
                            input-id="wamount"
                            v-model="$v.amount.$model"
                            :input-class="{ 'is-invalid': $v.amount.$error }"
                            :show-converter="!isToken && currencyMode === currencyModes.usd.value"
                            :from="currency"
                            :to="USD.symbol"
                            :subunit="4"
                            symbol="$"
                            @change="setFirstTimeOpen"
                            @keypress="checkInput(subunit, 8)"
                            @paste="checkInput(subunit, 8)"
                        />
                        <button
                            class="btn btn-primary btn-input"
                            type="button"
                            @click="setMaxAmount">
                            {{ $t('withdraw_modal.all') }}
                        </button>
                    </div>
                        <div v-if="!$v.amount.maxValue && $v.amount.decimal" class="invalid-message text-center">
                            {{ $t('withdraw_modal.do_not_have', translationsContext) }}
                        </div>
                        <div
                            v-if="!$v.amount.minValue && $v.amount.decimal && typeof $v.amount.$model === 'string'"
                            class="invalid-message text-center"
                        >
                            {{ $t('withdraw_modal.min_withdraw', translationsContext) }}
                        </div>
                        <div v-if="!$v.amount.decimal" class="invalid-message text-center">
                            {{ $t('withdraw_modal.invalid_amount') }}
                        </div>
                </div>
                <div v-if="twofa" class="col-12 pb-3">
                    <label for="twofactor" class="d-block text-left">
                        {{ $t('withdraw_modal.twofa_code') }}
                    </label>
                    <input
                        autocomplete="off"
                        v-model="code"
                        type="text"
                        id="twofactor"
                        class="form-control"
                    >
                </div>
                <div class="col-12 text-left">
                    <label>
                        {{ $t('withdraw_modal.fee') }}
                    </label>
                    <span class="float-right">{{ feeAmount }} {{ feeCurrency|rebranding }}</span>
                </div>
                <div class="col-12 pt-3 text-left">
                    <label>
                        {{ $t('withdraw_modal.total') }}
                    </label>
                    <span class="overflow-wrap-break-word word-break-all float-right">
                        {{ fullAmount | toMoney(subunit) }} {{ currency|rebranding }}
                    </span>
                </div>
                <div class="input-group col-12 pt-2 justify-content-center">
                    <button
                        class="btn btn-primary"
                        :disabled="$v.$anyError || withdrawing || (twofa !== '' && !code)"
                        @click="onWithdraw">
                        {{ $t('withdraw_modal.submit') }}
                    </button>&nbsp;
                    <button
                        class="btn-cancel pl-3 c-pointer bg-transparent"
                        @click="onCancel"
                        @keyup.enter="onCancel"
                        tabindex="0"
                    >
                        <slot name="cancel">{{ $t('withdraw_modal.cancel') }}</slot>
                    </button>
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
import {
    addressLength,
    addressContain,
    addressFirstSymbol,
    twoFACode,
    USD,
    currencyModes,
} from '../../utils/constants';
import {
    CheckInputMixin,
    MoneyFilterMixin,
    RebrandingFilterMixin,
    NotificationMixin,
    LoggerMixin,
} from '../../mixins/';
import PriceConverterInput from '../PriceConverterInput';

export default {
    name: 'WithdrawModal',
    mixins: [
        CheckInputMixin,
        MoneyFilterMixin,
        RebrandingFilterMixin,
        NotificationMixin,
        LoggerMixin,
    ],
    components: {
        PriceConverterInput,
        Modal,
    },
    props: {
        visible: Boolean,
        currency: String,
        isToken: Boolean,
        fee: String,
        baseFee: String,
        baseSymbol: String,
        withdrawUrl: String,
        maxAmount: String,
        availableBase: String,
        subunit: Number,
        twofa: String,
        noClose: Boolean,
        expirationTime: Number,
        currencyMode: String,
    },
    data() {
        return {
            code: null,
            amount: 0,
            address: '',
            withdrawing: true,
            flag: true,
            USD,
            currencyModes,
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
                amount.add(this.fee || 0).toString(),
                this.subunit
            );
        },
        feeAmount: function() {
            return this.fee || this.baseFee;
        },
        feeCurrency: function() {
            return this.fee ? this.currency : this.baseSymbol;
        },
        translationsContext: function() {
            return {
                currency: this.rebrandingFunc(this.currency),
                minAmount: this.minAmount,
            };
        },
    },
    methods: {
        closeModal: function() {
            this.$v.$reset();
            this.amount = 0;
            this.address = '';
            this.code = null;
            this.$emit('close');
        },
        onWithdraw: function() {
            this.$v.$touch();
            if (this.$v.$error) {
                this.notifyError(this.$t('toasted.error.correct_form'));
                return;
            }

            if (!this.fee && new Decimal(this.availableBase).lessThan(this.baseFee)) {
                this.notifyError(this.$t('toasted.error.do_not_have_enough', {currency: this.rebrandingFunc(this.feeCurrency)}));
                return;
            }

            this.withdrawing = true;

            this.$axios.single.post(this.withdrawUrl, {
                'crypto': this.currency,
                'amount': this.amount,
                'address': this.address,
                'code': this.code,
                'fee': this.fee,
            })
            .then((response) => {
                if (this.code === null) {
                    this.notifySuccess(
                        this.$t('toasted.success.email_sent', {hours: Math.floor(this.expirationTime / 3600)}));
                } else {
                    this.notifySuccess(this.$t('toasted.success.withdrawal.queued'));
                }
                this.closeModal();
            })
            .catch((error) => {
                this.notifyError(this.$t('api.wallet.withdrawal_failed'));
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
            this.amount = amount.greaterThan(this.fee || 0) ?
                toMoney(amount.sub(this.fee || 0).toString(), this.subunit) : toMoney(0, this.subunit);
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
                    Math.max(0, toMoney(new Decimal(this.maxAmount).sub(this.fee || 0).toString(), this.subunit))
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

