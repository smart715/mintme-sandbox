import {shallowMount, createLocalVue} from '@vue/test-utils';
import Vuex from 'vuex';
import BountiesAndRewards from '../../js/components/bountiesAndRewards/BountiesAndRewards';
import rewardsAndBounties from '../../js/storage/modules/rewards_and_bounties';
import {NotificationMixin} from '../../js/mixins';
import pairModule from '../../js/storage/modules/pair';
import moxios from 'moxios';
import axios from 'axios';
import {
    TYPE_REWARD,
    HTTP_NO_CONTENT,
} from '../../js/utils/constants';

const localVue = mockVue();

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.use(Vuex);
    localVue.mixin(NotificationMixin);
    localVue.use({
        install(Vue, options) {
            Vue.prototype.$axios = {retry: axios, single: axios};
            Vue.prototype.$routing = {generate: (val) => val};
            Vue.prototype.$t = (val) => val;
            Vue.prototype.$logger = {error: (val, params) => val, success: (val, params) => val};
            Vue.prototype.$toasted = {show: (val) => val};
        },
    });
    return localVue;
}

/**
 * @param {Object} props
 * @param {Object} store
 * @return {Wrapper<Vue>}
 */
function mockBountiesAndRewards(props = {}, store = {}) {
    return shallowMount(BountiesAndRewards, {
        localVue,
        store: createSharedTestStore(store),
        propsData: createSharedTestProps(props),
        directives: {
            'b-tooltip': {},
        },
    });
}

/**
 * @param {Object} props
 * @return {Object}
 */
function createSharedTestProps(props = {}) {
    return {
        tokenName: 'jasmToken',
        tokenAvatar: 'jasmTokenAvatar',
        isOwner: false,
        isMobileScreen: false,
        showFinalized: false,
        showSummary: false,
        rewards: [],
        bounties: [],
        rewardsMaxLimit: 22,
        bountiesMaxLimit: 22,
        isCreatedOnMintmeSite: false,
        disabledServicesConfig: '',
        disabledCryptos: [],
        isUserBlocked: false,
        reward: null,
        ...props,
    };
}

const reward1 =
    {
        createdAt: 1622482609,
        description: null,
        quantityReached: false,
        frozenAmount: '99900000.000000000000',
        link: null,
        participants: [],
        price: '100000.000000000000',
        quantity: 999,
        slug: 'qweqwe-3',
        title: 'qweqwe',
        token: {
            cryptoSymbol: 'WEB',
            decimals: 12,
            deploymentStatus: 'not-deployed',
            identifier: 'TOK000000000001',
            image: {avatar_small: 'https://localhost/media/cache/resolve/avatar_smalls/images/foo.png'},
            name: 'qwetoken',
            subunit: 4,
            symbol: 'qwetoken',
        },
        type: 'reward',
        volunteers: [],
    };

const testRewards = [
    {
        createdAt: 1622482609,
        description: null,
        quantityReached: false,
        frozenAmount: '99900000.000000000000',
        link: null,
        participants: [],
        price: '100000.000000000000',
        quantity: 999,
        slug: 'qweqwe-3',
        title: 'qweqwe',
        token: {
            cryptoSymbol: 'WEB',
            decimals: 12,
            deploymentStatus: 'not-deployed',
            identifier: 'TOK000000000001',
            image: {avatar_small: 'https://localhost/media/cache/resolve/avatar_smalls/images/foo.png'},
            name: 'qwetoken',
            subunit: 4,
            symbol: 'qwetoken',
        },
        type: 'reward',
        volunteers: [],
    },
    {
        createdAt: 1122482609,
        description: null,
        quantityReached: false,
        frozenAmount: '99900000.000000000000',
        link: null,
        participants: [],
        price: '100000.0000',
        quantity: 999,
        slug: 'test',
        title: 'test',
        token: {
            cryptoSymbol: 'WEB',
            decimals: 12,
            deploymentStatus: 'not-deployed',
            identifier: 'TOK000000000001',
            image: {avatar_small: 'https://localhost/media/cache/resolve/avatar_smalls/images/foo.png'},
            name: 'qwetoken',
            subunit: 4,
            symbol: 'qwetoken',
        },
        type: 'reward',
        volunteers: [],
    },
];

const testBounties = [
    {
        createdAt: 1622482609,
        description: null,
        quantityReached: false,
        frozenAmount: '99900000.000000000000',
        link: null,
        participants: [],
        price: '100000.000000000000',
        quantity: 999,
        slug: 'qweqwe-3',
        title: 'qweqwe',
        token: {
            cryptoSymbol: 'WEB',
            decimals: 12,
            deploymentStatus: 'not-deployed',
            identifier: 'TOK000000000001',
            image: {
                avatar_small: 'https://localhost/media/cache/resolve/avatar_smalls/images/foo.png',
            },
            name: 'qwetoken',
            subunit: 4,
            symbol: 'qwetoken',
        },
        type: 'bounty',
        volunteers: [],
    },
    {
        createdAt: 1122482609,
        description: null,
        quantityReached: false,
        frozenAmount: '99900000.000000000000',
        link: null,
        participants: [],
        price: '100000.0000',
        quantity: 999,
        slug: 'test',
        title: 'test',
        token: {
            cryptoSymbol: 'WEB',
            decimals: 12,
            deploymentStatus: 'not-deployed',
            identifier: 'TOK000000000001',
            image: {
                avatar_small: 'https://localhost/media/cache/resolve/avatar_smalls/images/foo.png',
            },
            name: 'qwetoken',
            subunit: 4,
            symbol: 'qwetoken',
        },
        type: 'bounty',
        volunteers: [],
    },
];

/**
 * @param {Object} store
 * @return {Vuex.Store}
 */
function createSharedTestStore(store = {}) {
    return new Vuex.Store({
        modules: {
            rewardsAndBounties,
            pair: pairModule,
            websocket: {
                namespaced: true,
                actions: {
                    addMessageHandler: () => {},
                },
            },
            tradeBalance: {
                namespaced: true,
                getters: {
                    getBalances: () => {},
                    isServiceUnavailable: () => false,
                },
            },
            ...store,
        },
    });
}

describe('BountiesAndRewards', () => {
    beforeEach(() => {
        moxios.install();
    });

    afterEach(() => {
        moxios.uninstall();
    });

    describe('"showRewards" and "showBounties"', () => {
        it('not owner, empty rewards and bounties, showRewards should be false', () => {
            const wrapper = mockBountiesAndRewards();

            expect(wrapper.vm.showRewards).toBe(false);
            expect(wrapper.vm.showBounties).toBe(false);
        });

        it('owner, empty rewards and bounties, showRewards should be true', () => {
            const wrapper = mockBountiesAndRewards({
                isOwner: true,
            });

            expect(wrapper.vm.showRewards).toBe(true);
            expect(wrapper.vm.showBounties).toBe(true);
        });
    });

    it('Verify that "openRemoveModal" works correctly', async () => {
        const wrapper = mockBountiesAndRewards();
        const currentBountyRewardItemTest = {
            title: 'jasm',
            volunteers: ['jasm'],
            participants: ['jasm'],
        };

        await wrapper.setData({
            modalRewardType: TYPE_REWARD,
            currentBountyRewardItem: {
                title: '',
                volunteers: [],
                participants: [],
            },
            showRemoveRewardModal: false,
        });

        wrapper.vm.openRemoveModal(TYPE_REWARD, currentBountyRewardItemTest);

        expect(wrapper.vm.modalRewardType).toBe(TYPE_REWARD);
        expect(wrapper.vm.currentBountyRewardItem).toBe(currentBountyRewardItemTest);
        expect(wrapper.vm.showRemoveRewardModal).toBe(true);
    });

    it('Verify that "openFinalizeModal" works correctly', async () => {
        const wrapper = mockBountiesAndRewards();
        const orderTest = {
            order: {
                id: 22,
                name: 'jasm',
            },
        };

        await wrapper.setData({
            finalizedOrder: {},
            showFinalizeModal: false,
        });

        wrapper.vm.openFinalizeModal(orderTest);

        expect(wrapper.vm.finalizedOrder).toBe(orderTest);
        expect(wrapper.vm.showFinalizeModal).toBe(true);
    });

    it('Verify that "openVolunteerModalAction" works correctly', async () => {
        const wrapper = mockBountiesAndRewards();
        const memberTest = 'jasm-member';
        const volunteerType = 'reject';

        await wrapper.setData({
            currentVolunteer: null,
            volunteerModalType: null,
            showVolunteerModal: false,
        });

        wrapper.vm.openVolunteerModalAction(memberTest, volunteerType);

        expect(wrapper.vm.volunteerModalType).toBe(volunteerType);
        expect(wrapper.vm.currentVolunteer).toBe(memberTest);
        expect(wrapper.vm.showVolunteerModal).toBe(true);
    });

    it('Verify that "closeSummaryModal" works correctly', async () => {
        const wrapper = mockBountiesAndRewards();

        await wrapper.setData({
            showSummaryModal: true,
        });

        wrapper.vm.closeSummaryModal();

        expect(wrapper.vm.showSummaryModal).toBe(false);
    });

    it('Verify that "closeFinalizeModal" works correctly', async () => {
        const wrapper = mockBountiesAndRewards();

        await wrapper.setData({
            showFinalizeModal: true,
        });

        wrapper.vm.closeFinalizeModal();

        expect(wrapper.vm.showFinalizeModal).toBe(false);
    });

    it('not owner, empty rewards and bounties, showRewards should be false', () => {
        const wrapper = mockBountiesAndRewards({
            isOwner: false,
            rewards: testRewards,
        });

        expect(wrapper.vm.showRewards).toBe(true);
        expect(wrapper.vm.showBounties).toBe(false);
        expect(wrapper.vm.showFinalizeModal).toBe(false);
        expect(wrapper.vm.showSummaryModal).toBe(false);
    });

    it('Verify that "showFinalizeModal" and "showSummaryModal" values are set correctly', () => {
        const wrapper = mockBountiesAndRewards({
            isOwner: true,
            showFinalized: true,
            showSummary: true,
            reward: reward1,
            rewards: testRewards,
            bounties: [],
        });

        expect(wrapper.vm.showFinalizeModal).toBe(true);
        expect(wrapper.vm.showSummaryModal).toBe(true);
    });

    it('Verify that "closeAddModal" works correctly', async () => {
        const wrapper = mockBountiesAndRewards();

        expect(wrapper.vm.showAddEditModal).toBe(false);

        await wrapper.setData({
            showAddEditModal: true,
        });

        expect(wrapper.vm.showAddEditModal).toBe(true);
    });

    it('confirm Remove', async (done) => {
        const wrapper = mockBountiesAndRewards();

        await wrapper.setProps({
            isOwner: true,
            showSummary: true,
            reward: reward1,
            rewards: testRewards,
            bounties: testBounties,
        });

        await wrapper.setData({
            modalRewardType: TYPE_REWARD,
            showRemoveRewardModal: true,
            currentBountyRewardItem: {
                title: 'qweqwe',
                volunteers: [],
                participants: [],
            },
        });

        moxios.stubRequest('delete_reward', {status: HTTP_NO_CONTENT});

        wrapper.vm.confirmRemoveRewardItem();

        moxios.wait(() => {
            expect(wrapper.vm.showRemoveRewardModal).toBe(false);
            done();
        });
    });

    it('acceptMember', async (done) => {
        const wrapper = mockBountiesAndRewards();

        await wrapper.setProps({
            isOwner: true,
            showSummary: true,
            reward: reward1,
            rewards: testRewards,
            bounties: testBounties,
        });

        await wrapper.setData({
            currentBountyRewardItem: {
                title: 'qweqwe',
                volunteers: [],
                participants: [],
            },
        });

        wrapper.vm.acceptMember('qweqwe3', 'ID1');

        moxios.stubRequest('accept_member', {
            status: 200,
            response: {
                ...reward1,
            },
        });

        moxios.wait(() => {
            expect(wrapper.vm.currentBountyRewardItem.title).toBe('qweqwe');
            done();
        });
    });

    it('acceptMember denied', async (done) => {
        const wrapper = mockBountiesAndRewards();
        wrapper.vm.notifyError = jest.fn();

        await wrapper.setProps({
            isOwner: true,
            showSummary: true,
            reward: reward1,
            rewards: testRewards,
            bounties: testBounties,
        });

        await wrapper.setData({
            currentBountyRewardItem: {
                title: '',
                volunteers: [],
                participants: [],
            },
        });

        moxios.stubRequest('accept_member', {
            status: 403,
            response: {
                message: 'error-message',
            },
        });

        wrapper.vm.acceptMember('qweqwe3', 'ID1');

        moxios.wait(() => {
            expect(wrapper.vm.notifyError).toHaveBeenCalledWith('error-message');
            done();
        });
    });

    describe('Check the "setIsRewardsInitializedStub" call', () => {
        it('initializes data on first mount', () => {
            const setIsRewardsInitializedStub = jest.fn();
            const localVue = mockVue();
            shallowMount(BountiesAndRewards, {
                store: new Vuex.Store({
                    modules: {
                        rewardsAndBounties,
                        pair: {
                            namespaced: true,
                            state: {
                                isRewardsInitialized: false,
                            },
                            getters: {
                                getIsRewardsInitialized(state) {
                                    return state.isRewardsInitialized;
                                },
                            },
                            mutations: {
                                setIsRewardsInitialized: setIsRewardsInitializedStub,
                            },
                        },
                        websocket: {
                            namespaced: true,
                            actions: {
                                addMessageHandler: () => {},
                            },
                        },
                        tradeBalance: {
                            namespaced: true,
                            getters: {
                                getBalances: () => {},
                                isServiceUnavailable: () => false,
                            },
                        },
                    },
                }),
                localVue,
                propsData: {
                    isOwner: false,
                    rewards: [],
                    bounties: [],
                },
            });

            expect(setIsRewardsInitializedStub).toHaveBeenCalled();
        });

        it('doesnt initialize data on second mount', () => {
            const setIsRewardsInitializedStub = jest.fn();
            const localVue = mockVue();
            shallowMount(BountiesAndRewards, {
                store: new Vuex.Store({
                    modules: {
                        rewardsAndBounties,
                        pair: {
                            namespaced: true,
                            state: {
                                isRewardsInitialized: true,
                            },
                            getters: {
                                getIsRewardsInitialized(state) {
                                    return state.isRewardsInitialized;
                                },
                            },
                            mutations: {
                                setIsRewardsInitialized: setIsRewardsInitializedStub,
                            },
                        },
                        websocket: {
                            namespaced: true,
                            actions: {
                                addMessageHandler: () => {},
                            },
                        },
                        tradeBalance: {
                            namespaced: true,
                            getters: {
                                getBalances: () => {},
                                isServiceUnavailable: () => false,
                            },
                        },
                    },
                }),
                localVue,
                propsData: {
                    isOwner: false,
                    rewards: [],
                    bounties: [],
                },
            });

            expect(setIsRewardsInitializedStub).not.toHaveBeenCalled();
        });
    });

    describe('removeOpenModalFlagFromUrl', () => {
        Object.defineProperty(window, 'location', {
            value: {
                href: 'foo.com/bar/reward-finalize/',
            },
        });
        Object.defineProperty(window, 'history', {
            value: {
                replaceState: (data, unused, href) => window.location.href = href,
            },
        });

        const wrapper = mockBountiesAndRewards({
            reward: testRewards[0],
            rewards: testRewards,
            bounties: testBounties,
            showFinalized: true,
        });

        it('should remove reward-finalize from url when url contains reward-finalize', () => {
            window.location.href = 'foo.com/bar/reward-finalize/';

            wrapper.vm.removeOpenModalFlagFromUrl('reward-finalize');
            expect(window.location.href).toEqual('foo.com/bar');
        });

        it('should remain the same when url has no reward-finalize in it', () => {
            window.location.href = 'foo.com/bar';
            expect(window.location.href).toEqual('foo.com/bar');

            wrapper.vm.removeOpenModalFlagFromUrl('reward-finalize');
            expect(window.location.href).toEqual('foo.com/bar');
        });

        it('should remove reward-summary from url when url contains reward-summary', () => {
            window.location.href = 'foo.com/bar/reward-summary/';
            expect(window.location.href).toEqual('foo.com/bar/reward-summary/');

            wrapper.vm.removeOpenModalFlagFromUrl('reward-summary');
            expect(window.location.href).toEqual('foo.com/bar');
        });

        it('should remain the same when url has no reward-summary in it', () => {
            window.location.href = 'foo.com/bar';
            expect(window.location.href).toEqual('foo.com/bar');

            wrapper.vm.removeOpenModalFlagFromUrl('reward-summary');
            expect(window.location.href).toEqual('foo.com/bar');
        });
    });

    describe('proceedVolunteerAction', () => {
        const wrapper = mockBountiesAndRewards({
            isOwner: true,
            showSummary: true,
            reward: reward1,
            rewards: testRewards,
            bounties: testBounties,
        });

        wrapper.vm.sendNotification = jest.fn();
        wrapper.vm.toMoney = jest.fn();
        wrapper.vm.editBounty = jest.fn();
        wrapper.vm.editReward = jest.fn();

        it('should proceed correctly with isRemoveBountyMemberType true', async (done) => {
            await wrapper.setData({
                volunteerModalType: 'reject',
                currentVolunteer: {reward: {slug: '', participants: []}, id: 1},
            });

            moxios.stubRequest('delete_bounty_member', {
                status: 200,
                response: {
                    ...reward1,
                },
            });

            wrapper.vm.proceedVolunteerAction();

            moxios.wait(() => {
                expect(wrapper.vm.currentBountyRewardItem).toStrictEqual(reward1);
                done();
            });
        });

        it('should proceed correctly with isRefundBountyMemberType true', async (done) => {
            await wrapper.setData({
                volunteerModalType: 'refund',
                currentVolunteer: {reward: {slug: '', participants: []}, id: 1},
            });

            const reward2 = {
                slug: 'qweqwe-3',
                participants: [],
                title: 'TEST',
            };

            moxios.stubRequest('refund_reward', {
                status: 200,
                response: {
                    ...reward2,
                },
            });

            wrapper.vm.proceedVolunteerAction();

            moxios.wait(() => {
                expect(wrapper.vm.currentBountyRewardItem).toStrictEqual(reward2);
                done();
            });
        });
    });
});
