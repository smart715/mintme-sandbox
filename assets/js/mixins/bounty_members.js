import {HTTP_NO_CONTENT, TYPE_BOUNTY, TYPE_REWARD, ServiceUnavailableError} from '../utils/constants';

export default {
    methods: {
        assertServiceAvailable: function() {
            if (this.serviceUnavailable) {
                throw new ServiceUnavailableError();
            }
        },
        deleteBountyMember: async function() {
            try {
                this.assertServiceAvailable();

                const response = await this.$axios.single.delete(this.$routing.generate('delete_bounty_member', {
                    slug: this.currentVolunteer.reward.slug,
                    memberId: this.currentVolunteer.id,
                }));
                const reward = response.data;

                TYPE_BOUNTY === reward.type
                    ? this.editBounty(reward)
                    : this.editReward(reward);

                if (this.currentBountyRewardItem && this.currentBountyRewardItem.slug === reward.slug) {
                    this.currentBountyRewardItem = reward;
                }

                this.notifySuccess(this.$t('bounties_rewards.manage.member.removed'));
            } catch (error) {
                this.notifyError(this.getErrorMessage(error));
                this.$logger.error('Error during remove member from reward/bounty', error);
            }
        },
        refundBountyMember: async function() {
            try {
                this.assertServiceAvailable();

                const response = await this.$axios.single.post(
                    this.$routing.generate('refund_reward', {slug: this.currentVolunteer.reward.slug}),
                    {participantId: this.currentVolunteer.id},
                );
                const reward = response.data;

                this.editReward(reward);

                if (this.currentBountyRewardItem && this.currentBountyRewardItem.slug === reward.slug) {
                    this.currentBountyRewardItem = reward;
                }

                this.notifySuccess(this.$t('bounties_rewards.manage.member.refunded'));
            } catch (error) {
                this.notifyError(this.getErrorMessage(error));
                this.$logger.error('Error during refund member for reward', error);
            }
        },
        acceptMember: async function({slug, memberId}) {
            try {
                this.assertServiceAvailable();

                const response = await this.$axios.single.post(
                    this.$routing.generate('accept_member', {slug}),
                    {memberId: memberId},
                );
                const reward = response.data.reward;

                TYPE_BOUNTY === reward.type
                    ? this.editBounty(reward)
                    : this.editReward(reward);

                this.currentBountyRewardItem = reward;
                this.notifySuccess(response.data.message);
            } catch (error) {
                this.notifyError(this.getErrorMessage(error));
                this.$logger.error('Error during add member for reward/bounty', error);
            }
        },
        saveParticipantStatus: async function({slug, member, status}) {
            try {
                this.assertServiceAvailable();

                const response = await this.$axios.single.post(
                    this.$routing.generate('change_participant_status', {slug: slug}),
                    {participantId: member.id, status},
                );

                const reward = response.data;

                this.editReward(reward);
                member.status = status;
                this.currentBountyRewardItem = reward;
                this.notifySuccess(this.$t('bounties_rewards.manage.member.delivered'));
            } catch (error) {
                this.notifyError(this.getErrorMessage(error));
                this.$logger.error('error', 'Error during set status member for reward', error);
            }
        },
        confirmRemoveRewardItem: async function(isDeletingFromSummary = false) {
            try {
                this.assertServiceAvailable();

                const response = await this.$axios.single.delete(
                    this.$routing.generate('delete_reward', {slug: this.currentBountyRewardItem.slug}),
                );
                if (HTTP_NO_CONTENT !== response.status) {
                    return;
                }

                this.modalRewardType === TYPE_REWARD
                    ? this.removeReward(this.currentBountyRewardItem)
                    : this.removeBounty(this.currentBountyRewardItem);

                this.modalRewardType === TYPE_REWARD
                    ? this.notifySuccess(this.$t('bounties_rewards.manage.reward.removed'))
                    : this.notifySuccess(this.$t('bounties_rewards.manage.bounty.removed'));
            } catch (error) {
                this.notifyError(this.getErrorMessage(error));
                this.$logger.error('Error during add member for reward/bounty', error);
            } finally {
                this.closeRemoveModal();
                if (isDeletingFromSummary) {
                    this.showSummaryModal = false;
                }
            }
        },
        proceedVolunteerAction: async function() {
            if (this.isRemoveBountyMemberType) {
                await this.deleteBountyMember();
            } else if (this.isRefundBountyMemberType) {
                await this.refundBountyMember();
            }
        },
        getErrorMessage: function(error) {
            if (error instanceof ServiceUnavailableError) {
                return this.$t('toasted.error.service_unavailable');
            }

            return error.response?.data?.message ?? this.$t('toasted.error.try_later');
        },
    },
};
