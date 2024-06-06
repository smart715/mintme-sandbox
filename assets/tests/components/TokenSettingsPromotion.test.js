import {shallowMount, createLocalVue} from '@vue/test-utils';
import TokenSettingsPromotion from '../../js/components/token_settings/TokenSettingsPromotion.vue';
import Vuex from 'vuex';
import {HTTP_NO_CONTENT, TYPE_BOUNTY, TYPE_REWARD} from '../../js/utils/constants';
import moxios from 'moxios';
import axios from 'axios';

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.use(Vuex);
    localVue.use({
        install(Vue) {
            Vue.prototype.$axios = {retry: axios, single: axios};
            Vue.prototype.$routing = {generate: (val) => val};
            Vue.prototype.$t = (val) => val;
            Vue.prototype.$toasted = {show: () => {}};
            Vue.prototype.$logger = {error: () => {}};
        },
    });
    return localVue;
}

/**
 * @return {Wrapper<vue>}
 * @param {Object} tokenSettings
 * @param {Object} rewardsAndBounties
 * @param {object} options
* @param {object} tradeBalance
 */
function mockDefaultWrapper(
    tokenSettings = {},
    rewardsAndBounties = {},
    options = {},
    tradeBalance = {}
) {
    const localVue = mockVue();
    localVue.use(Vuex);
    const store = new Vuex.Store({
        modules: {
            tokenSettings: {
                namespaced: true,
                mutations: {
                    setFacebookUrl: () => {},
                    setYoutubeChannelId: () => {},
                },
                getters: {
                    getTokenName: () => '',
                    getTokenAvatar: () => '',
                    getSocialUrls: () => ({}),
                },
                ...tokenSettings,
            },
            tradeBalance: {
                namespaced: true,
                getters: {
                    getBalances: () => {},
                    isServiceUnavailable: () => false,
                },
                ...tradeBalance,
            },
            rewardsAndBounties: {
                namespaced: true,
                getters: {
                    getRewards: () => [],
                    getBounties: () => testBounties,
                    getRewardsMaxLimit: () => 10,
                    getBountiesMaxLimit: () => 10,
                    getLoaded: () => true,
                },
                mutations: {
                    editBounty: () => {},
                    editReward: () => {},
                    removeReward: () => {},
                    removeBounty: () => {},
                    setRewards: () => {},
                    setBounties: () => {},
                },
                ...rewardsAndBounties,
            },
        },
    });

    return shallowMount(TokenSettingsPromotion, {
        localVue,
        store,
        ...options,
    });
}

const testRewards = [
    {
        participants: [],
        quantity: 999,
        slug: 'qweqwe-3',
        title: 'qweqwe',
        type: 'reward',
        volunteers: [],
    },
];

const testBounties = [
    {
        participants: [],
        quantity: 999,
        slug: 'qweqwe-3',
        title: 'qweqwe',
        type: 'bounty',
        volunteers: [],
    },
];

const volunteers = [
    {
        id: 1,
        reward: testRewards[0],
        isRequesting: false,
    },
];

describe('TokenSettingsPromotion', () => {
    beforeEach(() => {
        moxios.install();
    });

    afterEach(() => {
        moxios.uninstall();
    });

    describe('should compute "actionsLoaded" correctly', () => {
        it('when getBalances = !empty and isServiceUnavailable = false', () => {
            const wrapper = mockDefaultWrapper();

            expect(wrapper.vm.actionsLoaded).toBe(true);
        });

        it('when getBalances = null and isServiceUnavailable = false', () => {
            const wrapper = mockDefaultWrapper({}, {}, {}, {
                getters: {
                    getBalances: () => null,
                    isServiceUnavailable: () => false,
                },
            });

            expect(wrapper.vm.actionsLoaded).toBe(false);
        });

        it('when getBalances = !empty and isServiceUnavailable = true', () => {
            const wrapper = mockDefaultWrapper({}, {}, {}, {
                getters: {
                    getBalances: () => {},
                    isServiceUnavailable: () => true,
                },
            });

            expect(wrapper.vm.actionsLoaded).toBe(true);
        });
    });

    it('should compute tokenUrl correctly', () => {
        const wrapper = mockDefaultWrapper();

        expect(wrapper.vm.tokenUrl).toBe('token_show_intro');
    });

    describe('should compute isRewardType correctly', () => {
        it('when modalRewardType = TYPE_REWARD', async () => {
            const wrapper = mockDefaultWrapper();

            await wrapper.setData({
                modalRewardType: TYPE_REWARD,
            });

            expect(wrapper.vm.isRewardType).toBe(true);
        });

        it('when modalRewardType = TYPE_BOUNTY', async () => {
            const wrapper = mockDefaultWrapper();

            await wrapper.setData({
                modalRewardType: TYPE_BOUNTY,
            });

            expect(wrapper.vm.isRewardType).toBe(false);
        });
    });

    describe('should compute confirmTransactionMessage correctly', () => {
        it('when isRewardType = true', async () => {
            const wrapper = mockDefaultWrapper();

            await wrapper.setData({
                modalRewardType: TYPE_REWARD,
            });

            expect(wrapper.vm.confirmTransactionMessage).toBe('reward.delete.confirm_transactions');
        });

        it('when isRewardType = false', async () => {
            const wrapper = mockDefaultWrapper();

            await wrapper.setData({
                modalRewardType: TYPE_BOUNTY,
            });

            expect(wrapper.vm.confirmTransactionMessage).toBe('bounty.delete.confirm_transactions');
        });
    });

    it('should compute isRemoveBountyMemberType correctly', async () => {
        const wrapper = mockDefaultWrapper();
        await wrapper.setData({volunteerModalType: 'reject'});
        expect(wrapper.vm.isRemoveBountyMemberType).toBe(true);
    });

    it('should compute isRefundBountyMemberType correctly', async function() {
        const wrapper = mockDefaultWrapper();
        await wrapper.setData({volunteerModalType: 'refund'});
        expect(wrapper.vm.isRefundBountyMemberType).toBe(true);
    });

    it('shows appropriate volunteer confirmation message', async () => {
        const wrapper = mockDefaultWrapper();

        await wrapper.setData({volunteerModalType: 'reject'});

        expect(wrapper.vm.volunteerConfirmationMessage).toBe('bounties_rewards.manage.member.remove.confirm');

        await wrapper.setData({volunteerModalType: 'refund'});
        expect(wrapper.vm.volunteerConfirmationMessage).toBe('reward.item.refund.confirm');
    });

    it('should should compute isBountyType correctly', async function() {
        const wrapper = mockDefaultWrapper();

        await wrapper.setData({modalRewardType: 'bounty'});
        expect(wrapper.vm.isBountyType).toBe(true);

        await wrapper.setData({modalRewardType: 'TEST'});
        expect(wrapper.vm.isBountyType).toBe(false);
    });

    it('should compute removeRewardModalMessage correctly', async function() {
        const wrapper = mockDefaultWrapper();

        await wrapper.setData({modalRewardType: 'reward'});
        expect(wrapper.vm.removeRewardModalMessage).toBe('bounties_rewards.manage.reward.item.remove.confirm');

        await wrapper.setData({modalRewardType: 'bounty'});
        expect(wrapper.vm.removeRewardModalMessage).toBe('bounties_rewards.manage.bounty.item.remove.confirm');
    });

    it('should execute openEditBountyModal correctly', () => {
        const wrapper = mockDefaultWrapper();

        wrapper.vm.openEditBountyModal(testBounties[0]);

        expect(wrapper.vm.currentBountyRewardItem).toBe(testBounties[0]);
        expect(wrapper.vm.addEditModalType).toBe('edit');
        expect(wrapper.vm.showAddEditModal).toBe(true);
        expect(wrapper.vm.modalRewardType).toBe('bounty');
    });

    it('should execute openEditRewardModal correctly', () => {
        const wrapper = mockDefaultWrapper();

        wrapper.vm.openEditRewardModal(testRewards[0]);

        expect(wrapper.vm.currentBountyRewardItem).toBe(testRewards[0]);
        expect(wrapper.vm.addEditModalType).toBe('edit');
        expect(wrapper.vm.showAddEditModal).toBe(true);
        expect(wrapper.vm.modalRewardType).toBe('reward');
    });

    it('should execute openEditModal correctly', () => {
        const wrapper = mockDefaultWrapper();

        wrapper.vm.openEditModal('reward', testRewards[0]);

        expect(wrapper.vm.currentBountyRewardItem).toBe(testRewards[0]);
        expect(wrapper.vm.addEditModalType).toBe('edit');
        expect(wrapper.vm.showAddEditModal).toBe(true);
        expect(wrapper.vm.modalRewardType).toBe('reward');
    });

    it('should execute openRemoveRewardModal correctly', () => {
        const wrapper = mockDefaultWrapper();

        wrapper.vm.openRemoveRewardModal(testRewards[0]);

        expect(wrapper.vm.currentBountyRewardItem).toBe(testRewards[0]);
        expect(wrapper.vm.showRemoveRewardModal).toBe(true);
        expect(wrapper.vm.modalRewardType).toBe('reward');
    });

    it('should execute openRemoveBountyModal correctly', () => {
        const wrapper = mockDefaultWrapper();

        wrapper.vm.openRemoveBountyModal(testBounties[0]);

        expect(wrapper.vm.currentBountyRewardItem).toBe(testBounties[0]);
        expect(wrapper.vm.showRemoveRewardModal).toBe(true);
        expect(wrapper.vm.modalRewardType).toBe('bounty');
    });

    it('should execute openRemoveModal correctly', () => {
        const wrapper = mockDefaultWrapper();

        wrapper.vm.openRemoveModal('reward', testRewards[0]);

        expect(wrapper.vm.currentBountyRewardItem).toBe(testRewards[0]);
        expect(wrapper.vm.showRemoveRewardModal).toBe(true);
        expect(wrapper.vm.modalRewardType).toBe('reward');
    });

    it('should execute confirmRemoveRewardItem correctly with reward', async (done) => {
        const removeRewardFunction = jest.fn();
        const removeBountyFunction = jest.fn();
        const currentBountyRewardItem = testRewards[0];
        const wrapper = mockDefaultWrapper({}, {
            mutations: {
                editBounty: () => {},
                editReward: () => {},
                removeReward: removeRewardFunction,
                removeBounty: removeBountyFunction,
                setRewards: () => {},
                setBounties: () => {},
            },
        });

        await wrapper.setData({
            currentBountyRewardItem,
            modalRewardType: 'reward',
            showRemoveRewardModal: true,
        });
        moxios.stubRequest('delete_reward', {status: HTTP_NO_CONTENT});

        wrapper.vm.confirmRemoveRewardItem();

        moxios.wait(async () => {
            expect(wrapper.vm.showRemoveRewardModal).toBe(false);
            expect(removeRewardFunction).toHaveBeenCalledWith({}, currentBountyRewardItem);
            expect(removeBountyFunction).not.toHaveBeenCalled();
            done();
        });
    });

    it('should execute confirmRemoveRewardItem correctly with reward', async (done) => {
        const removeRewardFunction = jest.fn();
        const removeBountyFunction = jest.fn();
        const currentBountyRewardItem = testBounties[0];
        const wrapper = mockDefaultWrapper({}, {
            mutations: {
                editBounty: () => {},
                editReward: () => {},
                removeReward: removeRewardFunction,
                removeBounty: removeBountyFunction,
                setRewards: () => {},
                setBounties: () => {},
            },
        });

        await wrapper.setData({
            currentBountyRewardItem,
            modalRewardType: 'bounty',
            showRemoveRewardModal: true,
        });

        moxios.stubRequest('delete_reward', {status: HTTP_NO_CONTENT});

        wrapper.vm.confirmRemoveRewardItem();

        moxios.wait(async () => {
            expect(wrapper.vm.showRemoveRewardModal).toBe(false);
            expect(removeRewardFunction).not.toHaveBeenCalled();
            expect(removeBountyFunction).toHaveBeenCalledWith({}, currentBountyRewardItem);
            done();
        });
    });

    it('should execute closeRemoveModal correctly', async function() {
        const wrapper = mockDefaultWrapper();

        await wrapper.setData({showRemoveRewardModal: true});

        wrapper.vm.closeRemoveModal();

        expect(wrapper.vm.showRemoveRewardModal).toBe(false);
    });

    it('should execute openSummaryModal correctly', function() {
        const wrapper = mockDefaultWrapper();

        wrapper.vm.openSummaryModal(testBounties[0]);

        expect(wrapper.vm.showSummaryModal).toBe(true);
        expect(wrapper.vm.currentBountyRewardItem).toBe(testBounties[0]);
    });

    it('should execute acceptMember correctly with bounty and quantityReached', (done) => {
        const currentBountyRewardItem = {quantityReached: 1, ...testBounties[0]};
        const wrapper = mockDefaultWrapper( {}, {
            mutations: {
                editBounty: () => {},
                editReward: () => {},
                setRewards: () => {},
                setBounties: () => {},
            },
        });

        moxios.stubRequest('accept_member', {
            status: 200,
            response: {
                reward: {
                    ...currentBountyRewardItem,
                },
            },
        });

        wrapper.vm.acceptMember({slug: 'qweqwe3', memberId: 'ID1'});

        moxios.wait(() => {
            expect(wrapper.vm.currentBountyRewardItem).toStrictEqual(currentBountyRewardItem);
            done();
        });
    });

    it('should execute acceptMember correctly with bounty and no quantityReached', (done) => {
        const currentBountyRewardItem = testBounties[0];
        const editBountyFunction = jest.fn();
        const editRewardFunction = jest.fn();
        const wrapper = mockDefaultWrapper({}, {
            mutations: {
                editBounty: editBountyFunction,
                editReward: editRewardFunction,
                removeReward: () => {},
                removeBounty: () => {},
                setRewards: () => {},
                setBounties: () => {},
            },
        });

        moxios.stubRequest('accept_member', {
            status: 200,
            response: {
                reward: {
                    ...currentBountyRewardItem,
                },
            },
        });

        wrapper.vm.acceptMember({slug: 'qweqwe3', memberId: 'ID1'});

        moxios.wait(() => {
            expect(wrapper.vm.currentBountyRewardItem).toStrictEqual(currentBountyRewardItem);
            expect(editRewardFunction).not.toHaveBeenCalled();
            expect(editBountyFunction).toHaveBeenCalledWith({}, currentBountyRewardItem);
            done();
        });
    });

    it('should execute acceptMember correctly with reward and quantityReached', (done) => {
        const currentBountyRewardItem = {quantityReached: 1, ...testRewards[0]};
        const wrapper = mockDefaultWrapper({}, {
            mutations: {
                editBounty: () => {},
                editReward: () => {},
                setRewards: () => {},
                setBounties: () => {},
            },
        });

        moxios.stubRequest('accept_member', {
            status: 200,
            response: {
                reward: {
                    ...currentBountyRewardItem,
                },
            },
        });

        wrapper.vm.acceptMember({slug: 'qweqwe3', memberId: 'ID1'});

        moxios.wait(() => {
            expect(wrapper.vm.currentBountyRewardItem).toStrictEqual(currentBountyRewardItem);
            done();
        });
    });

    it('should execute acceptMember correctly with reward and no quantityReached', (done) => {
        const currentBountyRewardItem = testRewards[0];
        const editRewardFunction = jest.fn();
        const editBountyFunction = jest.fn();
        const wrapper = mockDefaultWrapper({}, {
            mutations: {
                editBounty: editBountyFunction,
                editReward: editRewardFunction,
                removeReward: () => {},
                removeBounty: () => {},
                setRewards: () => {},
                setBounties: () => {},
            },
        });

        moxios.stubRequest('accept_member', {
            status: 200,
            response: {
                reward: {
                    ...currentBountyRewardItem,
                },
            },
        });

        wrapper.vm.acceptMember({slug: 'qweqwe3', memberId: 'ID1'});

        moxios.wait(() => {
            expect(wrapper.vm.currentBountyRewardItem).toStrictEqual(currentBountyRewardItem);
            expect(editRewardFunction).toHaveBeenCalledWith({}, currentBountyRewardItem);
            expect(editBountyFunction).not.toHaveBeenCalled();
            done();
        });
    });

    it('should execute acceptVolunteer with isRequesting true correctly', function() {
        const wrapper = mockDefaultWrapper();
        const setFunction = jest.spyOn(wrapper.vm, '$set');
        const acceptMemberSpy = jest.spyOn(wrapper.vm, 'acceptMember');

        wrapper.vm.acceptVolunteer({...volunteers[0], isRequesting: true});

        expect(acceptMemberSpy).not.toHaveBeenCalled();
        expect(setFunction).not.toHaveBeenCalled();
    });

    it('should execute acceptVolunteer with isRequesting false correctly', function() {
        const wrapper = mockDefaultWrapper();
        const setFunction = jest.spyOn(wrapper.vm, '$set');
        const acceptMemberSpy = jest.spyOn(wrapper.vm, 'acceptMember');

        wrapper.vm.acceptVolunteer(volunteers[0]);

        expect(acceptMemberSpy).toHaveBeenCalledWith({
            memberId: volunteers[0].id,
            slug: volunteers[0].reward.slug,
        });
        expect(setFunction).toHaveBeenCalledWith(volunteers[0], 'isRequesting', true);
    });

    it('should execute openAddRewardModal correctly', function() {
        const wrapper = mockDefaultWrapper();

        wrapper.vm.openAddRewardModal();

        expect(wrapper.vm.addEditModalType).toBe('add');
        expect(wrapper.vm.modalRewardType).toBe('reward');
        expect(wrapper.vm.showAddEditModal).toBe(true);
    });

    it('should execute openAddBountyModal correctly', function() {
        const wrapper = mockDefaultWrapper();

        wrapper.vm.openAddBountyModal();

        expect(wrapper.vm.addEditModalType).toBe('add');
        expect(wrapper.vm.modalRewardType).toBe('bounty');
        expect(wrapper.vm.showAddEditModal).toBe(true);
    });

    it('should execute openAddModal correctly', function() {
        const wrapper = mockDefaultWrapper();

        wrapper.vm.openAddModal('reward');

        expect(wrapper.vm.addEditModalType).toBe('add');
        expect(wrapper.vm.modalRewardType).toBe('reward');
        expect(wrapper.vm.showAddEditModal).toBe(true);
    });

    it('should execute closeAddEditModal correctly', function() {
        const wrapper = mockDefaultWrapper();

        wrapper.vm.closeAddEditModal();

        expect(wrapper.vm.showAddEditModal).toBe(false);
    });

    it('should proceed correctly with isRemoveBountyMemberType true', async function(done) {
        const wrapper = mockDefaultWrapper();

        await wrapper.setData({
            volunteerModalType: 'reject',
            currentBountyRewardItem: {slug: '', participants: [], id: 1},
            currentVolunteer: {reward: {slug: '', participants: []}, id: 1},
        });
        const reward = {
            slug: '',
            participants: [],
            title: 'TEST',
        };

        moxios.stubRequest('delete_bounty_member', {
            status: 200,
            response: {
                ...reward,
            },
        });
        wrapper.vm.proceedVolunteerAction();

        moxios.wait(() => {
            expect(wrapper.vm.currentBountyRewardItem).toStrictEqual(reward);
            done();
        });
    });

    it('should proceed correctly with isRefundBountyMemberType true', async function(done) {
        const wrapper = mockDefaultWrapper();

        await wrapper.setData({
            volunteerModalType: 'refund',
            currentBountyRewardItem: {slug: '', participants: [], id: 1},
            currentVolunteer: {reward: {slug: '', participants: []}, id: 1},
        });

        const reward = {
            slug: '',
            participants: [],
            title: 'TEST',
        };

        moxios.stubRequest('refund_reward', {
            status: 200,
            response: {
                ...reward,
            },
        });
        wrapper.vm.proceedVolunteerAction();

        moxios.wait(() => {
            expect(wrapper.vm.currentBountyRewardItem).toStrictEqual(reward);
            done();
        });
    });

    it('should execute rejectMember correctly', function() {
        const wrapper = mockDefaultWrapper();
        const member = {slug: '', participants: [], id: 1};

        wrapper.vm.rejectMember(member);

        expect(wrapper.vm.volunteerModalType).toBe('reject');
        expect(wrapper.vm.currentVolunteer).toStrictEqual(member);
        expect(wrapper.vm.showVolunteerModal).toBe(true);
    });

    it('should execute openVolunteerModalAction correctly', function() {
        const wrapper = mockDefaultWrapper();
        const member = {slug: '', participants: [], id: 1};

        wrapper.vm.openVolunteerModalAction(member, 'refund');

        expect(wrapper.vm.volunteerModalType).toBe('refund');
        expect(wrapper.vm.currentVolunteer).toStrictEqual(member);
        expect(wrapper.vm.showVolunteerModal).toBe(true);
    });

    it('verify closeConfirmTransactionsModal works correctly', () => {
        const wrapper = mockDefaultWrapper();

        wrapper.vm.closeConfirmTransactionsModal();

        expect(wrapper.vm.showConfirmTransactionsModal).toBe(false);
        expect(wrapper.vm.showSummaryModal).toBe(true);
    });
});
