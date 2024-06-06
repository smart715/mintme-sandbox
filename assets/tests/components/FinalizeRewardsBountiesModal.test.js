import {shallowMount, createLocalVue} from '@vue/test-utils';
import Vuelidate from 'vuelidate';
import FinalizeRewardsBountiesModal from '../../js/components/bountiesAndRewards/modal/FinalizeRewardsBountiesModal';
import Vuex from 'vuex';
import moxios from 'moxios';
import axios from 'axios';
import {generateCoinAvatarHtml} from '../../js/utils';
import {
    TYPE_REWARD,
    TYPE_BOUNTY,
    REWARD_COMPLETED,
    REWARD_PENDING,
    tokenDeploymentStatus,
    TOKEN_DEFAULT_ICON_URL,
} from '../../js/utils/constants';

const localVue = mockVue();

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.use(Vuelidate);
    localVue.use(Vuex);
    localVue.use({
        install(Vue, options) {
            Vue.prototype.$routing = {generate: (val) => val};
            Vue.prototype.$axios = {retry: axios, single: axios};
            Vue.prototype.$t = (val) => val;
            Vue.prototype.$axios = {
                retry: axios,
                single: axios,
            };
            Vue.prototype.$logger = {error: (val) => {}};
            Vue.prototype.$toasted = {show: () => {}};
        },
    });

    return localVue;
}

/**
 * @param {Object} props
 * @return {Object}
 */
function createSharedTestProps(props = {}) {
    return {
        visible: true,
        tokenName: 'tokenName',
        reward: {
            slug: 'slug',
            type: TYPE_REWARD,
            quantity: 0,
            participants: [],
            price: 100,
            title: 'titleRewardTest',
        },
        actionsLoaded: true,
        serviceUnavailable: false,
        ...props,
    };
}

/**
 * @param {Object} mutations
 * @return {Vuex.Store}
 */
function createSharedTestStore(mutations) {
    return new Vuex.Store({
        modules: {
            user: {
                namespaced: true,
                getters: {
                    getId: () => '2',
                },
            },
            rewardsAndBounties: {
                mutations,
                namespaced: true,
            },
            tokenInfo: {
                namespaced: true,
                getters: {
                    getDeploymentStatus: () => tokenDeploymentStatus.notDeployed,
                },
            },
        },
    });
}

const translationContextTest = {
    leftAmount: 0,
    maxLength: 255,
    price: 100,
    title: 'titleRewardTest',
    tokenName: 'tokenName',
    tokenAvatar: generateCoinAvatarHtml({image: TOKEN_DEFAULT_ICON_URL, isUserToken: true}),
};

describe('FinalizeRewardsBountiesModal', () => {
    let wrapper;
    let mutations;
    let store;

    beforeEach(() => {
        mutations = {
            editBounty: jest.fn(),
            editReward: jest.fn(),
            removeReward: jest.fn(),
            removeBounty: jest.fn(),
        };

        store = createSharedTestStore(mutations);

        moxios.install();

        wrapper = shallowMount(FinalizeRewardsBountiesModal, {
            localVue: localVue,
            sync: false,
            store: store,
            propsData: createSharedTestProps(),
            directives: {
                'b-tooltip': {},
            },
        });
    });

    afterEach(() => {
        moxios.uninstall();
    });

    Object.defineProperty(window, 'location', {
        value: {
            href: '',
        },
        configurable: true,
    });

    it('Verify that "onTextareaFocus" works correctly', () => {
        wrapper.vm.onTextareaFocus();
        expect(window.location.href).toBe('');
    });

    it('Verify that "goToLogin" works correctly', () => {
        wrapper.vm.goToLogin();

        expect(window.location.href).toBe('login');
    });

    it('Verify that "payBtnDisabled" return the correct value', async () => {
        await wrapper.setData({
            payClickDisabled: false,
        });

        expect(wrapper.vm.payBtnDisabled).toBeFalsy();

        await wrapper.setData({
            payClickDisabled: true,
        });

        expect(wrapper.vm.payBtnDisabled).toBeTruthy();
    });

    it('Verify that "translationContext" return the correct value', async () => {
        expect(wrapper.vm.translationContext).toEqual(translationContextTest);

        await wrapper.setProps({
            visible: false,
        });

        expect(wrapper.vm.translationContext).toEqual({});
    });

    it('Verify that "isBountyType" return the correct value', async () => {
        expect(wrapper.vm.isBountyType).toBeFalsy();

        await wrapper.setProps({
            reward: {
                type: TYPE_BOUNTY,
                quantity: 0,
                participants: [],
                price: 100,
                title: 'titleRewardTest',
                token: {
                    name: 'tokenName',
                },
            },
        });

        expect(wrapper.vm.isBountyType).toBeTruthy();
    });

    it('Verify that "closeModal" works correctly', () => {
        wrapper.vm.closeModal();
        expect(wrapper.emitted('close-modal')).toBeTruthy();
    });

    it('Verify that "proceedAddButton" works correctly', async () => {
        await wrapper.setData({
            rewardConfirmationVisible: false,
        });

        wrapper.vm.proceedAddButton();

        expect(wrapper.vm.rewardConfirmationVisible).toBeTruthy();
    });

    describe('Check the different status of the participants', () => {
        beforeEach(async () => {
            await wrapper.setData({
                payClickDisabled: false,
                note: '',
            });
        });
        describe('Reward', () => {
            it('Verify when status is PENDING', async (done) => {
                moxios.stubRequest('reward_add_member', {
                    status: 200,
                    response: {
                        quantityReached: true,
                        type: TYPE_REWARD,
                        participants: [
                            {status: REWARD_PENDING},
                        ],
                    },
                });

                await wrapper.vm.addMember();

                moxios.wait(() => {
                    expect(mutations.editReward.mock.calls).toHaveLength(1);
                    expect(wrapper.vm.payClickDisabled).toBeFalsy();
                    expect(wrapper.emitted('close-modal')).toBeTruthy();
                    done();
                });
            });

            it('Verify when status is COMPLETED', async (done) => {
                moxios.stubRequest('reward_add_member', {
                    status: 200,
                    response: {
                        quantityReached: true,
                        type: TYPE_REWARD,
                        participants: [
                            {status: REWARD_COMPLETED},
                        ],
                    },
                });

                await wrapper.vm.addMember();

                moxios.wait(() => {
                    expect(mutations.removeReward.mock.calls).toHaveLength(1);
                    expect(wrapper.vm.payClickDisabled).toBeFalsy();
                    expect(wrapper.emitted('close-modal')).toBeTruthy();
                    done();
                });
            });
        });

        describe('Bountie', () => {
            it('Verify when status is PENDING', async (done) => {
                moxios.stubRequest('reward_add_member', {
                    status: 200,
                    response: {
                        quantityReached: true,
                        type: TYPE_BOUNTY,
                        participants: [
                            {status: REWARD_PENDING},
                        ],
                    },
                });

                await wrapper.vm.addMember();

                moxios.wait(() => {
                    expect(mutations.editBounty.mock.calls).toHaveLength(1);
                    expect(wrapper.vm.payClickDisabled).toBeFalsy();
                    expect(wrapper.emitted('close-modal')).toBeTruthy();
                    done();
                });
            });

            it('Verify when status is COMPLETED', async (done) => {
                moxios.stubRequest('reward_add_member', {
                    status: 200,
                    response: {
                        quantityReached: true,
                        type: TYPE_BOUNTY,
                        participants: [
                            {status: REWARD_COMPLETED},
                        ],
                    },
                });

                await wrapper.vm.addMember();

                moxios.wait(() => {
                    expect(mutations.removeBounty.mock.calls).toHaveLength(1);
                    expect(wrapper.vm.payClickDisabled).toBeFalsy();
                    expect(wrapper.emitted('close-modal')).toBeTruthy();
                    done();
                });
            });
        });
    });
});
