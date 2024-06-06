import {shallowMount, createLocalVue} from '@vue/test-utils';
import TipCommentModal from '../../js/components/modal/TipCommentModal';
import Vuex from 'vuex';
import moxios from 'moxios';
import axios from 'axios';
import Vuelidate from 'vuelidate';

const localVue = mockVue();

const getBalancesTest = {
    'WEB': {available: 100},
    'token': {available: 100},
};

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.use(Vuex);
    localVue.use(Vuelidate);
    localVue.use({
        install(Vue) {
            Vue.prototype.$routing = {generate: (val) => val};
            Vue.prototype.$axios = {retry: axios, single: axios};
            Vue.prototype.$toasted = {show: () => {}};
            Vue.prototype.$t = (val) => val;
            Vue.prototype.$logger = {error: () => {}};
        },
    });

    return localVue;
}

/**
 * @param {Object} getters
 * @return {Vuex.Store}
 */
function createSharedTestStore(getters) {
    return new Vuex.Store({
        modules: getters,
    });
}

/**
 * @param {Object} props
 * @param {Object} computed
 * @param {Vuex.Store} store
 * @return {Wrapper<Vue>}
 */
function mockModal(props = {}, computed, store) {
    return shallowMount(TipCommentModal, {
        localVue: localVue,
        sync: false,
        store: store,
        propsData: props,
        computed: {
            ...computed,
        },
    });
}

describe('TipCommentModal', () => {
    let wrapper;
    let store;
    let getters;

    beforeEach(() => {
        getters = {
            user: {
                namespaced: true,
                getters: {
                    getId: () => 1,
                },
            },
            tradeBalance: {
                namespaced: true,
                getters: {
                    getBalances: () => getBalancesTest,
                },
            },
            posts: {
                namespaced: true,
                getters: {
                    getCommentTipMinAmount: () => 0.1,
                    getCommentTipMaxAmount: () => 10000,
                    getCommentTipCost: () => 1,
                },
            },
        };

        store = createSharedTestStore(getters);

        moxios.install();

        wrapper = mockModal({deployedTokens: [{name: 'token'}], comment: {id: 1}}, {}, store);
    });

    afterEach(() => {
        moxios.uninstall();
    });

    it('should validate insufficient mintme balance correctly', () => {
        expect(wrapper.vm.mintmeBalanceValidator()).toBe(true);

        const mutatedGetters = {
            ...getters,
            posts: {
                namespaced: true,
                getters: {
                    getCommentTipMinAmount: () => 0.1,
                    getCommentTipMaxAmount: () => 10000,
                    getCommentTipCost: () => 100000,
                },
            },
        };

        wrapper = mockModal({deployedTokens: [{name: 'token'}]}, {}, createSharedTestStore(mutatedGetters));

        expect(wrapper.vm.mintmeBalanceValidator()).toBe(false);
    });

    it('should validate insufficient token balance correctly', () => {
        expect(wrapper.vm.tokenBalanceValidator(10)).toBe(true);
        expect(wrapper.vm.tokenBalanceValidator(10000)).toBe(false);
    });

    it('should create tip correctly', async (done) => {
        const setCommentTippedFn = jest.fn();
        wrapper = mockModal({deployedTokens: [{name: 'token'}], comment: {id: 1}}, {}, createSharedTestStore({
            ...getters,
            posts: {
                namespaced: true,
                getters: {
                    getCommentTipMinAmount: () => 0.1,
                    getCommentTipMaxAmount: () => 10,
                    getCommentTipCost: () => 0,
                },
                mutations: {
                    setCommentTipped: setCommentTippedFn,
                },
            },
        }));

        const closeModalFn = jest.spyOn(wrapper.vm, 'closeModal');
        const notifySuccessFn = jest.spyOn(wrapper.vm, 'notifySuccess');

        await wrapper.setProps({visible: true});
        await wrapper.setData({amount: 0.1});

        moxios.stubRequest('tip_comment', {
            status: 200,
            response: true,
        });

        await wrapper.vm.tipComment();

        expect(closeModalFn).toBeCalled();
        expect(setCommentTippedFn).toBeCalled();
        expect(notifySuccessFn).toBeCalled();

        done();
    });
});
