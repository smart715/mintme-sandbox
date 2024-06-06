import {toMoney} from '../../utils';
import {REWARD_COMPLETED, TOK} from '../../utils/constants';

const storage = {
    namespaced: true,
    state: {
        rewards: [],
        bounties: [],
        rewardsMaxLimit: 10,
        bountiesMaxLimit: 10,
        volunteers: [],
        rewardsLoaded: false,
        bountiesLoaded: false,
    },
    getters: {
        getRewards(state) {
            return state.rewards
                .map((reward) => {
                    reward.price = toMoney(reward.price, TOK.subunit);
                    reward.hasPendingParticipants = 0 !== getPendingRewardParticipants(reward.participants).length;

                    return reward;
                })
                .sort((a, b) => {
                    // Sort from oldest to newest
                    const aCreatedAt = parseInt(a.createdAt);
                    const bCreatedAt = parseInt(b.createdAt);

                    if (aCreatedAt > bCreatedAt) {
                        return 1;
                    }

                    if (bCreatedAt > aCreatedAt) {
                        return -1;
                    }

                    return 0;
                });
        },
        getBounties(state) {
            return state.bounties
                .map((reward) => {
                    reward.price = toMoney(reward.price, TOK.subunit);
                    reward.hasPendingParticipants = 0 !== getPendingBountyParticipants(reward.participants).length
                        || (reward.volunteers && reward.volunteers.length);
                    return reward;
                })
                .sort((a, b) => {
                    // Sort from oldest to newest
                    const aCreatedAt = parseInt(a.createdAt);
                    const bCreatedAt = parseInt(b.createdAt);

                    if (aCreatedAt > bCreatedAt) {
                        return 1;
                    }

                    if (bCreatedAt > aCreatedAt) {
                        return -1;
                    }

                    return 0;
                });
        },
        getVolunteers(state, getters) {
            const volunteers = [];
            getters.getBounties.forEach((reward) => {
                reward.volunteers.forEach((volunteer) => {
                    volunteer.reward = reward;
                    volunteers.push(volunteer);
                });
            });
            return volunteers;
        },
        getLoaded(state) {
            return state.rewardsLoaded && state.bountiesLoaded;
        },
        getRewardsMaxLimit(state) {
            return state.rewardsMaxLimit;
        },
        getBountiesMaxLimit(state) {
            return state.bountiesMaxLimit;
        },
    },
    mutations: {
        setRewards(state, rewards) {
            state.rewards = rewards;
            state.rewardsLoaded = true;
        },
        setBounties(state, bounties) {
            state.bounties = bounties;
            state.bountiesLoaded = true;
        },
        setRewardsMaxLimit(state, payload) {
            state.rewardsMaxLimit = payload;
        },
        setBountiesMaxLimit(state, payload) {
            state.bountiesMaxLimit = payload;
        },
        addReward(state, reward) {
            state.rewards.push(reward);
        },
        addBounty(state, bounty) {
            state.bounties.push(bounty);
        },
        removeReward(state, item) {
            state.rewards = state.rewards.filter((reward) => reward.slug !== item.slug);
        },
        removeBounty(state, item) {
            state.bounties = state.bounties.filter((bounty) => bounty.slug !== item.slug);
        },
        editBounty(state, item) {
            (state.bounties = state.bounties.filter((bounty) => bounty.slug !== item.slug)).push(item);
        },
        editReward(state, item) {
            (state.rewards = state.rewards.filter((reward) => reward.slug !== item.slug)).push(item);
        },
    },
};

const getPendingRewardParticipants = (participants) =>
    participants?.filter((p) => p.isPending || p.status === REWARD_COMPLETED) || [];
const getPendingBountyParticipants = (participants) => participants?.filter((p) => p.isPending) || [];

export default storage;
