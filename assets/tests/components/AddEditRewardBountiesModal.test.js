import {shallowMount, createLocalVue} from '@vue/test-utils';
import AddEditRewardBountiesModal from '../../js/components/bountiesAndRewards/modal/AddEditRewardBountiesModal';
import {
    EDIT_TYPE_REWARDS_MODAL,
    TYPE_REWARD,
    TYPE_BOUNTY,
    ADD_TYPE_REWARDS_MODAL,
} from '../../js/utils/constants';
import tradeBalance from '../../js/storage/modules/trade_balance';
import Vuex from 'vuex';
import moxios from 'moxios';
import axios from 'axios';

const localVue = mockVue();

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.use(Vuex);
    localVue.use({
        install(Vue) {
            Vue.prototype.$routing = {generate: (val) => val};
            Vue.prototype.$axios = {retry: axios, single: axios};
            Vue.prototype.$toasted = {show: () => {}};
            Vue.prototype.$t = (val) => val;
            Vue.prototype.$logger = {error: (val) => {}};
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
        tokenName: 'tokenNameTest',
        modalType: EDIT_TYPE_REWARDS_MODAL,
        type: TYPE_BOUNTY,
        editItem: {
            slug: 'slugTest',
            participants: [
                {id: 1},
                {id: 2},
            ],
        },
        ...props,
    };
}

/**
 * @param {Object} mutations
 * @param {Object} state
 * @return {Vuex.Store}
 */
function createSharedTestStore(mutations, state) {
    return new Vuex.Store({
        modules: {
            rewardsAndBounties: {
                mutations,
                state,
                namespaced: true,
            },
            tradeBalance: {
                ...tradeBalance,
                state: {
                    ...tradeBalance,
                    balances: {},
                },
            },
        },
    });
}

const propsTest = {
    title: 'TitleTest',
    price: '100',
    description: 'DescriptionTest',
    quantity: '2',
};

const translationContextTest = {
    maxDescription: 255,
    maxPrice: '100 000',
    maxQuantity: 999,
    maxTitle: 100,
    minPrice: '0.0001',
    minQuantity: 1,
    minTitle: 3,
    participantsAmount: 2,
    price_type: 'rewards.bountie.reward',
};

describe('AddEditRewardBountiesModal', () => {
    let wrapper;
    let mutations;
    let store;
    let state;

    beforeEach(() => {
        mutations = {
            addReward: jest.fn(),
            addBounty: jest.fn(),
            editBounty: jest.fn(),
            editReward: jest.fn(),
        };

        state = {
            addReward: {},
            addBounty: {},
            editBounty: {},
            editReward: {},
        };

        store = createSharedTestStore(mutations, state);

        wrapper = shallowMount(AddEditRewardBountiesModal, {
            localVue: localVue,
            sync: false,
            store: store,
            mocks: {
                $v: {
                    $invalid: false,
                    $touch: () => {},
                },
            },
            propsData: createSharedTestProps(),
        });

        moxios.install();
    });

    afterEach(() => {
        moxios.uninstall();
    });

    it('should disable price on bounty edit', async () => {
        expect(wrapper.vm.priceDisabled).toBe(true);

        await wrapper.setProps({
            modalType: ADD_TYPE_REWARDS_MODAL,
        });

        expect(wrapper.vm.priceDisabled).toBe(false);

        await wrapper.setProps({
            modalType: EDIT_TYPE_REWARDS_MODAL, type: TYPE_REWARD,
        });

        expect(wrapper.vm.priceDisabled).toBe(false);
    });

    it('Verify that "saveReward" works correctly', async (done) => {
        await wrapper.setData({
            saveClickDisabled: false,
        });

        wrapper.vm.saveReward();

        moxios.wait(() => {
            expect(wrapper.vm.saveClickDisabled).toBeTruthy();
            done();
        });
    });

    it('Verify that "isEditModalType" return the correct value', async () => {
        await wrapper.setProps({
            modalType: EDIT_TYPE_REWARDS_MODAL,
        });

        expect(wrapper.vm.isEditModalType).toBeTruthy();

        await wrapper.setProps({
            modalType: ADD_TYPE_REWARDS_MODAL,
        });

        expect(wrapper.vm.isEditModalType).toBeFalsy();
    });

    it('Verify that "isRewardType" return the correct value', async () => {
        await wrapper.setProps({
            type: TYPE_REWARD,
        });

        expect(wrapper.vm.isRewardType).toBeTruthy();

        await wrapper.setProps({
            type: TYPE_BOUNTY,
        });

        expect(wrapper.vm.isRewardType).toBeFalsy();
    });

    it('Verify that "isBountyType" return the correct value', async () => {
        await wrapper.setProps({
            type: TYPE_BOUNTY,
        });

        expect(wrapper.vm.isBountyType).toBeTruthy();

        await wrapper.setProps({
            type: TYPE_REWARD,
        });

        expect(wrapper.vm.isBountyType).toBeFalsy();
    });

    it('Verify that "saveBtnDisabled" return the correct value', () => {
        wrapper.vm.$v.$invalid = false;

        expect(wrapper.vm.saveBtnDisabled).toBeFalsy();

        wrapper.vm.$v.$invalid = true;

        expect(wrapper.vm.saveBtnDisabled).toBeTruthy();
    });

    it('Verify that "saveBtnDisabled" return the correct value', () => {
        wrapper.vm.$v.$invalid = false;

        expect(wrapper.vm.saveBtnDisabled).toBeFalsy();

        wrapper.vm.$v.$invalid = true;

        expect(wrapper.vm.saveBtnDisabled).toBeTruthy();
    });

    it('Verify that "minPrice" return the correct value', () => {
        const minPrice = '0.0001';
        expect(wrapper.vm.minPrice).toBe(minPrice);
    });

    it('Verify that "maxPrice" return the correct value', () => {
        const maxPrice = '100000';
        expect(wrapper.vm.maxPrice).toBe(maxPrice);
    });

    it('Verify that "participantsAmount" return the correct value', async () => {
        expect(wrapper.vm.participantsAmount).toBe(2);

        await wrapper.setProps({
            editItem: {
                participants: [],
            },
        });

        expect(wrapper.vm.participantsAmount).toBe(0);
    });

    it('Verify that "translationContext" return the correct value', async () => {
        expect(wrapper.vm.translationContext).toEqual(translationContextTest);

        await wrapper.setProps({
            type: TYPE_REWARD,
        });

        translationContextTest.price_type = 'rewards.reward.price';
        translationContextTest.minQuantity = 0;

        expect(wrapper.vm.translationContext).toEqual(translationContextTest);
    });

    it('Verify that "modalTitle" return the correct value', async () => {
        expect(wrapper.vm.modalTitle).toBe('token.bounties.edit');

        await wrapper.setProps({
            type: TYPE_REWARD,
        });

        expect(wrapper.vm.modalTitle).toBe('token.rewards.edit');
    });

    it('Verify that "priceDisabled" return the correct value', async () => {
        expect(wrapper.vm.priceDisabled).toBeTruthy();

        await wrapper.setProps({
            type: TYPE_REWARD,
        });

        expect(wrapper.vm.priceDisabled).toBeFalsy();
    });

    it('Verify that "descriptionLength" return the correct value', async () => {
        expect(wrapper.vm.descriptionLength).toBe(0);

        await wrapper.setData({
            description: 'DescriptionTest',
        });

        expect(wrapper.vm.descriptionLength).toBe(15);
    });

    describe('Verify route of editModalType', () => {
        it('Verify route "edit_reward"', async (done) => {
            await wrapper.setData(propsTest);

            moxios.stubRequest('edit_reward', {
                status: 200,
                response: {
                    data: 'editReward',
                },
            });

            await wrapper.vm.saveReward();

            moxios.wait(() => {
                expect(mutations.editBounty).toHaveBeenCalled();
                expect(wrapper.emitted('close')).toBeTruthy();
                expect(wrapper.vm.title).toBe('');
                expect(wrapper.vm.price).toBe('');
                expect(wrapper.vm.description).toBe('');
                expect(wrapper.vm.quantity).toBe('');
                done();
            });
        });

        it('Verify route "add_new_reward"', async (done) => {
            await wrapper.setData({
                title: 'TitleTest',
                price: '100',
                description: 'DescriptionTest',
                quantity: '2',
            });

            await wrapper.setProps({
                type: TYPE_REWARD,
                modalType: ADD_TYPE_REWARDS_MODAL,
            });

            moxios.stubRequest('add_new_reward', {
                status: 200,
                response: {
                    data: 'addNewReward',
                },
            });

            await wrapper.vm.saveReward();

            moxios.wait(() => {
                expect(mutations.editReward).toHaveBeenCalled();
                expect(wrapper.emitted('close')).toBeTruthy();
                expect(wrapper.vm.title).toBe('');
                expect(wrapper.vm.price).toBe('');
                expect(wrapper.vm.description).toBe('');
                expect(wrapper.vm.quantity).toBe('');
                done();
            });
        });
    });
});
