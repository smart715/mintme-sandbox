import {createLocalVue, shallowMount} from '@vue/test-utils';
import InitialTokenSellOrders from '../../js/components/token/InitialTokenSellOrders';
import tradeBalance from '../../js/storage/modules/trade_balance';
import axios from 'axios';
import moxios from 'moxios';
import Vuex from 'vuex';

/**
 * @return {VueConstructor<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.use(Vuex);
    localVue.use({
        install(Vue) {
            Vue.prototype.$routing = {generate: (val) => val};
            Vue.prototype.$axios = {retry: axios, single: axios};
            Vue.prototype.$t = (val) => val;
            Vue.prototype.$logger = {error: () => {}};
            Vue.prototype.$store = new Vuex.Store({
                modules: {
                    tradeBalance,
                },
            });
        },
    });
    return localVue;
}

/**
 * @param {Object} props
 * @param {Object} data
 * @param {Object} tradeBalanceState
 * @return {Wrapper<Vue>}
 */
function mockInitialTokenSellOrders(props = {}, data = {}, tradeBalanceState = {}) {
    const localVue = mockVue();
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
    localVue.use(Vuex);
    const store = new Vuex.Store({
        modules: {
            tradeBalance: {
                ...tradeBalance,
                state: {
                    balances: {
                        'TOK': {
                            available: '50.000000',
                            bonus: '2.00000',
                        },
                    },
                    serviceUnavailable: false,
                },
                ...tradeBalanceState,
            },
            market: {
                namespaced: true,
                getters: {
                    getCurrentMarket: () => marketTest,
                },
            },
        },
    });
    const wrapper = shallowMount(InitialTokenSellOrders, {
        store,
        localVue,
        propsData: {
            config: {
                totalOrders: 100,
                maxTokenForSale: 1000000,
            },
            ...props,
        },
        data() {
            return {
                ...data,
            };
        },
    });

    return wrapper;
}

describe('InitialTokenSellOrders ', () => {
    beforeEach(() => {
        moxios.install();
    });

    afterEach(() => {
        moxios.uninstall();
    });

    describe('selectedCurrency', () => {
        it('should compute selectedCurrency correctly', () => {
            const wrapper = mockInitialTokenSellOrders();

            expect(wrapper.vm.selectedCurrency).toEqual('testBaseSymbol');
        });
    });

    describe('noEnoughBalance', () => {
        it('should return true if tokenBalance is less than minTokenForSale', () => {
            const wrapper = mockInitialTokenSellOrders({
                tokenName: 'TOK',
                config: {
                    totalOrders: 100,
                    maxTokenForSale: 1000000,
                    minTokenForSale: 100,
                },
            });

            expect(wrapper.vm.noEnoughBalance).toBe(true);
        });

        it('should return false if tokenBalance is greater than minTokenForSale', () => {
            const wrapper = mockInitialTokenSellOrders({
                tokenName: 'TOK',
                config: {
                    totalOrders: 100,
                    maxTokenForSale: 1000000,
                    minTokenForSale: 49,
                },
            });

            expect(wrapper.vm.noEnoughBalance).toBe(false);
        });

        it('should return false if balanceLoaded is false', () => {
            const wrapper = mockInitialTokenSellOrders({
                tokenName: 'TOK',
                config: {
                    totalOrders: 100,
                    maxTokenForSale: 1000000,
                    minTokenForSale: 100,
                },
            }, {}, {
                state: {
                    balances: null,
                    serviceUnavailable: false,
                },
            });

            expect(wrapper.vm.noEnoughBalance).toBe(false);
        });
    });

    describe('currencyMode', () => {
        it('should compute currencyMode correctly', () => {
            const wrapper = mockInitialTokenSellOrders();

            localStorage.setItem('_currency_mode', 'USD');

            expect(wrapper.vm.currencyMode).toBe('USD');
        });
    });

    describe('disableSaveButton', () => {
        it('should return false when noEnoughBalance and loading and startingPriceModel.$invalid are false', () => {
            const wrapper = mockInitialTokenSellOrders({
                tokenName: 'TOK',
                config: {
                    totalOrders: 100,
                    maxTokenForSale: 1000000,
                    minTokenForSale: 49,
                },
            });

            wrapper.vm.$v = {
                startingPriceModel: {
                    $invalid: false,
                },
            };

            wrapper.setData({loading: false});

            expect(wrapper.vm.disableSaveButton).toBe(false);
        });

        it('should return true when noEnoughBalance is true', () => {
            const wrapper = mockInitialTokenSellOrders({
                tokenName: 'TOK',
                config: {
                    totalOrders: 100,
                    maxTokenForSale: 1000000,
                    minTokenForSale: 51,
                },
            });

            wrapper.vm.$v = {
                startingPriceModel: {
                    $invalid: false,
                },
            };

            wrapper.setData({loading: false});

            expect(wrapper.vm.disableSaveButton).toBe(true);
        });

        it('should return true when loading is true', () => {
            const wrapper = mockInitialTokenSellOrders({
                tokenName: 'TOK',
                config: {
                    totalOrders: 100,
                    maxTokenForSale: 1000000,
                    minTokenForSale: 49,
                },
            });

            wrapper.vm.$v = {
                startingPriceModel: {
                    $invalid: false,
                },
            };

            wrapper.setData({loading: true});

            expect(wrapper.vm.disableSaveButton).toBe(true);
        });

        it('should return true when startingPriceModel.$invalid is true', () => {
            const wrapper = mockInitialTokenSellOrders({
                tokenName: 'TOK',
                config: {
                    totalOrders: 100,
                    maxTokenForSale: 1000000,
                    minTokenForSale: 49,
                },
            });

            wrapper.vm.$v = {
                startingPriceModel: {
                    $invalid: true,
                },
            };

            wrapper.setData({loading: false});

            expect(wrapper.vm.disableSaveButton).toBe(true);
        });
    });

    describe('amountSliderLock', () => {
        it('should return true if tokenBalance is less or equal than minTokenForSale', () => {
            const wrapper = mockInitialTokenSellOrders({
                tokenName: 'TOK',
                config: {
                    totalOrders: 100,
                    maxTokenForSale: 1000000,
                    minTokenForSale: 50,
                },
            });

            expect(wrapper.vm.amountSliderLock).toBe(true);
        });

        it('should return false if tokenBalance is greater than minTokenForSale', () => {
            const wrapper = mockInitialTokenSellOrders({
                tokenName: 'TOK',
                config: {
                    totalOrders: 100,
                    maxTokenForSale: 1000000,
                    minTokenForSale: 49,
                },
            });

            expect(wrapper.vm.amountSliderLock).toBe(false);
        });

        it('should return false if balanceLoaded is false', () => {
            const wrapper = mockInitialTokenSellOrders({
                tokenName: 'TOK',
                config: {
                    totalOrders: 100,
                    maxTokenForSale: 1000000,
                    minTokenForSale: 100,
                },
            }, {}, {
                state: {
                    balances: null,
                    serviceUnavailable: false,
                },
            });

            expect(wrapper.vm.amountSliderLock).toBe(false);
        });
    });

    describe('translationContext', () => {
        it('should compute translationContext correctly', () => {
            const wrapper = mockInitialTokenSellOrders(
                {
                    tokenName: 'TOK',
                },
                {
                    minStartingPrice: '0.001',
                    maxEndPrice: '1.0',
                }
            );

            const expectedTranslationContext = {
                tokenName: 'TOK',
                minStartingPrice: '0.001',
                maxEndPrice: '1.0',
            };

            expect(wrapper.vm.translationContext).toEqual(expectedTranslationContext);
        });
    });

    describe('existInitialOrders', () => {
        it('should set showInitialOrdersForm to true when initial orders do not exist', (done) => {
            const wrapper = mockInitialTokenSellOrders(
                {
                    tokenName: 'TOK',
                },
                {},
                {
                    state: {
                        balances: null,
                        serviceUnavailable: false,
                    },
                }
            );

            moxios.stubRequest(wrapper.vm.$routing.generate('check_initial_orders', {
                tokenName: 'TOK',
            }), {
                status: 200,
                response: false,
            });

            wrapper.vm.existInitialOrders();

            moxios.wait(() => {
                expect(wrapper.vm.showInitialOrdersForm).toBe(true);
                expect(wrapper.vm.loading).toBe(false);
                done();
            });
        });

        it('should set showInitialOrdersForm to false when initial orders exist', (done) => {
            const wrapper = mockInitialTokenSellOrders(
                {
                    tokenName: 'TOK',
                },
                {},
                {
                    state: {
                        balances: null,
                        serviceUnavailable: false,
                    },
                }
            );

            moxios.stubRequest(wrapper.vm.$routing.generate('check_initial_orders', {
                tokenName: 'TOK',
            }), {
                status: 200,
                response: true,
            });

            wrapper.vm.existInitialOrders();

            moxios.wait(() => {
                expect(wrapper.vm.showInitialOrdersForm).toBe(false);
                expect(wrapper.vm.loading).toBe(false);
                done();
            });
        });

        it('should set orderServiceUnavailable to true on error', (done) => {
            const wrapper = mockInitialTokenSellOrders(
                {
                    tokenName: 'TOK',
                },
                {},
                {
                    state: {
                        balances: null,
                        serviceUnavailable: false,
                    },
                }
            );

            moxios.stubRequest(wrapper.vm.$routing.generate('check_initial_orders', {
                tokenName: 'TOK',
            }), {
                status: 500,
            });

            wrapper.vm.existInitialOrders();

            moxios.wait(() => {
                expect(wrapper.vm.orderServiceUnavailable).toBe(true);
                done();
            });
        });
    });

    describe('deleteInitialOrders', () => {
        it('should set showInitialOrdersForm to true on successful deletion', (done) => {
            const wrapper = mockInitialTokenSellOrders(
                {
                    tokenName: 'TOK',
                },
                {},
                {
                    state: {
                        balances: null,
                        serviceUnavailable: false,
                    },
                }
            );
            wrapper.vm.notifySuccess = jest.fn();

            moxios.stubRequest('delete_token_initial_orders', {
                status: 200,
            });

            wrapper.vm.deleteInitialOrders();

            moxios.wait(() => {
                expect(wrapper.vm.showInitialOrdersForm).toBe(true);
                expect(wrapper.vm.loading).toBe(false);
                expect(wrapper.vm.notifySuccess).toHaveBeenCalled();
                done();
            });
        });

        it('should call notifyError on access denied', (done) => {
            const wrapper = mockInitialTokenSellOrders(
                {
                    tokenName: 'TOK',
                },
                {},
                {
                    state: {
                        balances: null,
                        serviceUnavailable: false,
                    },
                }
            );
            wrapper.vm.notifyError = jest.fn();

            moxios.stubRequest('delete_token_initial_orders', {
                status: 403,
                response: {
                    message: 'message',
                },
            });

            wrapper.vm.deleteInitialOrders();

            moxios.wait(() => {
                expect(wrapper.vm.notifyError).toHaveBeenCalled();
                done();
            });
        });
    });

    describe('saveInitialOrders', () => {
        it('should set showInitialOrdersForm to false on successful creation', (done) => {
            const wrapper = mockInitialTokenSellOrders(
                {
                    tokenName: 'TOK',
                },
                {
                    startingPriceModel: '0.0001',
                },
                {
                    state: {
                        balances: null,
                        serviceUnavailable: false,
                    },
                }
            );
            wrapper.vm.notifySuccess = jest.fn();
            moxios.stubRequest('token_initial_orders', {
                status: 201,
            });

            wrapper.vm.saveInitialOrders();

            moxios.wait(() => {
                expect(wrapper.vm.showInitialOrdersForm).toBe(false);
                expect(wrapper.vm.loading).toBe(false);
                expect(wrapper.vm.notifySuccess).toHaveBeenCalled();
                done();
            });
        });

        it('should notify error on failed request', (done) => {
            const wrapper = mockInitialTokenSellOrders(
                {
                    tokenName: 'TOK',
                },
                {
                    startingPriceModel: '0.0001',
                },
                {
                    state: {
                        balances: null,
                        serviceUnavailable: false,
                    },
                }
            );
            wrapper.vm.notifyError = jest.fn();

            moxios.stubRequest(wrapper.vm.$routing.generate('token_initial_orders'), {
                status: 403,
                response: {
                    message: 'message',
                },
            });

            wrapper.vm.saveInitialOrders();

            moxios.wait(() => {
                expect(wrapper.vm.notifyError).toHaveBeenCalled();
                done();
            });
        });
    });

    describe('Watchers', () => {
        describe('priceGrowth', () => {
            it('should call getPrice when priceGrowth changes', async () => {
                const wrapper = mockInitialTokenSellOrders();
                const getPrice = jest.spyOn(wrapper.vm, 'getPrice');

                await wrapper.setData({priceGrowth: 43});

                expect(getPrice).toHaveBeenCalled();
            });
        });

        describe('tokensForSale', () => {
            it('should call getPrice when tokensForSale changes', async () => {
                const wrapper = mockInitialTokenSellOrders();
                const getPrice = jest.spyOn(wrapper.vm, 'getPrice');

                await wrapper.setData({tokensForSale: 100});

                expect(getPrice).toHaveBeenCalled();
            });
        });
    });

    describe('getPrice function', () => {
        it('getPrice function work correctly with minimum startingPrice', () => {
            const wrapper = mockInitialTokenSellOrders({},
                {
                    tokSubunit: 4,
                    startingPriceModel: '0.0001',
                    endPrice: 0,
                    priceGrowth: 42,
                });

            wrapper.vm.getPrice();
            expect(wrapper.vm.endPrice).toBe('0.0100');
            expect(wrapper.vm.mintmeAmountToReceive).toBe('5050.0000');
        });

        it('getPrice function work correctly', () => {
            const wrapper = mockInitialTokenSellOrders({},
                {
                    tokSubunit: 4,
                    startingPriceModel: '1',
                    endPrice: 0,
                    priceGrowth: 42,
                });

            wrapper.vm.getPrice();
            expect(wrapper.vm.endPrice).toBe('10.2750');
            expect(wrapper.vm.mintmeAmountToReceive).toBe('6872564.3927');
        });
    });
});
