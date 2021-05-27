import {createLocalVue, shallowMount} from '@vue/test-utils';
import Vuex from 'vuex';
import {status} from '../../js/storage/modules/websocket';
import Trade from '../../js/components/trade/Trade';
import moxios from 'moxios';
import axios from 'axios';
import {Constants} from '../../js/utils';
import tradeBalance from '../../js/storage/modules/trade_balance';

const $routing = {generate: (val, params) => val};

let ordersBuy = [{price: '1'}, {price: '2'}];
let ordersSell = [{price: '3'}, {price: '4'}];

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
            Vue.prototype.$store = new Vuex.Store({
                modules: {
                    status,
                    tradeBalance,
                    websocket: {
                        namespaced: true,
                        actions: {
                            addOnOpenHandler: jest.fn(),
                            addMessageHandler: jest.fn(),
                            init: jest.fn(),
                        },
                        getters: {
                            getClient: jest.fn(),
                        },
                    },
                },
            });
            Vue.prototype.$toasted = {show: () => false};
            Vue.prototype.$t = (val) => val;
        },
    });
    return localVue;
};

let propsForTestCorrectlyRenders = {
        websocketUrl: 'testWebsocketUrl',
        hash: 'testHash',
        loginUrl: 'testLoginUrl',
        signupUrl: 'testSignupUrl',
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
        loggedIn: true,
        tokenName: 'testTokenName',
        isOwner: true,
        userId: 2,
        precision: 0,
        mintmeSupplyUrl: 'testMintmeSupplyUrl',
        minimumVolumeForMarketcap: 11,
        disabledServicesConfig: '{"depositDisabled":false,"withdrawalsDisabled":false,"deployDisabled":false}',
};

describe('Trade', () => {
    beforeEach(() => {
        moxios.install();
    });

    afterEach(() => {
        moxios.uninstall();
    });

    it('renders correctly with assigned props', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(Trade, {
            localVue,
            propsData: propsForTestCorrectlyRenders,
        });
        expect(wrapper.find('trade-chart-stub').attributes('websocketurl')).toBe('testWebsocketUrl');
        expect(wrapper.find('trade-chart-stub').attributes('market')).toBe('[object Object]');
        expect(wrapper.find('trade-chart-stub').attributes('mintmesupplyurl')).toBe('testMintmeSupplyUrl');
        expect(wrapper.find('trade-chart-stub').attributes('minimumvolumeformarketcap')).toBe('11');
        expect(wrapper.find('trade-buy-order-stub').attributes('websocketurl')).toBe('testWebsocketUrl');
        expect(wrapper.find('trade-buy-order-stub').attributes('hash')).toBe('testHash');
        expect(wrapper.find('trade-buy-order-stub').attributes('loggedin')).toBe('true');
        expect(wrapper.find('trade-buy-order-stub').attributes('market')).toBe('[object Object]');
        expect(wrapper.find('trade-buy-order-stub').attributes('loginurl')).toBe('testLoginUrl');
        expect(wrapper.find('trade-buy-order-stub').attributes('signupurl')).toBe('testSignupUrl');
        expect(wrapper.find('trade-sell-order-stub').attributes('websocketurl')).toBe('testWebsocketUrl');
        expect(wrapper.find('trade-sell-order-stub').attributes('hash')).toBe('testHash');
        expect(wrapper.find('trade-sell-order-stub').attributes('loggedin')).toBe('true');
        expect(wrapper.find('trade-sell-order-stub').attributes('market')).toBe('[object Object]');
        expect(wrapper.find('trade-sell-order-stub').attributes('loginurl')).toBe('testLoginUrl');
        expect(wrapper.find('trade-sell-order-stub').attributes('signupurl')).toBe('testSignupUrl');
        expect(wrapper.find('trade-sell-order-stub').attributes('isowner')).toBe('true');
        expect(wrapper.find('trade-orders-stub').attributes('market')).toBe('[object Object]');
        expect(wrapper.find('trade-orders-stub').attributes('userid')).toBe('2');
        expect(wrapper.find('trade-orders-stub').attributes('loggedin')).toBe('true');
        expect(wrapper.find('trade-trade-history-stub').attributes('websocketurl')).toBe('testWebsocketUrl');
        expect(wrapper.find('trade-trade-history-stub').attributes('hash')).toBe('testHash');
        expect(wrapper.find('trade-trade-history-stub').attributes('market')).toBe('[object Object]');
    });

    it('should compute baseBalance correctly', () => {
        const localVue = mockVue();
        let wrapper = shallowMount(Trade, {
            localVue,
            computed: {
                balances: () => false,
            },
            propsData: propsForTestCorrectlyRenders,
        });
        expect(wrapper.vm.baseBalance).toBe(false);

        wrapper = shallowMount(Trade, {
            localVue,
            computed: {
                balances: () => [],
            },
            propsData: propsForTestCorrectlyRenders,
        });
        expect(wrapper.vm.baseBalance).toBe(false);

        wrapper = shallowMount(Trade, {
            localVue,
            computed: {
                balances: () => {
                    return {BTC: {available: '10'}};
                },
            },
            propsData: propsForTestCorrectlyRenders,
        });
        expect(wrapper.vm.baseBalance).toBe('10');
    });

    it('should compute quoteBalance correctly', () => {
        const localVue = mockVue();
        let wrapper = shallowMount(Trade, {
            localVue,
            computed: {
                balances: () => false,
            },
            propsData: propsForTestCorrectlyRenders,
        });
        expect(wrapper.vm.quoteBalance).toBe(false);

        wrapper = shallowMount(Trade, {
            localVue,
            computed: {
                balances: () => [],
            },
            propsData: propsForTestCorrectlyRenders,
        });
        expect(wrapper.vm.quoteBalance).toBe(false);

        wrapper = shallowMount(Trade, {
            localVue,
            computed: {
                balances() {
                    return {WEB: {available: '11'}};
                },
            },
            propsData: propsForTestCorrectlyRenders,
        });
        expect(wrapper.vm.quoteBalance).toBe('11');
    });

    it('should compute balanceLoaded correctly', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(Trade, {
            localVue,
            propsData: propsForTestCorrectlyRenders,
        });
        wrapper.vm.balances = null;
        expect(wrapper.vm.balanceLoaded).toBe(false);
    });

    it('should compute ordersLoaded correctly', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(Trade, {
            localVue,
            propsData: propsForTestCorrectlyRenders,
        });
        wrapper.vm.buyOrders = [];
        wrapper.vm.sellOrders = null;
        expect(wrapper.vm.ordersLoaded).toBe(false);
        wrapper.vm.buyOrders = null;
        wrapper.vm.sellOrders = [];
        expect(wrapper.vm.ordersLoaded).toBe(false);
        wrapper.vm.buyOrders = [];
        wrapper.vm.sellOrders = [];
        expect(wrapper.vm.ordersLoaded).toBe(true);
    });

    it('should compute marketPriceSell correctly', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(Trade, {
            localVue,
            propsData: propsForTestCorrectlyRenders,
        });
        wrapper.vm.buyOrders = null;
        expect(wrapper.vm.marketPriceSell).toBe(0);
        wrapper.vm.buyOrders = [];
        expect(wrapper.vm.marketPriceSell).toBe(0);
        wrapper.vm.buyOrders = [{price: 10}];
        expect(wrapper.vm.marketPriceSell).toBe(10);
    });

    it('should compute marketPriceBuy correctly', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(Trade, {
            localVue,
            propsData: propsForTestCorrectlyRenders,
        });
        wrapper.vm.sellOrders = null;
        expect(wrapper.vm.marketPriceBuy).toBe(0);
        wrapper.vm.sellOrders = [];
        expect(wrapper.vm.marketPriceBuy).toBe(0);
        wrapper.vm.sellOrders = [{price: 11}];
        expect(wrapper.vm.marketPriceBuy).toBe(11);
    });

    it('should set buyOrders and sellOrders correctly when the function saveOrders() is called', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(Trade, {
            localVue,
            propsData: propsForTestCorrectlyRenders,
        });
        wrapper.vm.sellOrders = null;
        wrapper.vm.saveOrders([], true);
        expect(wrapper.vm.sellOrders).toEqual([]);
        wrapper.vm.buyOrders = null;
        wrapper.vm.saveOrders([], false);
        expect(wrapper.vm.buyOrders).toEqual([]);
    });

    describe('updateOrders', () => {
        it('should do $axios request and set buyOrders and sellOrders correctly when context is undefined', (done) => {
            const localVue = mockVue();
            const wrapper = shallowMount(Trade, {
                localVue,
                propsData: propsForTestCorrectlyRenders,
            });

            moxios.stubRequest('pending_orders', {
                status: 200,
                response: {
                    buy: ordersBuy,
                    sell: ordersSell,
                    buyDepth: 0,
                    totalSellOrders: '1.0001',
                    totalBuyOrders: '1.0001',
                },
            });

            wrapper.vm.updateOrders(false);

            moxios.wait(() => {
                expect(wrapper.vm.buyOrders).toMatchObject([{price: '1'}, {price: '2'}]);
                expect(wrapper.vm.sellOrders).toMatchObject([{price: '3'}, {price: '4'}]);
                expect(wrapper.vm.totalSellOrders).toMatchObject({d: [1, 1000]});
                expect(wrapper.vm.totalBuyOrders).toMatchObject({d: [1, 1000]});
                done();
            });
        });

        it('should do $axios request and don\'t modify buyOrders and sellOrders when "buy" and "sell" is empty', (done) => {
            const localVue = mockVue();
            const wrapper = shallowMount(Trade, {
                localVue,
                propsData: propsForTestCorrectlyRenders,
            });
            let context = {type: 'sell', isAssigned: true, resolve() {}};
            wrapper.vm.updateOrders(context);

            moxios.stubRequest('pending_orders', {
                status: 200,
                response: {
                    buy: [],
                    sell: [],
                    buyDepth: 0,
                    totalSellOrders: 0,
                    totalBuyOrders: 0,
                },
            });

            moxios.wait(() => {
                expect(wrapper.vm.buyOrders).toEqual([]);
                expect(wrapper.vm.sellOrders).toEqual([]);
                done();
            });
        });

        it('should do $axios request and set sellOrders and sellPage correctly when context is defined', (done) => {
            const localVue = mockVue();
            const wrapper = shallowMount(Trade, {
                localVue,
                propsData: propsForTestCorrectlyRenders,
            });
            let context = {type: 'sell', isAssigned: true, resolve: ()=>{}};
            wrapper.vm.sellOrders = [];
            wrapper.vm.updateOrders(context);

            moxios.stubRequest('pending_orders', {
                status: 200,
                response: {
                    buy: ordersBuy,
                    sell: ordersSell,
                    buyDepth: 0,
                    totalSellOrders: '1.0001',
                    totalBuyOrders: '1.0001',
                },
            });

            moxios.wait(() => {
                expect(wrapper.vm.sellOrders).toMatchObject([{price: '3'}, {price: '4'}, {price: '3'}, {price: '4'}]);
                expect(wrapper.vm.sellPage).toBe(3);
                expect(wrapper.vm.totalSellOrders).toMatchObject({d: [1, 1000]});
                expect(wrapper.vm.totalBuyOrders).toMatchObject({d: [1, 1000]});
                done();
            });
        });

        it('should do $axios request and set buyOrders and buyPage correctly when context is defined', (done) => {
            const localVue = mockVue();
            const wrapper = shallowMount(Trade, {
                localVue,
                propsData: propsForTestCorrectlyRenders,
            });
            let context = {type: 'buy', isAssigned: true, resolve: ()=>{}};
            wrapper.vm.buyOrders = [];
            wrapper.vm.updateOrders(context);

            moxios.stubRequest('pending_orders', {
                status: 200,
                response: {
                    buy: ordersBuy,
                    sell: ordersSell,
                    buyDepth: 0,
                    totalSellOrders: '1.0001',
                    totalBuyOrders: '1.0001',
                },
            });

            moxios.wait(() => {
                expect(wrapper.vm.buyOrders).toMatchObject([{price: '1'}, {price: '2'}, {price: '1'}, {price: '2'}]);
                expect(wrapper.vm.buyPage).toBe(3);
                expect(wrapper.vm.totalSellOrders).toMatchObject({d: [1, 1000]});
                expect(wrapper.vm.totalBuyOrders).toMatchObject({d: [1, 1000]});
                done();
            });
        });
    });

    describe('processOrders', () => {
        it('to do default action when \'type\' does not meet any conditions', () => {
            const localVue = mockVue();
            const wrapper = shallowMount(Trade, {
                localVue,
                propsData: propsForTestCorrectlyRenders,
            });
            const oldSellOrders = wrapper.vm.sellOrders = [{id: 'bar'}];
            wrapper.vm.processOrders({side: Constants.WSAPI.order.type.SELL, id: 'foobar'}, 'foo');
            expect(wrapper.vm.sellOrders).toMatchObject(oldSellOrders);
            const oldBuyOrders = wrapper.vm.buyOrders = [{id: 'qwe'}];
            wrapper.vm.processOrders({side: 'bar', id: 'foobar'}, 'foo');
            expect(wrapper.vm.buyOrders).toMatchObject(oldBuyOrders);
        });

        it('should do $axios request and set sellOrders and buyOrders correctly when when \'type\' type matches PUT', (done) => {
            const localVue = mockVue();
            const wrapper = shallowMount(Trade, {
                localVue,
                propsData: propsForTestCorrectlyRenders,
            });
            wrapper.vm.sellOrders = [{id: 'bar', price: 2}];
            wrapper.vm.processOrders({side: Constants.WSAPI.order.type.SELL, id: 'foobar'}, Constants.WSAPI.order.status.PUT);
            wrapper.vm.buyOrders = [{id: 'qwe', price: 3}];
            wrapper.vm.processOrders({side: 'bar', id: 'foobar'}, Constants.WSAPI.order.status.PUT);

            moxios.stubRequest('pending_order_details', {
                status: 200,
                response: {id: 'foo', price: 1},
            });

            moxios.wait(() => {
                expect(wrapper.vm.sellOrders).toMatchObject([{id: 'bar', price: 2}, {id: 'foo', price: 1}]);
                expect(wrapper.vm.buyOrders).toMatchObject([{id: 'qwe', price: 3}, {id: 'foo', price: 1}]);
                done();
            });
        });

        it('set sellOrders and buyOrders correctly when when \'type\' type matches UPDATE and order is undefined', () => {
            const localVue = mockVue();
            const wrapper = shallowMount(Trade, {
                localVue,
                propsData: propsForTestCorrectlyRenders,
            });
            wrapper.vm.sellOrders = [{id: 'bar', price: 2}];
            wrapper.vm.buyOrders = null;
            wrapper.vm.totalSellOrders = {sub: () => {
                return wrapper.vm.totalSellOrders;
            }, add: () => {
                return wrapper.vm.totalSellOrders;
            }};
            wrapper.vm.totalBuyOrders = {sub: () => {
                 return wrapper.vm.totalBuyOrders;
            }, add: () => {
                 return wrapper.vm.totalBuyOrders;
            }};
            wrapper.vm.processOrders({side: Constants.WSAPI.order.type.SELL, id: 'foobar'}, Constants.WSAPI.order.status.UPDATE);
            expect(wrapper.vm.sellOrders).toMatchObject([{id: 'bar', price: 2}]);
            expect(wrapper.vm.buyOrders).toBe(null);
        });

        it('set sellOrders and buyOrders correctly when when \'type\' type matches UPDATE and order is defined', () => {
            const localVue = mockVue();
            const wrapper = shallowMount(Trade, {
                localVue,
                propsData: propsForTestCorrectlyRenders,
            });
            wrapper.vm.sellOrders = [{id: 'bar', price: 2, ctime: 'nestCtime', left: 'testLeft', mtime: 'testMtime'}];
            wrapper.vm.buyOrders = null;
            wrapper.vm.totalSellOrders = {sub: () => {
                return wrapper.vm.totalSellOrders;
            }, add: () => {
                return wrapper.vm.totalSellOrders;
            }};
            wrapper.vm.totalBuyOrders = {sub: () => {
                return wrapper.vm.totalBuyOrders;
            }, add: () => {
                 return wrapper.vm.totalBuyOrders;
            }};
            wrapper.vm.processOrders({side: Constants.WSAPI.order.type.SELL, id: 'bar', price: 2, ctime: 'nestCtime', left: 'testLeft', mtime: 'testMtime'}, Constants.WSAPI.order.status.UPDATE);
            expect(wrapper.vm.sellOrders).toMatchObject([{id: 'bar', price: 2, ctime: 'nestCtime', left: 'testLeft', mtime: 'testMtime', createdTimestamp: 'nestCtime', amount: 'testLeft', timestamp: 'testMtime'}]);
            expect(wrapper.vm.buyOrders).toBe(null);
        });

        it('set sellOrders and buyOrders correctly when when \'type\' type matches FINISH and order is undefined', () => {
            const localVue = mockVue();
            const wrapper = shallowMount(Trade, {
                localVue,
                propsData: propsForTestCorrectlyRenders,
            });
            wrapper.vm.sellOrders = [{id: 'bar', price: 2}];
            wrapper.vm.buyOrders = null;
            wrapper.vm.totalSellOrders = {sub: () => {
                return wrapper.vm.totalSellOrders;
            }, add: () => {
                return wrapper.vm.totalSellOrders;
            }};
            wrapper.vm.totalBuyOrders = {sub: () => {
                return wrapper.vm.totalBuyOrders;
            }, add: () => {
             return wrapper.vm.totalBuyOrders;
            }};
            expect(wrapper.vm.sellOrders).toMatchObject([{id: 'bar', price: 2}]);
            expect(wrapper.vm.buyOrders).toBe(null);
        });

        it('set sellOrders and buyOrders correctly when when \'type\' type matches FINISH and order is defined', () => {
            const localVue = mockVue();
            const wrapper = shallowMount(Trade, {
                localVue,
                propsData: propsForTestCorrectlyRenders,
            });
            wrapper.vm.sellOrders = [{id: 'bar', price: 2}];
            wrapper.vm.buyOrders = null;
            wrapper.vm.totalSellOrders = {sub: () => {
                return wrapper.vm.totalSellOrders;
            }, add: () => {
                return wrapper.vm.totalSellOrders;
            }};
            wrapper.vm.totalBuyOrders = {sub: () => {
                return wrapper.vm.totalBuyOrders;
            }, add: () => {
                return wrapper.vm.totalBuyOrders;
            }};
            wrapper.vm.processOrders({side: Constants.WSAPI.order.type.SELL, id: 'bar'}, Constants.WSAPI.order.status.FINISH);
            expect(wrapper.vm.sellOrders).toEqual([]);
            expect(wrapper.vm.buyOrders).toBe(null);
        });
    });
});
