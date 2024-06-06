import {createLocalVue, shallowMount} from '@vue/test-utils';
import Vuex from 'vuex';
import {status} from '../../js/storage/modules/websocket';
import Trade from '../../js/components/trade/Trade';
import moxios from 'moxios';
import axios from 'axios';
import {Constants} from '../../js/utils';
import tradeBalance from '../../js/storage/modules/trade_balance';
import market from '../../js/storage/modules/market';
import rates from '../../js/storage/modules/rates';
import minOrder from '../../js/storage/modules/min_order';
import orders from '../../js/storage/modules/orders';
import TradeBuyOrder from '../../js/components/trade/TradeBuyOrder';
import TradeSellOrder from '../../js/components/trade/TradeSellOrder';
import AddPhoneAlertModal from '../../js/components/modal/AddPhoneAlertModal';

const $routing = {generate: (val, params) => val};
const $logger = {error: (val, params) => val, success: (val, params) => val};

const ordersBuy = [{price: '1'}, {price: '2'}];
const ordersSell = [{price: '3'}, {price: '4'}];

const baseQuoteMarket = {
    base: {
        name: 'Betcoin',
        symbol: 'BTC',
        subunit: 8,
        identifier: 'BTC',
    },
    identifier: 'WEBBTC',
    quote: {
        name: 'Webchain',
        symbol: 'WEB',
        subunit: 4,
        identifier: 'WEB',
    },
};


/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();

    market.state.currentMarketIndex = 'WEB';
    market.state.markets = {'WEB': baseQuoteMarket};

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
                    market,
                    minOrder,
                    rates,
                    orders,
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
            Vue.prototype.$logger = $logger;
        },
    });
    return localVue;
}

const propsForTestCorrectlyRenders = {
    websocketUrl: 'testWebsocketUrl',
    hash: 'testHash',
    loginUrl: 'testLoginUrl',
    signupUrl: 'testSignupUrl',
    market: baseQuoteMarket,
    markets: {
        'WEB': baseQuoteMarket,
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
            directives: {
                'b-tooltip': {},
            },
        });
        expect(wrapper.findComponent('trade-chart-stub').attributes('websocketurl')).toBe('testWebsocketUrl');
        expect(wrapper.findComponent('trade-chart-stub').attributes('market')).toBe('[object Object]');
        expect(wrapper.findComponent('trade-chart-stub').attributes('mintmesupplyurl')).toBe('testMintmeSupplyUrl');
        expect(wrapper.findComponent('trade-chart-stub').attributes('minimumvolumeformarketcap')).toBe('11');
        expect(wrapper.findComponent('trade-buy-order-stub').attributes('websocketurl')).toBe('testWebsocketUrl');
        expect(wrapper.findComponent('trade-buy-order-stub').attributes('hash')).toBe('testHash');
        expect(wrapper.findComponent('trade-buy-order-stub').attributes('loggedin')).toBe('true');
        expect(wrapper.findComponent('trade-buy-order-stub').attributes('market')).toBe('[object Object]');
        expect(wrapper.findComponent('trade-buy-order-stub').attributes('loginurl')).toBe('testLoginUrl');
        expect(wrapper.findComponent('trade-buy-order-stub').attributes('signupurl')).toBe('testSignupUrl');
        expect(wrapper.findComponent('trade-sell-order-stub').attributes('websocketurl')).toBe('testWebsocketUrl');
        expect(wrapper.findComponent('trade-sell-order-stub').attributes('hash')).toBe('testHash');
        expect(wrapper.findComponent('trade-sell-order-stub').attributes('loggedin')).toBe('true');
        expect(wrapper.findComponent('trade-sell-order-stub').attributes('market')).toBe('[object Object]');
        expect(wrapper.findComponent('trade-sell-order-stub').attributes('loginurl')).toBe('testLoginUrl');
        expect(wrapper.findComponent('trade-sell-order-stub').attributes('signupurl')).toBe('testSignupUrl');
        expect(wrapper.findComponent('trade-sell-order-stub').attributes('isowner')).toBe('true');
        expect(wrapper.findComponent('trade-orders-stub').attributes('market')).toBe('[object Object]');
        expect(wrapper.findComponent('trade-orders-stub').attributes('userid')).toBe('2');
        expect(wrapper.findComponent('trade-orders-stub').attributes('loggedin')).toBe('true');
        expect(wrapper.findComponent('trade-trade-history-stub').attributes('websocketurl')).toBe('testWebsocketUrl');
        expect(wrapper.findComponent('trade-trade-history-stub').attributes('hash')).toBe('testHash');
        expect(wrapper.findComponent('trade-trade-history-stub').attributes('market')).toBe('[object Object]');
    });

    it('should compute baseBalance correctly', () => {
        const localVue = mockVue();
        let wrapper = shallowMount(Trade, {
            localVue,
            computed: {
                balances: () => false,
            },
            propsData: propsForTestCorrectlyRenders,
            directives: {
                'b-tooltip': {},
            },
        });
        expect(wrapper.vm.baseBalance).toBe(false);

        wrapper = shallowMount(Trade, {
            localVue,
            computed: {
                balances: () => [],
            },
            propsData: propsForTestCorrectlyRenders,
            directives: {
                'b-tooltip': {},
            },
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
            directives: {
                'b-tooltip': {},
            },
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
            directives: {
                'b-tooltip': {},
            },
        });
        expect(wrapper.vm.quoteBalance).toBe(false);

        wrapper = shallowMount(Trade, {
            localVue,
            computed: {
                balances: () => [],
            },
            propsData: propsForTestCorrectlyRenders,
            directives: {
                'b-tooltip': {},
            },
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
            directives: {
                'b-tooltip': {},
            },
        });
        expect(wrapper.vm.quoteBalance).toBe('11');
    });

    it('should compute balanceLoaded correctly', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(Trade, {
            localVue,
            propsData: propsForTestCorrectlyRenders,
            computed: {
                balances: () => null,
            },
            directives: {
                'b-tooltip': {},
            },
        });

        expect(wrapper.vm.balanceLoaded).toBe(false);
    });

    it('should compute ordersLoaded correctly', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(Trade, {
            localVue,
            propsData: propsForTestCorrectlyRenders,
            directives: {
                'b-tooltip': {},
            },
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
            directives: {
                'b-tooltip': {},
            },
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
            directives: {
                'b-tooltip': {},
            },
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
            directives: {
                'b-tooltip': {},
            },
        });
        wrapper.vm.sellOrders = null;
        wrapper.vm.saveOrders([], true);
        expect(wrapper.vm.sellOrders).toEqual([]);
        wrapper.vm.buyOrders = null;
        wrapper.vm.saveOrders([], false);
        expect(wrapper.vm.buyOrders).toEqual([]);
    });

    describe('updateOrders', () => {
        it(
            'should do $axios request and set buyOrders and sellOrders correctly when context is undefined',
            (done) => {
                const localVue = mockVue();
                const wrapper = shallowMount(Trade, {
                    localVue,
                    propsData: propsForTestCorrectlyRenders,
                    directives: {
                        'b-tooltip': {},
                    },
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
            }
        );

        it(
            `should do $axios request and don't modify buyOrders and sellOrders when "buy" and "sell" is empty`,
            (done) => {
                const localVue = mockVue();
                const wrapper = shallowMount(Trade, {
                    localVue,
                    propsData: propsForTestCorrectlyRenders,
                    directives: {
                        'b-tooltip': {},
                    },
                });
                const context = {type: 'sell', isAssigned: true, resolve() {}};

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

                wrapper.vm.updateOrders(context);

                moxios.wait(() => {
                    expect(wrapper.vm.buyOrders).toEqual(null);
                    expect(wrapper.vm.sellOrders).toEqual(null);
                    done();
                });
            }
        );

        it('should do $axios request and set sellOrders and sellPage correctly when context is defined', (done) => {
            const localVue = mockVue();
            const wrapper = shallowMount(Trade, {
                localVue,
                propsData: propsForTestCorrectlyRenders,
                directives: {
                    'b-tooltip': {},
                },
            });
            const context = {type: 'sell', isAssigned: true, resolve: ()=>{}};
            wrapper.vm.sellOrders = [];

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

            wrapper.vm.updateOrders(context);

            moxios.wait(() => {
                expect(wrapper.vm.sellOrders).toMatchObject([{price: '3'}, {price: '4'}]);
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
                directives: {
                    'b-tooltip': {},
                },
            });
            const context = {type: 'buy', isAssigned: true, resolve: ()=>{}};
            wrapper.vm.buyOrders = [];

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

            wrapper.vm.updateOrders(context);

            moxios.wait(() => {
                expect(wrapper.vm.buyOrders).toMatchObject([{price: '1'}, {price: '2'}]);
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
                directives: {
                    'b-tooltip': {},
                },
            });
            const oldSellOrders = wrapper.vm.sellOrders = [{id: 'bar'}];
            wrapper.vm.processOrders({side: Constants.WSAPI.order.type.SELL, id: 'foobar'}, 'foo');
            expect(wrapper.vm.sellOrders).toMatchObject(oldSellOrders);
            const oldBuyOrders = wrapper.vm.buyOrders = [{id: 'qwe'}];
            wrapper.vm.processOrders({side: 'bar', id: 'foobar'}, 'foo');
            expect(wrapper.vm.buyOrders).toMatchObject(oldBuyOrders);
        });

        it(
            `should do $axios request and set sellOrders and buyOrders correctly when when 'type' type matches PUT`,
            (done) => {
                const localVue = mockVue();
                const wrapper = shallowMount(Trade, {
                    localVue,
                    propsData: propsForTestCorrectlyRenders,
                    directives: {
                        'b-tooltip': {},
                    },
                });

                moxios.stubRequest('pending_order_details', {
                    status: 200,
                    response: {id: 'foo', price: 1},
                });

                wrapper.vm.sellOrders = [{id: 'bar', price: 2}];
                wrapper.vm.processOrders(
                    {side: Constants.WSAPI.order.type.SELL, id: 'foobar'},
                    Constants.WSAPI.order.status.PUT
                );
                wrapper.vm.buyOrders = [{id: 'qwe', price: 3}];

                wrapper.vm.addNewOrderDebounce.flush();

                moxios.wait(() => {
                    expect(wrapper.vm.sellOrders).toMatchObject([{id: 'bar', price: 2}, {id: 'foo', price: 1}]);
                    done();
                });

                wrapper.vm.processOrders({side: 'bar', id: 'foobar'}, Constants.WSAPI.order.status.PUT);
                wrapper.vm.addNewOrderDebounce.flush();

                moxios.wait(() => {
                    expect(wrapper.vm.buyOrders).toMatchObject([{id: 'qwe', price: 3}, {id: 'foo', price: 1}]);
                    done();
                });
            }
        );

        it(
            `set sellOrders and buyOrders correctly when when 'type' type matches UPDATE and order is undefined`,
            () => {
                const localVue = mockVue();
                const wrapper = shallowMount(Trade, {
                    localVue,
                    propsData: propsForTestCorrectlyRenders,
                    directives: {
                        'b-tooltip': {},
                    },
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
                wrapper.vm.processOrders(
                    {side: Constants.WSAPI.order.type.SELL, id: 'foobar'},
                    Constants.WSAPI.order.status.UPDATE
                );
                expect(wrapper.vm.sellOrders).toMatchObject([{id: 'bar', price: 2}]);
                expect(wrapper.vm.buyOrders).toBe(null);
            }
        );

        it('set sellOrders and buyOrders correctly when when \'type\' type matches UPDATE and order is defined', () => {
            const localVue = mockVue();
            const wrapper = shallowMount(Trade, {
                localVue,
                propsData: propsForTestCorrectlyRenders,
                directives: {
                    'b-tooltip': {},
                },
            });
            wrapper.vm.sellOrders = [{id: 'bar', price: 2, ctime: 'nestCtime', amount: 5, mtime: 'testMtime'}];
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
            wrapper.vm.processOrders({
                id: 'bar',
                side: Constants.WSAPI.order.type.SELL,
                price: 2,
                ctime: 'nestCtime',
                left: 3,
                mtime: 'testMtime',
            },
            Constants.WSAPI.order.status.UPDATE
            );

            expect(wrapper.vm.sellOrders).toMatchObject([{
                id: 'bar',
                price: 2,
                ctime: 'nestCtime',
                mtime: 'testMtime',
                createdTimestamp: 'nestCtime',
                amount: 3,
                timestamp: 'testMtime',
            }]);

            expect(wrapper.vm.buyOrders).toBe(null);
        });

        it(
            `set sellOrders and buyOrders correctly when when 'type' type matches FINISH and order is undefined`,
            () => {
                const localVue = mockVue();
                const wrapper = shallowMount(Trade, {
                    localVue,
                    propsData: propsForTestCorrectlyRenders,
                    directives: {
                        'b-tooltip': {},
                    },
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
            }
        );

        it('set sellOrders and buyOrders correctly when when \'type\' type matches FINISH and order is defined', () => {
            const localVue = mockVue();
            const wrapper = shallowMount(Trade, {
                localVue,
                propsData: propsForTestCorrectlyRenders,
                directives: {
                    'b-tooltip': {},
                },
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
            wrapper.vm.processOrders(
                {side: Constants.WSAPI.order.type.SELL, id: 'bar'},
                Constants.WSAPI.order.status.FINISH
            );
            expect(wrapper.vm.sellOrders).toEqual([]);
            expect(wrapper.vm.buyOrders).toBe(null);
        });

        it('should set prevented action name on prevent', () => {
            const localVue = mockVue();
            const wrapper = shallowMount(Trade, {
                localVue,
                propsData: propsForTestCorrectlyRenders,
                directives: {
                    'b-tooltip': {},
                },
            });

            wrapper.findComponent(TradeBuyOrder).vm.$emit('making-order-prevented');
            expect(wrapper.vm.preventedActionRefName).toEqual('tradeBuyOrder');

            wrapper.findComponent(TradeSellOrder).vm.$emit('making-order-prevented');
            expect(wrapper.vm.preventedActionRefName).toEqual('tradeSellOrder');
        });

        it('should set prevented action name on prevent', () => {
            const placeOrderStub = jest.fn();
            const localVue = mockVue();
            const wrapper = shallowMount(Trade, {
                localVue,
                propsData: propsForTestCorrectlyRenders,
                stubs: {
                    'TradeSellOrder': {
                        methods: {
                            placeOrder: placeOrderStub,
                        },
                        render: () => {},
                    },
                },
            });

            wrapper.vm.preventedActionRefName = 'tradeSellOrder';
            wrapper.findComponent(AddPhoneAlertModal).vm.$emit('phone-verified');

            expect(placeOrderStub).toBeCalled();
        });
    });
});
