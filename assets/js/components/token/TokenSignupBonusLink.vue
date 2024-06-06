<template>
    <div>
        <div v-if="loading" class="text-center">
            <font-awesome-icon
                icon="circle-notch"
                class="loading-spinner"
                fixed-width
                spin
            />
        </div>
        <div v-else-if="hasSignUpBonusLink">
            <m-button
                type="primary"
                class="mt-3 px-3"
                wide
                :loading="loadingModal"
                @click="showModal = true"
            >
                {{ $t('page.token_settings.tab.sign_up.delete_bonus') }}
            </m-button>
            <confirm-modal
                type="warning"
                no-title
                :visible="showModal"
                :show-image="false"
                @confirm="deleteTokenSignupBonusLink"
                @close="showModal = false"
            >
                <p class="text-white modal-title text-break pt-2 pb-4">
                    {{ $t('page.token_settings.tab.sign_up.confirm') }}
                </p>
            </confirm-modal>
        </div>
        <div v-else class="airdrop-campaign">
            <m-input
                autocomplete="off"
                v-model="tokensAmount"
                :label="$t('page.token_settings.tab.sign_up.amount.label')"
                :invalid="isTokenAmountValid"
                @keypress="checkInput(2)"
                @paste="checkInput(2)"
            >
                <template v-slot:hint>
                    <span v-b-tooltip="tooltipConfig">
                        <span v-html="$t('token.sign_up_bonus.min_amount',translationsContext)" />
                        {{ tokenBalance | toMoney(tokSubunit, false) | formatMoney }}.
                    </span>
                </template>
                <template v-slot:errors>
                    <div v-if="balanceLoaded && insufficientBalance">
                        {{ $t('token.sign_up_bonus.insufficient_funds') }}
                    </div>
                    <div v-else-if="balanceLoaded && !isAmountValid">
                        <span v-b-tooltip="tooltipConfig">
                            <span v-html="$t('token.sign_up_bonus.min_amount',translationsContext)" />
                            {{ tokenBalance | toMoney(tokSubunit, false) | formatMoney }}.
                        </span>
                    </div>
                </template>
            </m-input>
            <m-input
                autocomplete="off"
                v-model="participantsAmount"
                :label="$t('page.token_settings.tab.sign_up.participants.label')"
                :invalid="!isParticipantsAmountValid"
                @keypress="checkInput(0)"
                @paste="checkInput(0)"
            >
                <template v-slot:hint>
                    {{ $t('token.sign_up_bonus.min_amount_participants', participantsTransContext) }}
                </template>
                <template v-slot:errors>
                    <div v-if="!isParticipantsAmountValid">
                        {{ $t('token.sign_up_bonus.min_amount_participants', participantsTransContext) }}
                    </div>
                </template>
            </m-input>
            <div class="pb-0 px-0">
                {{ $t('token.sign_up_bonus.reward') }}
                <span class="text-nowrap text-primary">
                    {{ reward | toMoney(tokSubunit) | formatMoney }}
                    <span v-b-tooltip="tooltipConfig">
                        <coin-avatar
                            :image="tokenAvatar"
                            :is-user-token="true"
                        />
                        {{ truncatedTokenName }}
                    </span>
                </span>
            </div>
            <div class="px-0 clearfix">
                <div class="w-100 mb-3 text-danger">
                    {{ errorMessage }}
                </div>
                <m-button
                    type="primary"
                    class="btn btn-primary float-left"
                    :disabled="btnDisabled"
                    @click="createTokenSignupBonusLink"
                >
                    {{ $t('save') }}
                </m-button>
            </div>
        </div>
    </div>
</template>

<script>
import Decimal from 'decimal.js';
import {mapGetters} from 'vuex';
import ConfirmModal from '../modal/ConfirmModal';
import {
    CheckInputMixin,
    NotificationMixin,
    MoneyFilterMixin,
    FiltersMixin,
} from '../../mixins';
import {
    TOK,
    HTTP_CREATED,
    HTTP_NO_CONTENT,
    HTTP_NOT_FOUND,
    HTTP_ACCESS_DENIED,
} from '../../utils/constants';
import {MButton, MInput} from '../UI';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import {VBTooltip, VBToggle} from 'bootstrap-vue';
import CoinAvatar from '../CoinAvatar';
import {generateCoinAvatarHtml} from '../../utils';

export default {
    name: 'TokenSignupBonusLink',
    mixins: [
        CheckInputMixin,
        NotificationMixin,
        MoneyFilterMixin,
        FiltersMixin,
    ],
    components: {
        ConfirmModal,
        MButton,
        MInput,
        FontAwesomeIcon,
        CoinAvatar,
    },
    directives: {
        'b-toggle': VBToggle,
        'b-tooltip': VBTooltip,
    },
    props: {
        tokenName: String,
        tokenAvatar: String,
        signupBonusParams: Object,
    },
    data() {
        return {
            tokSubunit: TOK.subunit,
            showModal: false,
            loadingModal: false,
            loading: false,
            errorMessage: '',
            errorUrlTooLong: '',
            tokensAmount: 100,
            participantsAmount: 100,
            hasSignUpBonusLink: false,
        };
    },
    mounted: function() {
        this.loadTokenSignupBonus();
    },
    computed: {
        ...mapGetters('tradeBalance', {
            balances: 'getBalances',
        }),
        balanceLoaded: function() {
            return null !== this.balances;
        },
        tokenBalance: function() {
            return this.balanceLoaded
                ? this.balances[this.tokenName]?.available ?? '0'
                : '0';
        },
        btnDisabled: function() {
            return this.insufficientBalance
                || !this.isAmountValid
                || !this.isParticipantsAmountValid
                || !this.isRewardValid;
        },
        insufficientBalance: function() {
            if (this.balanceLoaded) {
                const balance = new Decimal(this.tokenBalance);
                const tokensAmount = new Decimal(this.tokensAmount || 0);

                return balance.lessThan(this.minTokensAmount)
                    || balance.lessThan(tokensAmount);
            }

            return false;
        },
        reward: function() {
            if (0 < this.tokensAmount && 0 < this.participantsAmount) {
                const amount = new Decimal(this.tokensAmount);
                const participants = new Decimal(this.participantsAmount);

                return amount.dividedBy(participants);
            }

            return new Decimal(0);
        },
        isTokenAmountValid: function() {
            return this.balanceLoaded && (this.insufficientBalance || !this.isAmountValid);
        },
        isParticipantsAmountValid: function() {
            return this.participantsAmount >= this.minParticipantsAmount
                && this.participantsAmount <= this.maxParticipantsAmount;
        },
        isAmountValid: function() {
            if (0 < this.tokensAmount) {
                const tokensAmount = new Decimal(this.tokensAmount);

                return tokensAmount.greaterThanOrEqualTo(this.minTokensAmount);
            }

            return false;
        },
        minTokensAmount: function() {
            return this.signupBonusParams.min_tokens_amount || 0;
        },
        minParticipantsAmount: function() {
            return this.signupBonusParams.min_participants_amount || 0;
        },
        maxParticipantsAmount: function() {
            return this.signupBonusParams.max_participants_amount || 0;
        },
        minTokenReward: function() {
            return this.signupBonusParams.min_token_reward || 0;
        },
        shouldTruncate: function() {
            return this.tokenName
                ? 10 < this.tokenName.length
                : false;
        },
        tooltipConfig: function() {
            const tooltip = {
                title: this.tokenName,
                customClass: 'tooltip-custom',
                variant: 'light',
            };

            return this.shouldTruncate
                ? tooltip
                : null;
        },
        truncatedTokenName: function() {
            return this.shouldTruncate
                ? this.truncateFunc(this.tokenName, 10)
                : this.tokenName;
        },
        participantsTransContext: function() {
            return {
                minParticipantsAmount: this.minParticipantsAmount,
                maxParticipantsAmount: this.maxParticipantsAmount,
            };
        },
        bonusUrl: function() {
            return location.origin + this.$routing.generate('token_sign_up', {
                name: this.tokenName,
            });
        },
        translationsContext: function() {
            return {
                tokenName: this.truncatedTokenName,
                minTokensAmount: this.minTokensAmount,
                avatar: generateCoinAvatarHtml({image: this.tokenAvatar, isUserToken: true}),
            };
        },
        isRewardValid: function() {
            return this.reward.greaterThanOrEqualTo(this.minTokenReward);
        },
    },
    methods: {
        loadTokenSignupBonus: async function() {
            this.loading = true;

            try {
                const request = await this.$axios.retry.get(
                    this.$routing.generate('has_active_token_signup_bonus', {
                        tokenName: this.tokenName,
                    })
                );

                if (HTTP_NO_CONTENT === request.status) {
                    return;
                }

                this.hasSignUpBonusLink = true;
                this.$emit('bonus-link-changed', this.bonusUrl);
            } catch (err) {
                this.$logger.error('Can not load token sign up bonus.', err);
            } finally {
                this.loading = false;
            }
        },
        createTokenSignupBonusLink: async function() {
            if (this.btnDisabled || this.insufficientBalance) {
                return;
            }

            if (!this.isRewardValid) {
                this.errorMessage = this.$t('page.token_settings.tab.sign_up.invalid_reward', {
                    minTokenReward: this.minTokenReward,
                    tokenName: this.tokenName,
                });

                return;
            }

            this.loading = true;

            try {
                await this.$axios.single.post(
                    this.$routing.generate('create_token_sign_up_bonus_link', {
                        tokenName: this.tokenName,
                    }), {
                        amount: new Decimal(this.tokensAmount).toString(),
                        participants: new Decimal(this.participantsAmount).toString(),
                    }
                );

                this.notifySuccess(this.$t('page.token_settings.tab.sign_up.create_success'));
                this.hasSignUpBonusLink = true;
                this.$emit('bonus-link-changed', this.bonusUrl);
            } catch (err) {
                if (HTTP_CREATED !== err.response.status) {
                    this.notifyError(err.response?.data?.message || this.$t('toasted.error.try_later'));
                } else if (HTTP_NOT_FOUND === err.response.status && err.response.data.message) {
                    location.href = this.$routing.generate('token_create');
                } else {
                    this.notifyError(err.response?.data?.message || this.$t('toasted.error.try_reload'));
                }

                this.$logger.error('Can not create sign up bonus link.', err);
            } finally {
                this.loading = false;
            }
        },
        deleteTokenSignupBonusLink: async function() {
            if (!this.hasSignUpBonusLink) {
                return;
            }

            this.loadingModal = true;
            try {
                await this.$axios.single.delete(
                    this.$routing.generate('delete_token_sign_up_bonus_link', {
                        tokenName: this.tokenName,
                    })
                );

                this.hasSignUpBonusLink = false;
                this.notifySuccess(this.$t('page.token_settings.tab.sign_up.delete_success'));
                this.$emit('bonus-link-changed', null);
            } catch (err) {
                if (HTTP_ACCESS_DENIED === err.response.status && err.response.data.message) {
                    this.notifyError(err.response.data.message);
                } else {
                    this.notifyError(this.$t('toasted.error.try_reload'));
                }

                this.$logger.error('Can not delete token bonus link.', err);
            } finally {
                this.loadingModal = false;
            }
        },
    },
    watch: {
        isRewardValid: function(value) {
            this.errorMessage = !value
                ? this.$t('page.token_settings.tab.sign_up.invalid_reward', {
                    minTokenReward: this.minTokenReward,
                    tokenName: this.tokenName,
                })
                : '';
        },
    },
};
</script>
