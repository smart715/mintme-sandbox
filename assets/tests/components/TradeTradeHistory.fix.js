import {createLocalVue, shallowMount} from '@vue/test-utils';
import Vuex from 'vuex';
import {status} from '../../js/storage/modules/websocket';
import TradeTradeHistory from '../../js/components/trade/TradeTradeHistory';
import moxios from 'moxios';
import axios from 'axios';

let rebrandingTest = (val) => {
    if (!val) {
        return val;
    }

    const brandDict = [
        {regexp: /(webTest)/g, replacer: 'mintimeTest'},
    ];
    brandDict.forEach((item) => {
        if (typeof val !== 'string') {
            return;
        }
        val = val.replace(item.regexp, item.replacer);
    });

    return val;
};

const $routing = {generate: (val, params) => val};

const $store = new Vuex.Store({
    modules: {status},
});

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.use(Vuex);
    localVue.use({
        install(Vue) {
            Vue.prototype.$axios = {
                retry: axios,
                single: axios,
            };
            Vue.prototype.$routing = $routing;
            Vue.prototype.$store = $store;
        },
    });
    return localVue;
};

let propsForTestCorrectlyRenders = {
    market: {
        base: {
            name: 'Betcoin',
            symbol: 'BTC',
            subunit: 8,
            identifier: 'BTC',
        },
        quote: {
            name: 'Webchain',
            symbol: 'WEB',
            subunit: 4,
            identifier: 'WEB',
        },
    },
};

const tableData = [
    {
        timestamp: 1551876719.890195,
        side: 2,
        amount: '5.000000000000000000',
        price: '1.000000000000000000',
        fee: '0.500000000000000000',
        market: {
            token: {
                name: 'user110token',
            },
            currencySymbol: 'WEB',
            hiddenName: 'TOK000000000010WEB',
        },
        maker: {
            profile: {
                nickname: 'foo',
            },
        },
        taker: {
            profile: {
                nickname: 'foo',
            },
        },
    },
];

describe('TradeTradeHistory', () => {
    beforeEach(() => {
        moxios.install();
    });

    afterEach(() => {
        moxios.uninstall();
    });

    describe('should compute shouldTruncate correctly', () => {
        it('when truncate isn\'t necessary', () => {
            const localVue = mockVue();
            propsForTestCorrectlyRenders.market.quote.symbol = '12345678901234567';
            const wrapper = shallowMount(TradeTradeHistory, {
                localVue,
                propsData: propsForTestCorrectlyRenders,
            });
            expect(wrapper.vm.shouldTruncate).toBe(false);
        });
        it('when truncate is necessary', () => {
            const localVue = mockVue();
            propsForTestCorrectlyRenders.market.quote.symbol = '123456789012345678';
            const wrapper = shallowMount(TradeTradeHistory, {
                localVue,
                propsData: propsForTestCorrectlyRenders,
            });
            expect(wrapper.vm.shouldTruncate).toBe(true);
        });
        propsForTestCorrectlyRenders.market.quote.symbol = 'WEB';
    });

    it('should compute hasOrders correctly', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(TradeTradeHistory, {
            localVue,
            propsData: propsForTestCorrectlyRenders,
        });
        wrapper.vm.tableData = false;
        expect(wrapper.vm.hasOrders).toBe(false);
        wrapper.vm.tableData = tableData;
        expect(wrapper.vm.hasOrders).toBe(true);
    });

    it('should compute loaded correctly', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(TradeTradeHistory, {
            localVue,
            propsData: propsForTestCorrectlyRenders,
        });
        wrapper.vm.tableData = tableData;
        expect(wrapper.vm.hasOrders).toBe(true);
    });

    it('should compute lastId correctly', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(TradeTradeHistory, {
            localVue,
            propsData: propsForTestCorrectlyRenders,
        });
        wrapper.vm.tableData = tableData;
        expect(wrapper.vm.lastId).toBe(0);
        wrapper.vm.tableData = [{id: 'foo'}];
        expect(wrapper.vm.lastId).toBe('foo');
    });

    describe('updateTableData', () => {
        it('should do $axios request and set tableData correctly when attach is undefined and result of $axios request is not empty', (done) => {
            const localVue = mockVue();
            const wrapper = shallowMount(TradeTradeHistory, {
                localVue,
                propsData: propsForTestCorrectlyRenders,
            });
            wrapper.vm.updateTableData();

            moxios.stubRequest('executed_orders', {
                status: 200,
                response: ['foo'],
            });

            moxios.wait(() => {
                expect(wrapper.vm.tableData).toEqual(['foo']);
                done();
            });
        });

        it('should do $axios request and set tableData correctly when attach is true and result of $axios request is not empty', (done) => {
            const localVue = mockVue();
            const wrapper = shallowMount(TradeTradeHistory, {
                localVue,
                propsData: propsForTestCorrectlyRenders,
            });
            wrapper.vm.updateTableData(true);

            moxios.stubRequest('executed_orders', {
                status: 200,
                response: ['foo'],
            });

            moxios.wait(() => {
                expect(wrapper.vm.tableData).toEqual(['foo', 'foo']);
                done();
            });
        });

        it('should do $axios request and set tableData correctly when attach is true and result of $axios request is empty', (done) => {
            const localVue = mockVue();
            const wrapper = shallowMount(TradeTradeHistory, {
                localVue,
                propsData: propsForTestCorrectlyRenders,
            });
            wrapper.vm.tableData = 'foo';
            wrapper.vm.updateTableData(true);

            moxios.stubRequest('executed_orders', {
                status: 200,
            });

            moxios.wait(() => {
                expect(wrapper.vm.tableData).toBe('foo');
                done();
            });
        });
    });

    it('renders correctly with assigned props', () => {
        const localVue = mockVue();
        propsForTestCorrectlyRenders.market.base.symbol = 'webTest';
        const wrapper = shallowMount(TradeTradeHistory, {
            localVue,
            filters: {
                rebranding: function(val) {
                    return rebrandingTest(val);
                },
            },
            propsData: propsForTestCorrectlyRenders,
        });
        expect(wrapper.html()).toContain('mintimeTest');
    });
});
