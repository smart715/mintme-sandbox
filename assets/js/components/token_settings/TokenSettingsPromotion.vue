<template>
    <div>
        <template v-if="activeSubTab === PROMOTION_TABS.bounty">
            <div class="card mt-2 p-3 overflow-auto">
                <bounties
                    is-owner
                    :token-name="getTokenName"
                    :token-url="tokenUrl"
                    :bounties="getBounties"
                    :loaded="loaded"
                    :actions-loaded="actionsLoaded"
                    :token-avatar="getTokenAvatar"
                    is-setting-page
                    @open-add-modal="openAddBountyModal"
                    @open-finalize-modal="openFinalizeModal"
                    @on-edit="openEditBountyModal"
                    @on-delete="openRemoveBountyModal"
                    @on-summary="openSummaryModal"
                    @accept-volunteer="acceptVolunteer"
                    @open-volunteer-modal="openVolunteerModalAction"
                />
            </div>
        </template>
        <template v-if="activeSubTab === PROMOTION_TABS.token_shop">
            <div class="card mt-2 p-3 overflow-auto">
                <rewards
                    is-owner
                    :token-name="getTokenName"
                    :token-url="tokenUrl"
                    :rewards="getRewards"
                    :loaded="loaded"
                    :is-setting-page="true"
                    :actions-loaded="actionsLoaded"
                    :token-avatar="getTokenAvatar"
                    @open-add-modal="openAddRewardModal"
                    @open-finalize-modal="openFinalizeModal"
                    @on-edit="openEditRewardModal"
                    @on-delete="openRemoveRewardModal"
                    @on-summary="openSummaryModal"
                />
            </div>
        </template>
        <template v-if="activeSubTab === PROMOTION_TABS.airdrop">
            <div class="card mt-2 p-3">
                <h5 class="card-title" v-html="$t('page.token_settings.tab.promotion.airdrop')"></h5>
                <div class="row">
                    <div class="col-12 col-md-8">
                        <token-airdrop-campaign
                            :token-name="getTokenName"
                            :token-avatar="getTokenAvatar"
                            :airdrop-params="airdropParams"
                            :facebook-url="getSocialUrls.facebookUrl"
                            :youtube-client-id="youtubeClientId"
                            :youtube-channel-id="getSocialUrls.youtubeChannelId"
                            :current-locale="currentLocale"
                            @updated-facebook="setFacebookUrl"
                            @updated-youtube="setYoutubeChannelId"
                        />
                    </div>
                    <div class="col">
                        <h5 class="text-uppercase">{{ $t('page.token_settings.tips') }}</h5>
                        {{ $t('page.token_settings.tab.promotion.airdrop_tips') }}
                    </div>
                </div>
            </div>
        </template>
        <template v-if="activeSubTab === PROMOTION_TABS.discord_rewards">
            <div class="card mt-2 p-3">
                <h5 class="card-title d-flex align-items-center">
                    <div v-html="$t('page.token_settings.tab.promotion.discord')"></div>
                    <guide class="tooltip-center font-size-tooltip d-md-none mtn-3">
                        <template slot="header">
                            {{ $t('page.token_settings.tips') }}
                        </template>
                        <template slot="body">
                            {{ $t('page.token_settings.tab.promotion.discord_tips') }}
                        </template>
                    </guide>
                </h5>
                <div class="row">
                    <div class="col-12 col-md-8">
                        <discord-rewards-edit
                            :token-name="getTokenName"
                            :token-avatar="getTokenAvatar"
                            :auth-url="discordAuthUrl"
                        />
                    </div>
                    <div class="col d-none d-md-block">
                        <h5 class="text-uppercase">{{ $t('page.token_settings.tips') }}</h5>
                        {{ $t('page.token_settings.tab.promotion.discord_tips') }}
                    </div>
                </div>
            </div>
        </template>
        <template v-if="activeSubTab === PROMOTION_TABS.signup_bonus">
            <div class="card mt-2 p-3">
                <h5 class="card-title" v-html="$t('page.token_settings.tab.sign_up.header')" />
                <div class="row">
                    <div class="col-12 col-md-7 mb-2">
                        <token-signup-bonus-link
                            :token-name="getTokenName"
                            :token-avatar="getTokenAvatar"
                            :signup-bonus-params="signupBonusParams"
                            @bonus-link-changed="setBonusLink"
                        />
                    </div>
                    <div class="col">
                        <h5 class="text-uppercase tips-title">{{ $t('page.token_settings.tips') }}</h5>
                        <div v-if="tokenSignUpBonusLink">
                            <copy-link
                                class="code-copy c-pointer ml-2"
                                :content-to-copy="tokenSignUpBonusLink"
                            >
                                <span class="text-reset link d-inline highlight">
                                    {{ truncateMiddleFunc(tokenSignUpBonusLink, 30) }}
                                </span>
                                <font-awesome-icon
                                    :icon="['far', 'copy']"
                                    class="hover-icon"
                                />
                            </copy-link>
                            <br/>
                            {{ $t('page.token_settings.tab.sign_up.tips.share') }}
                        </div>
                        <div v-else>
                            {{ $t('page.token_settings.tab.sign_up.tips.create') }}
                        </div>
                    </div>
                </div>
            </div>
        </template>
        <template v-if="activeSubTab === PROMOTION_TABS.token_promotion">
            <div class="card mt-2 p-3">
                <h5 class="card-title" v-html="$t('page.token_settings.token_promotion.title')"></h5>
                <div class="row">
                    <div class="col-12 col-md-5">
                        <token-promotions
                            :token-name="getTokenName"
                            :disabled-services-config="disabledServicesConfig"
                            :tariffs="tokenPromotionTariffs"
                            :is-created-on-mintme-site="isCreatedOnMintmeSite"
                        />
                    </div>
                    <div class="col">
                        <h5 class="text-uppercase">{{ $t('page.token_settings.tips') }}</h5>
                        <span v-html="$t('page.token_settings.token_promotion.hint')"></span>
                    </div>
                </div>
            </div>
        </template>
        <div v-if="rewardsEnabled" class="mt-2">
            <div class="row">
                <add-edit-reward-bounties-modal
                    :visible="showAddEditModal"
                    :token-name="getTokenName"
                    :type="modalRewardType"
                    :edit-item="currentBountyRewardItem"
                    :modal-type="addEditModalType"
                    @close="closeAddEditModal"
                ></add-edit-reward-bounties-modal>
                <finalize-rewards-bounties-modal
                    :visible="showFinalizeModal"
                    :reward="finalizedOrder"
                    :token-avatar="getTokenAvatar"
                    :token-name="getTokenName"
                    :disabled-services-config="disabledServicesConfig"
                    :actions-loaded="actionsLoaded"
                    :service-unavailable="serviceUnavailable"
                    @close-modal="showFinalizeModal = false"
                >
                </finalize-rewards-bounties-modal>
                <confirm-modal
                    :visible="showRemoveRewardModal"
                    @confirm="confirmRemoveRewardItem(true)"
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
                <confirm-modal
                    :visible="showRefundModal"
                    @confirm="refundBountyMember"
                    @close="showRefundModal = false"
                >
                    {{ $t('reward.item.refund.confirm', translationContext) }}
                </confirm-modal>
                <confirm-modal
                    :visible="showConfirmTransactionsModal"
                    :show-cancel-button="false"
                    :show-image="false"
                    @confirm="showSummaryModal = true"
                    @close="closeConfirmTransactionsModal"
                >
                    <template slot="confirm">
                        {{ $t('deposit_modal.ok') }}
                    </template>
                    {{confirmTransactionMessage}}
                </confirm-modal>
                <summary-rewards-bounties-modal
                    :visible="showSummaryModal"
                    :token-avatar="getTokenAvatar"
                    :item="currentBountyRewardItem"
                    :actions-loaded="actionsLoaded"
                    @close="showSummaryModal = false"
                    @edit="openEditModal(currentBountyRewardItem.type, currentBountyRewardItem)"
                    @remove="openRemoveModal(currentBountyRewardItem.type, currentBountyRewardItem)"
                    @accept-member="acceptMember"
                    @refund-member="refundMember"
                    @reject-member="rejectMember"
                    @save-participant-status="saveParticipantStatus"
                >
                </summary-rewards-bounties-modal>
            </div>
        </div>
    </div>
</template>

<script>
import {mapGetters, mapMutations} from 'vuex';
import TokenSignupBonusLink from '../token/TokenSignupBonusLink';
import TokenAirdropCampaign from '../token/airdrop_campaign/TokenAirdropCampaign';
import DiscordRewardsEdit from '../token/discord/DiscordRewardsEdit';
import TokenPromotions from '../token/TokenPromotions';
import Bounties from '../bountiesAndRewards/Bounties';
import Rewards from '../bountiesAndRewards/Rewards';
import AddEditRewardBountiesModal from '../bountiesAndRewards/modal/AddEditRewardBountiesModal';
import FinalizeRewardsBountiesModal from '../bountiesAndRewards/modal/FinalizeRewardsBountiesModal';
import {
    ADD_TYPE_REWARDS_MODAL,
    EDIT_TYPE_REWARDS_MODAL,
    TYPE_BOUNTY,
    TYPE_REWARD,
    VOLUNTEER_REFUND_TYPE,
    VOLUNTEER_REMOVE_TYPE,
    TOKEN_SETTINGS_PROMOTION_TABS as PROMOTION_TABS,
} from '../../utils/constants';
import ConfirmModal from '../modal/ConfirmModal';
import SummaryRewardsBountiesModal from '../bountiesAndRewards/modal/SummaryRewardsBountiesModal';
import {
    BountyMemberMixin,
    NotificationMixin,
    UserMixin,
} from '../../mixins';
import CopyLink from '../CopyLink';
import {toMoney} from '../../utils';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import stripHTMLMixin from '../../mixins/filters/stripHTML';
import TruncateFilterMixin from '../../mixins/filters/truncate';
import Guide from '../Guide';

export default {
    name: 'TokenSettingsPromotion',
    mixins: [
        NotificationMixin,
        TruncateFilterMixin,
        UserMixin,
        stripHTMLMixin,
        BountyMemberMixin,
    ],
    props: {
        airdropParams: Object,
        signupBonusParams: Object,
        youtubeClientId: String,
        youtubeChannelId: String,
        currentLocale: String,
        discordAuthUrl: String,
        rewards: Array,
        bounties: Array,
        showSummary: Boolean,
        reward: {
            type: Object,
            required: false,
            default: null,
        },
        disabledServicesConfig: String,
        rewardsEnabled: Boolean,
        activeSubTab: String,
        tokenPromotionTariffs: Array,
        isCreatedOnMintmeSite: Boolean,
    },
    components: {
        TokenAirdropCampaign,
        DiscordRewardsEdit,
        Bounties,
        Rewards,
        AddEditRewardBountiesModal,
        FinalizeRewardsBountiesModal,
        ConfirmModal,
        SummaryRewardsBountiesModal,
        TokenSignupBonusLink,
        TokenPromotions,
        FontAwesomeIcon,
        CopyLink,
        Guide,
    },
    data() {
        return {
            showSummaryModal: false,
            showAddEditModal: false,
            showFinalizeModal: false,
            showRemoveRewardModal: false,
            showConfirmTransactionsModal: false,
            finalizedOrder: {},
            modalRewardType: TYPE_REWARD,
            showVolunteerModal: false,
            currentVolunteer: null,
            currentBountyRewardItem: {
                title: '',
                volunteers: [],
                participants: [],
            },
            addEditModalType: ADD_TYPE_REWARDS_MODAL,
            tokenSignUpBonusLink: null,
            volunteerModalType: null,
            isDeletingFromSummary: false,
            showRefundModal: false,
            PROMOTION_TABS,
        };
    },
    mounted() {
        this.setRewards(this.rewards);
        this.setBounties(this.bounties);

        if (this.showSummary) {
            const reward = this.reward;
            reward.price = toMoney(reward.price, reward.token.subunit);
            this.currentBountyRewardItem = reward;
            this.showSummaryModal = true;
        }
    },
    computed: {
        ...mapGetters('tokenSettings', [
            'getTokenName',
            'getSocialUrls',
            'getTokenAvatar',
        ]),
        ...mapGetters('rewardsAndBounties', {
            getRewards: 'getRewards',
            getBounties: 'getBounties',
            loaded: 'getLoaded',
        }),
        ...mapGetters('tradeBalance', {
            balances: 'getBalances',
            serviceUnavailable: 'isServiceUnavailable',
        }),
        actionsLoaded() {
            return null !== this.balances || this.serviceUnavailable;
        },
        tokenUrl() {
            return this.$routing.generate('token_show_intro', {name: this.getTokenName});
        },
        isRewardType() {
            return TYPE_REWARD === this.modalRewardType;
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
        confirmTransactionMessage() {
            return this.isRewardType
                ? this.$t('reward.delete.confirm_transactions')
                : this.$t('bounty.delete.confirm_transactions');
        },
        isBountyType() {
            return TYPE_BOUNTY === this.modalRewardType;
        },
        removeRewardModalMessage() {
            return this.isBountyType
                ? this.$t('bounties_rewards.manage.bounty.item.remove.confirm', this.translationContext)
                : this.$t('bounties_rewards.manage.reward.item.remove.confirm', this.translationContext);
        },
    },
    methods: {
        ...mapMutations('tokenSettings', [
            'setFacebookUrl',
            'setYoutubeChannelId',
        ]),
        ...mapMutations('rewardsAndBounties', [
            'removeReward',
            'removeBounty',
            'editBounty',
            'editReward',
            'setRewards',
            'setBounties',
        ]),
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
        openEditBountyModal: function(item) {
            this.openEditModal(TYPE_BOUNTY, item);
        },
        openEditRewardModal: function(item) {
            this.openEditModal(TYPE_REWARD, item);
        },
        openRemoveRewardModal: function(item) {
            this.openRemoveModal(TYPE_REWARD, item);
        },
        openRemoveBountyModal: function(item) {
            this.openRemoveModal(TYPE_BOUNTY, item);
        },
        openRemoveModal: function(type, item, isOpenedFromSummary = false) {
            this.modalRewardType = type;
            this.currentBountyRewardItem = item;

            if (this.hasPendingParticipants(item)) {
                this.showConfirmTransactionsModal = true;
            } else {
                this.showRemoveRewardModal = true;
                this.isDeletingFromSummary = isOpenedFromSummary;
            }
        },
        closeRemoveModal: function() {
            this.showRemoveRewardModal = false;
        },
        openSummaryModal: function(item) {
            this.currentBountyRewardItem = item;
            this.showSummaryModal = true;
        },
        acceptVolunteer: async function(volunteer) {
            if (volunteer.isRequesting) {
                return;
            }

            this.$set(volunteer, 'isRequesting', true);

            await this.acceptMember({
                slug: volunteer.reward.slug,
                memberId: volunteer.id,
            });

            this.$set(volunteer, 'isRequesting', false);
        },
        openAddRewardModal: function() {
            this.openAddModal(TYPE_REWARD);
        },
        openAddBountyModal: function() {
            this.openAddModal(TYPE_BOUNTY);
        },
        openAddModal: function(type) {
            this.addEditModalType = ADD_TYPE_REWARDS_MODAL;
            this.modalRewardType = type;
            this.showAddEditModal = true;
        },
        closeAddEditModal: function() {
            this.showAddEditModal = false;
        },
        setBonusLink: function(link) {
            this.tokenSignUpBonusLink = link;
        },
        openVolunteerModalAction(member, type) {
            this.volunteerModalType = type;
            this.currentVolunteer = member;
            this.showVolunteerModal = true;
        },
        rejectMember(member) {
            this.openVolunteerModalAction(member, 'reject');
        },
        refundMember(member) {
            this.currentVolunteer = member;
            this.showRefundModal = true;
        },
        hasPendingParticipants: function(item) {
            return item.participants.some((p) => p.isPending);
        },
        closeConfirmTransactionsModal: function() {
            this.showConfirmTransactionsModal = false;
            this.showSummaryModal = true;
        },
    },
};
</script>
