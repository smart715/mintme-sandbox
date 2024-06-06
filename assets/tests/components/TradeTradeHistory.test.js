import {createLocalVue, shallowMount} from '@vue/test-utils';
import Vuex from 'vuex';
import {status} from '../../js/storage/modules/websocket';
import TradeTradeHistory from '../../js/components/trade/TradeTradeHistory';
import moxios from 'moxios';
import axios from 'axios';

const rebrandingTest = (val) => {
    if (!val) {
        return val;
    }

    const brandDict = [
        {regexp: /(webTest)/g, replacer: 'mintimeTest'},
    ];
    brandDict.forEach((item) => {
        if ('string' !== typeof val) {
            return;
        }
        val = val.replace(item.regexp, item.replacer);
    });

    return val;
};

const $routing = {generate: (val, params) => val};

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.use(Vuex);
    const $store = new Vuex.Store({
        modules: {
            status,
            websocket: {
                namespaced: true,
                actions: {
                    addMessageHandler: () => {},
                    addOnOpenHandler: () => {},
                },
            },
        },
    });
    localVue.use({
        install(Vue) {
            Vue.prototype.$axios = {
                retry: axios,
                single: axios,
            };
            Vue.prototype.$sortCompare = () => {};
            Vue.prototype.$routing = $routing;
            Vue.prototype.$store = $store;
            Vue.prototype.$t = (val) => val;
            Vue.prototype.$logger = {error: () => {}};
        },
    });
    return localVue;
}

const propsForTestCorrectlyRenders = {
    websocketUrl: '',
    market: {
        base: {
            name: 'Betcoin',
            symbol: 'BTC',
            subunit: 8,
            identifier: 'BTC',
            image: {
                url: require('../../img/BTC.svg'),
            },
        },
        quote: {
            name: 'Webchain',
            symbol: 'WEB',
            subunit: 4,
            identifier: 'WEB',
            image: {
                url: require('../../img/default_token_avatar.svg'),
            },
        },
    },
};

const tableData = [
    {
        'maker':
        {
            'id': 1,
            'profile':
            {
                'nickname': 'test',
                'token': {
                    'name': 'test',
                    'image': {
                        'url': '/media/default_token.png',
                        'avatar_small': '../avatar_middle/media/default_token.png',
                    },
                    'symbol': 'test',
                    'deploymentStatus': 'not-deployed',
                    'blocked': false,
                    'identifier': 'TOK000000000001',
                    'subunit': 4,
                },
                'image': {
                    'url': '/media/default_token.png',
                    'avatar_small': '../avatar_middle/media/default_token.png',
                },
            },
        },
        'taker': {
            'id': 1,
            'profile': {
                'nickname': 'test',
                'firstName': null,
                'lastName': null,
                'city': null,
                'country': null,
                'description': null,
                'anonymous': false,
                'token': {
                    'name': 'test',
                    'symbol': 'test',
                    'deploymentStatus': 'not-deployed',
                    'blocked': false,
                    'identifier': 'TOK000000000001',
                    'subunit': 4,
                },
                'image': {
                    'avatar_small': '../avatar_middle/media/default_token.png',
                },
            },
        },
        'status': 'finished',
        'id': 101,
        'timestamp': 1596541004,
        'createdTimestamp': null,
        'side': 2,
        'amount': '0.001900000000',
        'price': '1.000000000000',
        'fee': '0.000000000000',
        'market': {
            'base': {
                'name': 'Webchain',
                'symbol': 'WEB',
                'subunit': 4,
                'tradable': true,
                'exchangeble': true,
                'identifier': 'WEB',
            },
            'quote': {
                'name': 'test',
                'symbol': 'test',
                'deploymentStatus': 'not-deployed',
                'blocked': false,
                'identifier': 'TOK000000000001',
                'subunit': 4,
            },
            'identifier': 'TOK000000000001WEB',
        },
    }];

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
            propsForTestCorrectlyRenders.market.quote.symbol = '123';
            const wrapper = shallowMount(TradeTradeHistory, {
                localVue,
                propsData: propsForTestCorrectlyRenders,
            });
            expect(wrapper.vm.shouldTruncate).toBe(false);
        });
        it('when truncate is necessary', () => {
            const localVue = mockVue();
            propsForTestCorrectlyRenders.market.quote.symbol = '1234';
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
            attachTo: document.body,
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
            attachTo: document.body,
        });
        wrapper.vm.tableData = tableData;
        expect(wrapper.vm.hasOrders).toBe(true);
    });

    it('should compute lastId correctly', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(TradeTradeHistory, {
            localVue,
            propsData: propsForTestCorrectlyRenders,
            attachTo: document.body,
        });
        wrapper.vm.tableData = tableData;
        expect(wrapper.vm.lastId).toBe(101);
        wrapper.vm.tableData[0].id = 102;
        expect(wrapper.vm.lastId).toBe(102);
    });

    describe('updateTableData', () => {
        it(`should do $axios request and set tableData correctly`, () => {
            moxios.stubRequest('executed_orders', {
                status: 200,
                response: tableData,
            });

            const localVue = mockVue();
            const wrapper = shallowMount(TradeTradeHistory, {
                localVue,
                propsData: propsForTestCorrectlyRenders,
                attachTo: document.body,
            });
            wrapper.vm.addOrderTableData(tableData);

            moxios.wait(() => {
                expect(wrapper.vm.tableData).toEqual(tableData);
            });
        });

        it(
            `should do $axios request and set tableData correctly
             when attach is false and result of $axios request is not empty`,
            (done) => {
                moxios.stubRequest('executed_orders', {
                    status: 200,
                    response: tableData,
                });

                const localVue = mockVue();
                const wrapper = shallowMount(TradeTradeHistory, {
                    localVue,
                    propsData: propsForTestCorrectlyRenders,
                    attachTo: document.body,
                });

                wrapper.vm.updateTableData();

                moxios.wait(() => {
                    expect(wrapper.vm.tableData).toEqual(tableData);
                    done();
                });
            }
        );

        it(
            `should do $axios request and set tableData correctly
            when attach is true and result of $axios request is not empty`,
            (done) => {
                moxios.stubRequest('executed_orders', {
                    status: 200,
                    response: tableData,
                });

                const localVue = mockVue();
                const wrapper = shallowMount(TradeTradeHistory, {
                    localVue,
                    propsData: propsForTestCorrectlyRenders,
                    attachTo: document.body,
                });
                wrapper.vm.updateTableData(true);

                moxios.wait(() => {
                    expect(wrapper.vm.tableData).toEqual(tableData);
                    done();
                });
            }
        );

        it(
            `should do $axios request and set tableData correctly
            when attach is true and result of $axios request is empty`,
            (done) => {
                moxios.stubRequest('executed_orders', {
                    status: 200,
                    response: [],
                });

                const localVue = mockVue();
                const wrapper = shallowMount(TradeTradeHistory, {
                    localVue,
                    propsData: propsForTestCorrectlyRenders,
                });

                wrapper.vm.updateTableData(true);

                moxios.wait(() => {
                    expect(wrapper.vm.tableData).toEqual([]);
                    done();
                });
            }
        );
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

        moxios.stubRequest('executed_orders', {
            status: 200,
            response: tableData,
        });

        moxios.wait(() => {
            expect(wrapper.findComponent('.card').html()).toContain('mintmeTest');
            done();
        });
    });

    it('should render crypto icon correctly', () => {
        const localVue = mockVue();
        propsForTestCorrectlyRenders.market.base.symbol = 'BTC';
        const wrapper = shallowMount(TradeTradeHistory, {
            localVue,
            propsData: propsForTestCorrectlyRenders,
        });

        moxios.stubRequest('executed_orders', {
            status: 200,
            response: [],
        });

        moxios.wait(() => {
            expect(wrapper.findComponent('#base-avatar').attributes('src'))
                .toContain(propsForTestCorrectlyRenders.market.base.symbol);
            done();
        });
    });

    it('should render token icon correctly', () => {
        const localVue = mockVue();
        propsForTestCorrectlyRenders.market.quote.cryptoSymbol = 'BTC';
        const wrapper = shallowMount(TradeTradeHistory, {
            localVue,
            propsData: propsForTestCorrectlyRenders,
        });

        moxios.stubRequest('executed_orders', {
            status: 200,
            response: [],
        });

        moxios.wait(() => {
            expect(wrapper.findComponent('#quote-avatar').attributes('src'))
                .toContain(propsForTestCorrectlyRenders.market.quote.quote.image.avatar_small);
            done();
        });
    });

    it('should return order row class correctly', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(TradeTradeHistory, {
            localVue,
            propsData: propsForTestCorrectlyRenders,
        });

        expect(wrapper.vm.orderRowClass('Buy')).toBe('justify-content-end flex-row-reverse');
        expect(wrapper.vm.orderRowClass('Sell')).toBe('justify-content-start flex-row');
    });
});
