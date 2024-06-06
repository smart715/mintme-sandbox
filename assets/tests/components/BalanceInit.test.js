import {shallowMount, createLocalVue} from '@vue/test-utils';
import BalanceInit from '../../js/components/trade/BalanceInit';
import tradeBalance from '../../js/storage/modules/trade_balance';
import Vuex from 'vuex';
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
            Vue.prototype.$routing = {generate: jest.fn((val) => val)};
            Vue.prototype.$t = (val) => val;
            Vue.prototype.$toasted = {show: () => {}};
            Vue.prototype.$logger = {error: jest.fn()};
        },
    });

    return localVue;
}

/**
 * @return {Wrapper<vue>}
 * @param {object} options
 */
function mockDefaultWrapper(options = {}) {
    const marketTest = {
        hiddenName: 'TOK000000000001WEB',
        tokenName: 'tok1',
        quote: {
            symbol: 'testQuoteSymbol',
            identifier: 'WEB',
            subunit: 4,
        },
        base: {
            symbol: 'testBaseSymbol',
            identifier: 'BTC',
            subunit: 4,
        },
    };
    const localVue = mockVue();
    localVue.use(Vuex);
    const store = new Vuex.Store({
        modules: {
            tradeBalance: {
                ...tradeBalance,
                state: {
                    balances: {},
                    quoteBalance: {},
                    quoteBonusBalance: {},
                    quoteFullBalance: {},
                    baseBalance: {},
                },
            },
            websocket: {
                namespaced: true,
                actions: {
                    addOnOpenHandler: jest.fn(),
                    addMessageHandler: () => {},
                    init: jest.fn(),
                    authorize: jest.fn(),
                },
                getters: {
                    getClient: jest.fn(),
                },
            },
            market: {
                namespaced: true,
                getters: {
                    getCurrentMarket: () => marketTest,
                },
            },
        },
    });

    return shallowMount(BalanceInit, {
        localVue,
        store,
        propsData: {
            websocketUrl: '',
        },
        ...options,
    });
}

describe('BalanceInit', () => {
    describe('updateAssets', () => {
        beforeEach(() => {
            moxios.install();
        });

        afterEach(() => {
            moxios.uninstall();
        });

        it('Calls setServiceUnavailable when axios request fails and loggedIn = false', async (done) => {
            const wrapper = mockDefaultWrapper();
            const setServiceUnavailable = jest.spyOn(wrapper.vm, 'setServiceUnavailable');

            await wrapper.setProps({loggedIn: false});

            moxios.stubRequest('tokens_ping', {
                status: 500,
            });

            await wrapper.vm.updateAssets();

            moxios.wait(() => {
                expect(wrapper.vm.balances).toBe(false);
                expect(setServiceUnavailable).toHaveBeenCalled();
                done();
            });
        });

        it('Should fill current market balance with zero balance', async (done) => {
            const wrapper = mockDefaultWrapper();

            await wrapper.setProps({loggedIn: true});

            moxios.stubRequest('tokens', {
                status: 200,
                response: {},
            });

            await wrapper.vm.updateAssets();

            moxios.wait(() => {
                expect(wrapper.vm.balances['testQuoteSymbol']).toEqual({
                    available: '0',
                    identifier: 'WEB',
                    subunit: 4,
                });
                done();
            });
        });

        // TODO: has issues with axios retry, fix later
        it.skip('Calls setServiceUnavailable if axios request fails', async (done) => {
            const wrapper = mockDefaultWrapper();
            const setServiceUnavailable = jest.spyOn(wrapper.vm, 'setServiceUnavailable');

            await wrapper.setProps({loggedIn: true});

            moxios.stubRequest('tokens', {
                status: 500,
            });

            await wrapper.vm.updateAssets();

            moxios.wait(() => {
                expect(setServiceUnavailable).toHaveBeenCalled();
                done();
            });
        });

        it.skip('Should update balances with request response', async (done) => {
            const wrapper = mockDefaultWrapper();
            await wrapper.setProps({loggedIn: true});

            moxios.stubRequest('tokens', {
                status: 200,
                response: {
                    common: {
                        testQuoteSymbol: {
                            available: '100',
                            identifier: 'TOK000000000001',
                            subunit: 4,
                        },
                    },
                    predefined: {},
                },
            });

            await wrapper.vm.updateAssets();

            moxios.wait(() => {
                expect(wrapper.vm.balances['testQuoteSymbol']).toEqual({
                    available: '100',
                    identifier: 'TOK000000000001',
                    subunit: 4,
                });
                done();
            });
        });

        it('Should call sendMessage on authorization success', async (done) => {
            const wrapper = mockDefaultWrapper();
            const sendMessage = jest.spyOn(wrapper.vm, 'sendMessage');

            wrapper.vm.authorize = jest.fn().mockResolvedValueOnce(new Promise((resolve) => resolve()));

            await wrapper.setProps({loggedIn: true});

            moxios.stubRequest('tokens', {
                status: 200,
                response: {
                    authorization: true,
                },
            });

            await wrapper.vm.updateAssets();

            moxios.wait(() => {
                expect(sendMessage).toHaveBeenCalled();
                done();
            });
        });
    });

    describe('fetchAvailableBalance', () => {
        beforeEach(() => {
            moxios.install();
        });

        afterEach(() => {
            moxios.uninstall();
        });

        it('Returns data if axios request is successful', async (done) => {
            const wrapper = mockDefaultWrapper();

            moxios.stubRequest('token_balance', {
                status: 200,
                response: {
                    available: 100,
                },
            });
            const result = await wrapper.vm.fetchAvailableBalance('my_token');

            moxios.wait(() => {
                expect(result).toBe(100);
                done();
            });
        });

        it('Throws error if axios request fails', async (done) => {
            const wrapper = mockDefaultWrapper();
            const loggerSpy = jest.spyOn(wrapper.vm.$logger, 'error');

            moxios.stubRequest('token_balance', {
                status: 500,
            });

            await wrapper.vm.fetchAvailableBalance();

            moxios.wait(() => {
                expect(loggerSpy).toHaveBeenCalled();
                done();
            });
        });
    });

    describe('getAvailableBalance', () => {
        beforeEach(() => {
            moxios.install();
        });

        afterEach(() => {
            moxios.uninstall();
        });

        it('returns the correct available balance', async () => {
            const wrapper = mockDefaultWrapper();
            const nextBalance = {
                available: '1000',
            };
            const currentBalance = {
                owner: true,
                fullname: 'Token',
                subunit: 2,
            };

            moxios.stubRequest('token_balance', {
                status: 200,
                response: {
                    available: 100,
                },
            });

            const result = await wrapper.vm.getAvailableBalance(currentBalance, nextBalance);

            expect(result).toEqual('100.00');
        });
    });

    describe('setMessageBalance', () => {
        beforeEach(() => {
            moxios.install();
        });

        afterEach(() => {
            moxios.uninstall();
        });

        it('should update the quoteBalance when identifier is same as market.quote.identifier', async (done) => {
            const wrapper = mockDefaultWrapper();
            const balanceToUpdate = {
                identifier: 'WEB',
                subunit: 2,
                owner: true,
            };
            const responseBalance = {
                available: '2000',
            };

            moxios.stubRequest('token_balance', {
                status: 200,
                response: {
                    available: 100,
                },
            });

            await wrapper.vm.setMessageBalance(balanceToUpdate, responseBalance);

            moxios.wait(() => {
                expect(wrapper.vm.quoteBalance).toEqual(balanceToUpdate.available);
                done();
            });
        });

        it('should update the baseBalance when identifier is same as market.base.identifier', async (done) => {
            const wrapper = mockDefaultWrapper();
            const balanceToUpdate = {
                identifier: 'BTC',
                subunit: 2,
                owner: true,
            };
            const responseBalance = {
                available: '2000',
            };

            moxios.stubRequest('token_balance', {
                status: 200,
                response: {
                    available: 100,
                },
            });

            await wrapper.vm.setMessageBalance(balanceToUpdate, responseBalance);

            moxios.wait(() => {
                expect(wrapper.vm.baseBalance).toEqual(balanceToUpdate.available);
                done();
            });
        });
    });
});
