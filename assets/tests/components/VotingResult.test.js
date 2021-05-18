import {createLocalVue, shallowMount} from '@vue/test-utils';
import VotingResult from '../../js/components/voting/VotingResult';
import Vuex from 'vuex';

/**
 * @return {VueConstructor}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.use(Vuex);
    localVue.use({
        install(Vue) {
            Vue.prototype.$t = (val) => val;
        },
    });

    return localVue;
}

/**
 * @param {Object} getters
 * @return {Wrapper<Vue>}
 */
function createWrapper(getters = {}) {
    const localVue = mockVue();
    localVue.component('b-progress', {});
    const store = new Vuex.Store({
        modules: {
            voting: {
                namespaced: true,
                getters: {
                    getCurrentVoting: () => {
                        return {
                            options: [],
                            userVotings: [],
                        };
                    },
                    getTokenName: () => 'foo',
                    ...getters,
                },
            },
        },
    });
    const wrapper = shallowMount(VotingResult, {
        store,
        localVue,
    });

    return wrapper;
}

describe('VotingResult', () => {
    it('should calculate total amount correctly', () => {
        const wrapper = createWrapper({
            getCurrentVoting: () => {
                return {
                    options: [],
                    userVotings: [
                        {amountMoney: 5},
                        {amountMoney: 1},
                        {amountMoney: 3},
                    ],
                };
            },
        });

        expect(wrapper.vm.totalAmount).toBe(9);
    });

    it('should calculate optionAmounts correctly', () => {
        const wrapper = createWrapper({
            getCurrentVoting: () => {
                return {
                    options: [],
                    userVotings: [
                        {
                            option: {id: 1},
                            amountMoney: 1,
                        },
                        {
                            option: {id: 2},
                            amountMoney: 4,
                        },
                    ],
                };
            },
        });

        expect(wrapper.vm.optionAmounts).toEqual({
            1: 1,
            2: 4,
        });
    });

    it('should calculate options correctly', () => {
        const wrapper = createWrapper({
            getCurrentVoting: () => {
                return {
                    options: [
                        {id: 1},
                        {id: 2},
                    ],
                    userVotings: [
                        {
                            option: {id: 1},
                            amountMoney: 1,
                        },
                        {
                            option: {id: 2},
                            amountMoney: 4,
                        },
                    ],
                };
            },
        });

        expect(wrapper.vm.options).toEqual([
            {
                id: 1,
                amount: '1',
                percentage: 20,
            },
            {
                id: 2,
                amount: '4',
                percentage: 80,
            },
        ]);
    });
});
