import {createLocalVue, shallowMount} from '@vue/test-utils';
import VotingVote from '../../js/components/voting/VotingVote';
import voting from '../../js/storage/modules/voting';
import tradeBalance from '../../js/storage/modules/trade_balance';
import Vuex from 'vuex';
import moxios from 'moxios';
import axios from 'axios';

/**
 * @return {VueConstructor}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.use(Vuex);
    localVue.use({
        install(Vue) {
            Vue.prototype.$axios = {retry: axios, single: axios};
            Vue.prototype.$t = (val) => val;
            Vue.prototype.$routing = {generate: (val) => val};
            Vue.prototype.$toasted = {show: (val) => val};
            Vue.prototype.$logger = {error: (val) => {}};
        },
    });

    return localVue;
}

/**
 * @param {Object} overrideVoting
 * @param {Object} overrideTradeBalance
 * @return {Wrapper<Vue>}
 */
function createWrapper(overrideVoting = {}, overrideTradeBalance = {}) {
    const localVue = mockVue();
    const store = new Vuex.Store({
        modules: {
            tradeBalance: {
                ...tradeBalance,
                ...overrideTradeBalance,
            },
            voting: {
                ...voting,
                ...overrideVoting,
            },
        },
    });
    const wrapper = shallowMount(VotingVote, {
        store,
        localVue,
    });

    return wrapper;
}

describe('VotingVote', () => {
    beforeEach(() => {
        moxios.install();
    });

    afterEach(() => {
        moxios.uninstall();
    });

    describe('btnDisabled', () => {
        it('should be true in case no selection', async () => {
            const wrapper = createWrapper({}, {
                getters: {
                    ...tradeBalance.getters,
                    getQuoteFullBalance: () => 1,
                },
            });
            expect(wrapper.vm.btnDisabled).toBe(true);

            await wrapper.setData({
                selected: 1,
            });
            expect(wrapper.vm.btnDisabled).toBe(false);
        });

        it('should be true in case no requesting', async () => {
            const wrapper = createWrapper({}, {
                getters: {
                    ...tradeBalance.getters,
                    getQuoteFullBalance: () => 1,
                },
            });

            await wrapper.setData({
                selected: 1,
                requesting: true,
            });
            expect(wrapper.vm.btnDisabled).toBe(true);

            await wrapper.setData({
                requesting: false,
            });
            expect(wrapper.vm.btnDisabled).toBe(false);
        });

        it('should be true in case quote balance not loaded', async () => {
            let wrapper = createWrapper({}, {
                getters: {
                    ...tradeBalance.getters,
                    getQuoteFullBalance: () => 0,
                },
            });

            await wrapper.setData({
                selected: 1,
                requesting: false,
            });
            expect(wrapper.vm.btnDisabled).toBe(true);

            wrapper = createWrapper({}, {
                getters: {
                    ...tradeBalance.getters,
                    getQuoteFullBalance: () => 1,
                },
            });

            await wrapper.setData({
                selected: 1,
                requesting: false,
            });
            expect(wrapper.vm.btnDisabled).toBe(false);
        });
    });

    it('shouldn\'t call storeVote() in case balance not lower than the limit', async () => {
        let storeVoteCalled = false;
        let wrapper = createWrapper();

        await wrapper.setProps({minAmount: 1, loggedIn: true});

        wrapper.vm.storeVote = () => storeVoteCalled = true;
        wrapper.vm.vote();
        expect(storeVoteCalled).toBe(false);
        wrapper = createWrapper(
            {
                getters: {
                    ...voting.getters,
                    getCurrentVoting: () => {
                        return {
                            options: {'-1': {id: 1}},
                        };
                    },
                },
            },
            {
                getters: {
                    ...tradeBalance.getters,
                    getQuoteFullBalance: () => 2,
                },
            }
        );

        await wrapper.setProps({minAmount: 1, loggedIn: true});

        wrapper.vm.storeVote = () => storeVoteCalled = true;
        wrapper.vm.vote();
        expect(storeVoteCalled).toBe(true);
    });

    it('should storeVote successfully', async (done) => {
        let updateVotingCalled = false;
        const wrapper = createWrapper({
            getters: {
                ...voting.getters,
                getCurrentVoting: () => {
                    return {
                        options: {'-1': {id: 1}},
                    };
                },
            },
            actions: {
                ...voting.actions,
                updateVoting: () => updateVotingCalled = true,
            },
        });

        moxios.stubRequest('user_vote', {
            status: 200,
            response: {
                data: {
                    voting: 'foo',
                },
            },
        });

        await wrapper.vm.storeVote();

        moxios.wait(() => {
            expect(updateVotingCalled).toBe(true);
            done();
        });
    });
});
