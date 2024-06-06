<template>
    <div>
        <modal
            :visible="visible"
            @close="closeModal"
        >
            <template v-slot:header>
                {{ $t('rewards_bounties.finalize.get') }}
                <span
                    v-if="isLongTitle"
                    class="ml-1"
                >
                    {{ reward.title | truncate(13) }}
                </span>
                <span v-else class="ml-1">
                    {{ reward.title }}
                </span>
            </template>
            <template slot="body">
                <div class="text-left">
                    <div
                        v-if="showBalance"
                        class="d-flex justify-content-start align-items-center"
                    >
                        {{ $t('balance') }}
                        <coin-balance
                            class="mx-1"
                            :coin-name="tokenName"
                            with-bonus
                        />
                        <template v-if="!serviceUnavailable">
                            <coin-avatar
                                :image="tokenAvatar"
                                :is-user-token="true"
                            />
                            {{ tokenName }}
                        </template>
                    </div>
                    <div class="pb-3 text-break">
                        <div v-if="!rewardQuantityIsZero">
                            <span>{{ reward.title }}</span>
                            <span v-html="$t('rewards_bounties.finalize.left', translationContext)"/>
                            <br>
                        </div>
                        <span v-if="reward.link">
                            <a :href="reward.link" target="_blank" rel="nofollow noopener">{{ reward.link }}</a><br>
                        </span>
                        {{ reward.description }}
                    </div>
                    <m-textarea
                        :max-length="maxLength"
                        :rows="rows"
                        :value="note"
                        :label="$t('rewards_bounties.add_note')"
                        :counter="true"
                        @input="inputNote"
                        @focus="onTextareaFocus"
                    ></m-textarea>
                    <div v-if="showBalance">
                        {{ $t('rewards_bounties.current_balance') }}
                        <coin-balance
                            class="text-primary mx-1"
                            :coin-name="tokenName"
                            with-bonus
                        />
                        <template v-if="!serviceUnavailable">
                            <coin-avatar
                                :image="tokenAvatar"
                                :is-user-token="true"
                            />
                            <span class="text-primary">{{ tokenName }}</span>
                        </template>
                        <span
                            v-if="showAddMoreFunds"
                            :class="getDepositDisabledClasses(tokenName)"
                            @click="openDepositModal(tokenName)"
                        >
                            {{ $t('rewards_bounties.add_more_funds') }}
                        </span>
                    </div>
                    <div>
                        {{ $t('rewards_bounties.price') }}
                        <span class="text-primary">
                            {{ reward.price }}
                            <coin-avatar
                                :image="tokenAvatar"
                                :is-user-token="true"
                            />
                            {{ tokenName }}
                        </span>
                    </div>
                    <div v-if="!$v.note.maxLength" class="text-danger text-center small">
                        {{ $t('rewards_bounties.add_note.max_length', translationContext)  }}
                    </div>
                    <div class="col-12 pt-2 text-center">
                        <template v-if="!serviceUnavailable">
                            <button
                                class="btn btn-primary"
                                @click="proceedAddButton"
                                :disabled="payBtnDisabled"
                            >
                                <span v-if="isBountyType" v-html="this.$t('rewards_bounties.finalize.volunteer')"/>
                                <span v-else v-html="this.$t('rewards_bounties.finalize.pay')"/>
                                <font-awesome-icon
                                    v-if="!actionsLoaded"
                                    icon="circle-notch"
                                    class="loading-spinner text-white"
                                    fixed-width
                                    spin
                                />
                            </button>
                            <span
                                class="btn-cancel pl-3 c-pointer"
                                @click="closeModal"
                            >
                                <slot name="cancel">{{ $t('cancel') }}</slot>
                            </span>
                        </template>
                        <div v-else class="text-danger">
                            {{ this.$t('toasted.error.service_unavailable_short') }}
                        </div>
                    </div>
                </div>
            </template>
        </modal>
        <confirm-modal
            :visible="rewardConfirmationVisible"
            @confirm="addMember"
            @close="rewardConfirmationVisible = false"
        >
            <p
                class="text-white modal-title pt-2 text-break"
                v-html="$t('reward.pay.confirmation', translationContext)"
            ></p>
        </confirm-modal>
        <deposit-modal
            :visible="showDepositModal"
            :currency="tokenName"
            :is-token="isTokenModal"
            :is-created-on-mintme-site="isCreatedOnMintmeSite"
            :is-owner="isOwner"
            :token-networks="currentTokenNetworks"
            :crypto-networks="currentCryptoNetworks"
            :subunit="currentSubunit"
            :no-close="false"
            :add-phone-alert-visible="addPhoneAlertVisible"
            :deposit-add-phone-modal-visible="depositAddPhoneModalVisible"
            @close-confirm-modal="closeConfirmModal"
            @phone-alert-confirm="onPhoneAlertConfirm(tokenName)"
            @close-add-phone-modal="closeAddPhoneModal"
            @deposit-phone-verified="onDepositPhoneVerified"
            @close="closeDepositModal"
        />
    </div>
</template>

<script>
import Modal from '../../modal/Modal';
import ConfirmModal from '../../modal/ConfirmModal';
import {
    HTTP_OK,
    TYPE_BOUNTY,
    REWARD_PENDING,
    HTTP_NOT_FOUND,
    REWARD_BOUNTIES_TEXT_AREA,
    HTTP_ACCESS_DENIED,
    HTTP_UNAUTHORIZED,
    tokenDeploymentStatus,
    REWARD_TITLE_TRUNCATE_LENGTH,
} from '../../../utils/constants';
import {generateCoinAvatarHtml} from '../../../utils';
import {
    NotificationMixin,
    DepositModalMixin,
} from '../../../mixins';
import {maxLength} from 'vuelidate/lib/validators';
import {MTextarea} from '../../UI';
import {mapGetters, mapMutations} from 'vuex';
import CoinBalance from '../../CoinBalance';
import CoinAvatar from '../../CoinAvatar';
import DepositModal from '../../modal/DepositModal';
import {VBTooltip} from 'bootstrap-vue';
import {faCircleNotch} from '@fortawesome/free-solid-svg-icons';
import {library} from '@fortawesome/fontawesome-svg-core';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import TruncateFilterMixin from '../../../mixins/filters/truncate';
import Decimal from 'decimal.js';

library.add(faCircleNotch);

const NOTE_MAX_LENGTH = 255;

export default {
    name: 'FinalizeRewardsBountiesModal',
    mixins: [
        NotificationMixin,
        DepositModalMixin,
        TruncateFilterMixin,
    ],
    directives: {
        'b-tooltip': VBTooltip,
    },
    components: {
        Modal,
        ConfirmModal,
        MTextarea,
        CoinBalance,
        CoinAvatar,
        DepositModal,
        FontAwesomeIcon,
    },
    props: {
        visible: Boolean,
        reward: Object,
        tokenName: String,
        isOwner: Boolean,
        isCreatedOnMintmeSite: Boolean,
        actionsLoaded: Boolean,
        serviceUnavailable: Boolean,
        tokenAvatar: String,
    },
    data() {
        return {
            note: '',
            rewardConfirmationVisible: false,
            payClickDisabled: false,
            rows: REWARD_BOUNTIES_TEXT_AREA.rows,
            maxLength: REWARD_BOUNTIES_TEXT_AREA.maxLength,
        };
    },
    computed: {
        ...mapGetters('user', {
            userId: 'getId',
        }),
        ...mapGetters('tokenInfo', [
            'getDeploymentStatus',
        ]),
        isBountyType: function() {
            return TYPE_BOUNTY === this.reward.type;
        },
        payBtnDisabled: function() {
            return !this.$v.note.maxLength
                || this.payClickDisabled
                || !this.actionsLoaded
                || this.serviceUnavailable
            ;
        },
        rewardQuantityIsZero: function() {
            return new Decimal(this.reward.quantity || 0).isZero();
        },
        translationContext: function() {
            return this.visible ? {
                leftAmount: parseInt(this.reward.quantity) - (this.reward.activeParticipantsAmount || 0),
                price: this.reward.price,
                tokenName: this.tokenName,
                title: this.reward.title,
                maxLength: NOTE_MAX_LENGTH,
                tokenAvatar: generateCoinAvatarHtml({image: this.tokenAvatar, isUserToken: true}),
            } : {};
        },
        isLoggedIn() {
            return !!this.userId;
        },
        showAddMoreFunds() {
            return this.getDeploymentStatus === tokenDeploymentStatus.deployed;
        },
        showBalance() {
            return !this.isBountyType && this.isLoggedIn;
        },
        isLongTitle() {
            return this.reward?.title?.length > REWARD_TITLE_TRUNCATE_LENGTH;
        },
    },
    methods: {
        ...mapMutations('rewardsAndBounties', [
            'editBounty',
            'editReward',
            'removeBounty',
            'removeReward',
        ]),
        closeModal: function() {
            this.$emit('close-modal');
            this.note = '';
        },
        proceedAddButton: function() {
            if (this.isBountyType) {
                this.addMember();
                return;
            }

            this.rewardConfirmationVisible = true;
        },
        removeItem(reward) {
            TYPE_BOUNTY === reward.type
                ? this.removeBounty(reward)
                : this.removeReward(reward);
        },
        editItem(reward) {
            TYPE_BOUNTY === reward.type
                ? this.editBounty(reward)
                : this.editReward(reward);
        },
        addMember: async function() {
            this.payClickDisabled = true;

            if (!this.isLoggedIn) {
                this.goToLogin();
                return;
            }

            try {
                const response = await this.$axios.single.post(
                    this.$routing.generate('reward_add_member', {rewardSlug: this.reward.slug}),
                    {note: this.note}
                );

                if (HTTP_OK === response.status && response.data.hasOwnProperty('error')) {
                    this.notifyInfo(response.data.error);
                } else if (HTTP_OK === response.status) {
                    const reward = response.data;

                    if (reward.quantityReached && !reward.participants.find((p) => REWARD_PENDING === p.status)) {
                        this.removeItem(reward);
                    } else {
                        this.editItem(reward);
                    }

                    if (this.isBountyType) {
                        this.notifySuccess(this.$t('bounty.purchased'));
                    } else {
                        this.notifySuccess(this.$t('reward.purchased'));
                    }
                }
            } catch (error) {
                const status = error.response.status;

                if (HTTP_NOT_FOUND === status) {
                    if (this.isBountyType) {
                        this.notifyError(this.$t('api.404.bounty'));
                    } else {
                        this.notifyError(this.$t('api.404.reward'));
                    }
                } else if (
                    (HTTP_UNAUTHORIZED === status || HTTP_ACCESS_DENIED === status) &&
                    error.response.data.message
                ) {
                    this.notifyError(error.response.data.message);
                } else {
                    this.notifyError(this.$t('toasted.error.try_later'));
                }

                this.$logger.error('error', 'Error during add member for reward/bounty', error);
            } finally {
                this.closeModal();
                this.payClickDisabled = false;
            }
        },
        inputNote: function(value) {
            this.note = value;
        },
        onTextareaFocus() {
            if (!this.isLoggedIn) {
                this.goToLogin();
            }
        },
        goToLogin() {
            location.href = this.$routing.generate('login', {}, true);
        },
    },
    validations() {
        return {
            note: {
                maxLength: maxLength(NOTE_MAX_LENGTH),
            },
        };
    },
};
</script>
