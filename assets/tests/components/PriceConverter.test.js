import {shallowMount, createLocalVue} from '@vue/test-utils';
import PriceConverter from '../../js/components/PriceConverter';
import Vuex from 'vuex';
import moxios from 'moxios';
import axios from 'axios';
import Decimal from 'decimal.js';

const localVue = mockVue();

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
            Vue.prototype.$logger = {error: (val) => {}};
            Vue.prototype.$t = (val) => {};
        },
    });
    return localVue;
}

/**
 * @param {Object} mutations
 * @param {Object} state
 * @return {Vuex.Store}
 */
function createSharedTestStore(mutations, state) {
    return new Vuex.Store({
        modules: {
            rates: {
                mutations,
                state,
                namespaced: true,
                getters: {
                    getRequesting: () => 0,
                    getRates: () => ratesTest,
                },
            },
            minOrder: {
                namespaced: true,
                getters: {
                    getMinOrder: () => 10,
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
};

/**
 * @param {Object} props
 * @return {Object}
 */
function createSharedTestProps(props = {}) {
    return {
        amount: '100',
        from: '',
        to: '',
        subunit: 8,
        symbol: '',
        delay: 0,
        convertedAmountProp: '',
        ...props,
    };
};

const marketTest = {
    hiddenName: 'TOK000000000001WEB',
    tokenName: 'tok1',
    quote: {
        symbol: 'testQuoteSymbol',
        subunit: 4,
    },
    base: {
        symbol: 'testBaseSymbol',
        subunit: 4,
    },
};

const ratesTest = {
    USD: {
        BTC: '100',
    },
};

describe('PriceConverter', () => {
    let mutations;
    let store;
    let state;
    let wrapper;

    beforeEach(() => {
        moxios.install();

        mutations = {
            setRates: jest.fn(),
            setRequesting: jest.fn(),
        };

        state = {
            setRates: 0,
            setRequesting: 0,
        };

        store = createSharedTestStore(mutations, state);

        wrapper = shallowMount(PriceConverter, {
            localVue: localVue,
            store: store,
            propsData: createSharedTestProps(),
        });

        ratesTest.USD.BTC = '100';
    });

    afterEach(() => {
        moxios.uninstall();
    });

    it('Verify that "minOrderInCrypto" return the correct value', async () => {
        await wrapper.setProps({
            from: 'USD',
            to: 'BTC',
        });

        expect(wrapper.vm.minOrderInCrypto).toBe('0.1');
    });

    it('Verify that "convertAmountWithRate" return the correct value', async () => {
        await wrapper.setProps({
            from: 'USD',
            to: 'BTC',
        });

        expect(wrapper.vm.convertAmountWithRate).toEqual(new Decimal(10000));
    });

    it('Verify that "baseSubunit" return the correct value', () => {
        expect(wrapper.vm.baseSubunit).toBe(marketTest.base.subunit);
    });

    it('Verify that "rate" return the correct value', async () => {
        await wrapper.setProps({
            from: 'USD',
            to: 'BTC',
        });

        expect(wrapper.vm.rate).toBe('100');
    });

    it('Verify that "rateLoaded" return the correct value', async () => {
        expect(wrapper.vm.rateLoaded).toBe(false);

        await wrapper.setProps({
            from: 'USD',
            to: 'BTC',
        });
        expect(wrapper.vm.rateLoaded).toBeTruthy();
    });

    it('Verify that "rateLoaded" return the correct value', async () => {
        expect(wrapper.vm.rateLoaded).toBe(false);

        await wrapper.setProps({
            from: 'USD',
            to: 'BTC',
        });
        expect(wrapper.vm.rateLoaded).toBeTruthy();
    });

    it('Verify that "getExchangeRates" is working correctly', (done) => {
        moxios.stubRequest('exchange_rates', {
            status: 200,
            response: {
                ratesTest,
            },
        });

        wrapper.vm.getExchangeRates();
        expect(wrapper.vm.getRates).toEqual(ratesTest);

        ratesTest.USD.BTC = '200';

        moxios.wait(() => {
            expect(wrapper.vm.getRates).toEqual(ratesTest);
            done();
        });
    });
});
