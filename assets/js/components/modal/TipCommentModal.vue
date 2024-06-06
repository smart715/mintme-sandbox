<template>
    <div>
        <modal
            :visible="visible"
            :no-close="submitting"
            @close="closeModal"
        >
            <template slot="header">
                <div
                    class="highlight font-size-3 font-weight-bold text-center"
                    v-html="$t('comment.tip_modal.header')"
                ></div>
            </template>
            <template slot="body">
                <div v-html="$t('comment.tip_modal.body.message', translationContext)" class="mb-4"></div>
                <m-input
                    v-model="amount"
                    placeholder="0"
                    :disabled="balancesLoading"
                    :label="$t('comment.tip_modal.body.input.label')"
                    :invalid="invalidAmount"
                    @keypress="checkInput()"
                    @paste="checkInput()"
                >
                    <template v-slot:errors>
                        <div v-if="$v.amount.$dirty && !$v.amount.required">
                            {{ $t('form.validation.required') }}
                        </div>
                        <div v-if="!$v.amount.mintmeBalanceValid">
                            {{ $t('comment.tip_modal.body.not_enough', {currency: rebrandingFunc(WEB.symbol)}) }}
                        </div>
                        <div v-if="!$v.amount.tokenBalanceValid">
                            {{ $t('comment.tip_modal.body.not_enough', {currency: token.name}) }}
                        </div>
                        <div v-if="!$v.amount.decimal">
                            {{ $t('post_form.msg.amount.numeric') }}
                        </div>
                        <div v-if="!$v.amount.between">
                            {{ $t('comment.tip_modal.body.input.error.between', translationContext) }}
                        </div>
                    </template>
                </m-input>
                <div class="text-nowrap d-flex align-items-center">
                    {{ $t('comment.tip_modal.body.balance') }}
                    <coin-balance class="mx-1" :coin-name="token.name" />
                    <coin-avatar-name :token="token" />
                </div>
                <div class="d-flex align-items-center">
                    {{ $t('comment.tip_modal.body.cost') }}
                    {{ tipCost | toMoney }}
                    <coin-avatar-name :crypto="WEB" />
                </div>
                <div class="d-flex justify-content-center align-items-center mt-2">
                    <m-button
                        type="primary"
                        class="font-size-2"
                        :disabled="submitBtnDisabled"
                        :loading="submitting"
                        @click="tipComment"
                    >
                        <template v-slot:prefix>
                            <font-awesome-icon icon="check-square" class="mr-2" />
                        </template>
                        {{ $t('comment.tip_modal.btn_tip') }}
                    </m-button>
                    <m-button
                        class="ml-2 font-size-2"
                        @click="closeModal"
                    >
                        {{ $t('cancel') }}
                    </m-button>
                </div>
            </template>
        </modal>
    </div>
</template>

<script>
import Modal from './Modal.vue';
import {MInput, MButton} from '../UI';
import {
    CheckInputMixin,
    FloatInputMixin,
    MoneyFilterMixin,
    NotificationMixin,
    RebrandingFilterMixin,
} from '../../mixins';
import {required, decimal, between} from 'vuelidate/lib/validators';
import {library} from '@fortawesome/fontawesome-svg-core';
import {faCheckSquare} from '@fortawesome/free-solid-svg-icons';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import {mapGetters, mapMutations} from 'vuex';
import CoinBalance from '../CoinBalance';
import {WEB} from '../../utils/constants';
import CoinAvatarName from '../CoinAvatarName';
import Decimal from 'decimal.js';
import {toMoney} from '../../utils';

library.add(faCheckSquare);

export default {
    name: 'TipCommentModal',
    mixins: [
        CheckInputMixin,
        NotificationMixin,
        FloatInputMixin,
        RebrandingFilterMixin,
        MoneyFilterMixin,
    ],
    components: {
        Modal,
        MInput,
        MButton,
        FontAwesomeIcon,
        CoinBalance,
        CoinAvatarName,
    },
    props: {
        visible: Boolean,
        deployedTokens: Array,
        comment: Object,
    },
    data() {
        return {
            amount: '',
            submitting: false,
            WEB,
        };
    },
    computed: {
        ...mapGetters('user', {userId: 'getId'}),
        ...mapGetters('posts', {
            tipMinAmount: 'getCommentTipMinAmount',
            tipMaxAmount: 'getCommentTipMaxAmount',
            tipCost: 'getCommentTipCost',
        }),
        ...mapGetters('tradeBalance', {
            balances: 'getBalances',
        }),
        token() {
            return this.deployedTokens[0];
        },
        invalidAmount() {
            return this.$v.amount.$dirty && this.$v.amount.$invalid;
        },
        isLoggedIn() {
            return !!this.userId;
        },
        balancesLoading() {
            return !this.balances;
        },
        mintmeBalance() {
            if (!this.balances) {
                return '0';
            }

            return this.balances[WEB.symbol]?.available || '0';
        },
        tokenBalance() {
            if (!this.balances) {
                return '0';
            }

            return this.balances[this.token.name]?.available || '0';
        },
        submitBtnDisabled() {
            return this.submitting
                || '' === this.amount
                || this.invalidAmount
                || this.balancesLoading;
        },
        translationContext() {
            return {minAmount: toMoney(this.tipMinAmount), maxAmount: toMoney(this.tipMaxAmount)};
        },
    },
    methods: {
        ...mapMutations('posts', [
            'setCommentTipped',
        ]),
        async tipComment() {
            this.$v.$touch();

            if (this.$v.$invalid) {
                return;
            }

            this.submitting = true;

            try {
                const url = this.$routing.generate('tip_comment', {commentId: this.comment.id});
                await this.$axios.single.post(url, {
                    tipAmount: this.amount,
                    tokenName: this.token.name,
                });
                this.setCommentTipped(this.comment);
                this.notifySuccess(this.$t('comment.tip_modal.msg_success'));

                this.submitting = false;
                this.closeModal();
            } catch (error) {
                this.notifyError(error.response?.data?.message || this.$t('toasted.error.try_later'));
                this.$logger.error('Error while tipping a comment', error);

                this.submitting = false;
            }
        },
        mintmeBalanceValidator() {
            const cost = new Decimal(this.tipCost);

            return !this.balancesLoading && new Decimal(cost).lessThan(this.mintmeBalance);
        },
        tokenBalanceValidator(val) {
            const tokenBalance = new Decimal(this.tokenBalance);

            return !val || (!this.balancesLoading && new Decimal(val).lessThan(tokenBalance));
        },
        closeModal() {
            if (this.submitting) {
                return;
            }

            this.$emit('close');
        },
    },
    validations() {
        return {
            amount: {
                decimal,
                required,
                between: between(this.tipMinAmount, this.tipMaxAmount),
                mintmeBalanceValid: this.mintmeBalanceValidator,
                tokenBalanceValid: this.tokenBalanceValidator,
            },
        };
    },
    watch: {
        amount() {
            this.$v.amount.$touch();
            this.amount = this.parseFloatInput(this.amount);
        },
        visible() {
            this.amount = '';
            this.$v.amount.$reset();
        },
    },
};
</script>
