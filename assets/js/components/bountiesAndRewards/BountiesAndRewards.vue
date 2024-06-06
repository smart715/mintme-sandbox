<template>
    <div>
        <bounties
            v-if="showBounties"
            class="mt-4"
            hide-actions
            :token-name="tokenName"
            :token-avatar="tokenAvatar"
            :is-owner="isOwner"
            :bounties="getBounties"
            :loaded="loaded"
            :is-mobile-screen="isMobileScreen"
            :actions-loaded="actionsLoaded"
            @open-add-modal="openAddModal"
            @open-finalize-modal="openFinalizeModal"
        />
        <rewards
            v-if="showRewards"
            class="mt-4"
            hide-actions
            :token-name="tokenName"
            :token-avatar="tokenAvatar"
            :is-owner="isOwner"
            :rewards="getRewards"
            :loaded="loaded"
            :is-mobile-screen="isMobileScreen"
            :actions-loaded="actionsLoaded"
            @open-add-modal="openAddModal"
            @open-finalize-modal="openFinalizeModal"
        />
        <add-edit-reward-bounties-modal
            :visible="showAddEditModal"
            :token-name="tokenName"
            :token-avatar="tokenAvatar"
            :type="modalRewardType"
            :modal-type="addEditModalType"
            :edit-item="currentBountyRewardItem"
            @close="closeAddModal"
        />
        <finalize-rewards-bounties-modal
            :visible="showFinalizeModal"
            :reward="finalizedOrder"
            :token-name="tokenName"
            :is-owner="isOwner"
            :disabled-services-config="disabledServicesConfig"
            :is-created-on-mintme-site="isCreatedOnMintmeSite"
            :disabled-cryptos="disabledCryptos"
            :is-user-blocked="isUserBlocked"
            :token-avatar="tokenAvatar"
            :actions-loaded="actionsLoaded"
            :service-unavailable="serviceUnavailable"
            @close-modal="closeFinalizeModal"
        />
        <confirm-modal
            :visible="showRemoveRewardModal"
            @confirm="confirmRemoveRewardItem"
            @close="closeRemoveModal"
        >
            {{ removeRewardModalMessage }}
        </confirm-modal>
        <confirm-modal
            :visible="showVolunteerModal"
            @confirm="proceedVolunteerAction"
            @close="showVolunteerModal = false"
        >
            {{ volunteerConfirmationMessage }}
        </confirm-modal>
        <summary-rewards-bounties-modal
            :visible="showSummaryModal"
            :item="currentBountyRewardItem"
            :token-avatar="tokenAvatar"
            @close="showSummaryModal = false"
            :actions-loaded="actionsLoaded"
            @edit="openEditModal(currentBountyRewardItem.type, currentBountyRewardItem)"
            @remove="openRemoveModal(currentBountyRewardItem.type, currentBountyRewardItem)"
            @accept-member="acceptMember"
            @reject-member="rejectMember"
        />
    </div>
</template>

<script>
import Bounties from './Bounties';
import Rewards from './Rewards';
import AddEditRewardBountiesModal from './modal/AddEditRewardBountiesModal';
import FinalizeRewardsBountiesModal from './modal/FinalizeRewardsBountiesModal';
import SummaryRewardsBountiesModal from './modal/SummaryRewardsBountiesModal';
import ConfirmModal from '../modal/ConfirmModal';
import {mapGetters, mapMutations} from 'vuex';
import {toMoney} from '../../utils';
import {
    ADD_TYPE_REWARDS_MODAL,
    EDIT_TYPE_REWARDS_MODAL,
    TYPE_BOUNTY,
    TYPE_REWARD,
    VOLUNTEER_REFUND_TYPE,
    VOLUNTEER_REMOVE_TYPE,
    TOK,
} from '../../utils/constants';
import {BountyMemberMixin, NotificationMixin, UserMixin} from '../../mixins';

export default {
    name: 'BountiesAndRewards',
    components: {
        FinalizeRewardsBountiesModal,
        Bounties,
        Rewards,
        AddEditRewardBountiesModal,
        ConfirmModal,
        SummaryRewardsBountiesModal,
    },
    mixins: [
        NotificationMixin,
        UserMixin,
        BountyMemberMixin,
    ],
    props: {
        tokenName: String,
        tokenAvatar: String,
        isOwner: Boolean,
        isMobileScreen: Boolean,
        showFinalized: Boolean,
        showSummary: Boolean,
        rewards: Array,
        bounties: Array,
        rewardsMaxLimit: Number,
        bountiesMaxLimit: Number,
        isCreatedOnMintmeSite: Boolean,
        disabledServicesConfig: String,
        disabledCryptos: Array,
        isUserBlocked: Boolean,
        reward: {
            type: Object,
            required: false,
            default: null,
        },
    },
    data() {
        return {
            showAddEditModal: false,
            showFinalizeModal: false,
            finalizedOrder: {},
            addModalType: '',
            showRewards: false,
            showBounties: false,
            showSummaryModal: false,
            showRemoveRewardModal: false,
            modalRewardType: TYPE_REWARD,
            currentBountyRewardItem: {
                title: '',
                volunteers: [],
                participants: [],
            },
            addEditModalType: ADD_TYPE_REWARDS_MODAL,
            showVolunteerModal: false,
            currentVolunteer: null,
            volunteerModalType: null,
        };
    },
    mounted() {
        if (!this.isRewardsInitialized) {
            this.setRewards(this.rewards);
            this.setBounties(this.bounties);
            this.setRewardsMaxLimit(this.rewardsMaxLimit);
            this.setBountiesMaxLimit(this.bountiesMaxLimit);
            this.setIsRewardsInitialized(true);
        }

        if (this.isOwner || 0 < this.rewards.length) {
            this.showRewards = true;
        }

        if (this.isOwner || 0 < this.bounties.length) {
            this.showBounties = true;
        }

        if (this.showFinalized) {
            const reward = this.reward;
            reward.price = toMoney(reward.price, TOK.subunit);
            this.finalizedOrder = reward;
            this.showFinalizeModal = true;
        }

        if (this.showSummary) {
            const reward = this.reward;
            reward.price = toMoney(reward.price, TOK.subunit);
            this.currentBountyRewardItem = reward;
            this.showSummaryModal = true;
        }
    },
    computed: {
        ...mapGetters('rewardsAndBounties', {
            getRewards: 'getRewards',
            getBounties: 'getBounties',
            loaded: 'getLoaded',
        }),
        ...mapGetters('pair', {
            isRewardsInitialized: 'getIsRewardsInitialized',
        }),
        ...mapGetters('tradeBalance', {
            balances: 'getBalances',
            serviceUnavailable: 'isServiceUnavailable',
        }),
        actionsLoaded() {
            return null !== this.balances || this.serviceUnavailable;
        },
        tokenUrl: function() {
            return this.$routing.generate('token_show_intro', {name: this.tokenName});
        },
        translationContext() {
            return {
                type: this.modalRewardType,
                title: this.currentBountyRewardItem?.title,
                nickname: this.getNickname(this.currentVolunteer?.user) ?? '',
                amount: toMoney(this.currentVolunteer?.price || 0),
            };
        },
        isRemoveBountyMemberType() {
            return VOLUNTEER_REMOVE_TYPE === this.volunteerModalType;
        },
        isRefundBountyMemberType() {
            return VOLUNTEER_REFUND_TYPE === this.volunteerModalType;
        },
        volunteerConfirmationMessage() {
            if (this.isRemoveBountyMemberType) {
                return this.$t('bounties_rewards.manage.member.remove.confirm', this.translationContext);
            }

            return this.$t('reward.item.refund.confirm', this.translationContext);
        },
        isBountyType() {
            return TYPE_BOUNTY === this.modalRewardType;
        },
        removeRewardModalMessage() {
            return this.isBountyType
                ? this.$t('bounty.item.remove.confirm', this.translationContext)
                : this.$t('reward.item.remove.confirm', this.translationContext);
        },
    },
    methods: {
        ...mapMutations('rewardsAndBounties', [
            'removeReward',
            'removeBounty',
            'editBounty',
            'editReward',
            'setRewards',
            'setBounties',
            'setRewardsMaxLimit',
            'setBountiesMaxLimit',
        ]),
        ...mapMutations('pair', ['setIsRewardsInitialized']),
        openEditModal: function(type, item) {
            this.currentBountyRewardItem = item;
            this.addEditModalType = EDIT_TYPE_REWARDS_MODAL;
            this.modalRewardType = type;
            this.showAddEditModal = true;
        },
        openFinalizeModal: function(order) {
            this.finalizedOrder = order;
            this.showFinalizeModal = true;
        },
        openEditRewardModal: function(item) {
            this.openEditModal(TYPE_REWARD, item);
        },
        openRemoveRewardModal: function(item) {
            this.openRemoveModal(TYPE_REWARD, item);
        },
        openRemoveModal: function(type, item) {
            this.modalRewardType = type;
            this.currentBountyRewardItem = item;
            this.showRemoveRewardModal = true;
        },
        closeRemoveModal: function() {
            this.showRemoveRewardModal = false;
        },
        openAddModal: function(type) {
            const isRewardType = TYPE_REWARD === type;
            const maxLimit = isRewardType ? this.rewardsMaxLimit : this.bountiesMaxLimit;
            const itemsAmount = isRewardType ? this.getRewards.length : this.getBounties.length;

            if (itemsAmount >= maxLimit) {
                this.notifyError(isRewardType
                    ? this.$t('rewards_bounty.max_rewards_limit_reached', {amount: maxLimit})
                    : this.$t('rewards_bounty.max_bounty_limit_reached', {amount: maxLimit}),
                );
                return;
            }

            this.addEditModalType = ADD_TYPE_REWARDS_MODAL;
            this.modalRewardType = type;
            this.showAddEditModal = true;
        },
        closeAddModal: function() {
            this.showAddEditModal = false;
        },
        removeOpenModalFlagFromUrl: function(flag) {
            window.history.replaceState(
                {},
                '',
                window.location.href.replace(new RegExp('\\/' + flag + '\\/.*'), ''),
            );
        },
        closeFinalizeModal: function() {
            this.showFinalizeModal = false;
            this.removeOpenModalFlagFromUrl('reward-finalize');
        },
        closeSummaryModal: function() {
            this.showSummaryModal = false;
            this.removeOpenModalFlagFromUrl('reward-summary');
        },
        openVolunteerModalAction(member, type) {
            this.volunteerModalType = type;
            this.currentVolunteer = member;
            this.showVolunteerModal = true;
        },
        rejectMember(member) {
            this.openVolunteerModalAction(member, 'reject');
        },
    },
};
</script>
