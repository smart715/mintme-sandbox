import {shallowMount, createLocalVue} from '@vue/test-utils';
import TradeOrders from '../../js/components/trade/TradeOrders';
import moxios from 'moxios';
import axios from 'axios';
import Vuex from 'vuex';
import Decimal from 'decimal.js';

const localVue = mockVue();

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.use(Vuex);
    localVue.use({
        install(Vue, options) {
            Vue.prototype.$axios = {retry: axios, single: axios};
            Vue.prototype.$routing = {generate: (val) => val};
            Vue.prototype.$t = (val) => val;
            Vue.prototype.$toasted = {show: () => {}};
            Vue.prototype.$logger = {error: (val) => {}};
        },
    });

    return localVue;
}

/**
 * @param {Object} props
 * @return {Object}
 */
function createTradeOrders(props = {}) {
    return {
        isToken: true,
        ordersLoaded: true,
        ordersUpdated: true,
        buyOrders: [],
        sellOrders: [],
        totalSellOrders: [],
        totalBuyOrders: [],
        market: fakeMarket,
        userId: 1,
        loggedIn: true,
        ...props,
    };
}

/**
 * @param {Object} mutations
 * @return {Vuex.Store}
 */
function createSharedTestStore(mutations) {
    return new Vuex.Store({
        modules: {
            orders: {
                mutations,
                namespaced: true,
                getters: {
                    getBuyOrders: () => {},
                    getSellOrders: () => {},
                },
            },
        },
    });
}

const fakeMarket = {
    id: 1,
    base: {
        name: 'Webchain',
        symbol: 'WEB',
        subunit: 4,
        tradable: true,
        exchangeble: true,
        isToken: false,
        identifier: 'WEB',
        image: {
            url: '/media/default_profile.png',
        },
    },
    quote: {
        name: 'TEST',
        telegramUrl: null,
        discordUrl: null,
        coverImage: null,
        createdOnMintmeSite: true,
        decimals: 12,
        holdersCount: 1,
        symbol: 'TEST',
        cryptoSymbol: 'WEB',
        deploymentStatus: 'deployed',
        blocked: false,
        quiet: false,
        ownerId: undefined,
        identifier: 'TOK000000000005',
        subunit: 4,
        networks: ['WEB'],
    },
    identifier: 'TOK000000000005WEB',
};

const fakeSellBuyOrder =[{
    maker: {
        id: 5,
        profile: {
            nickname: 'test05',
            firstName: '',
            lastName: '',
            city: '',
            country: '',
            description: '',
            anonymous: false,
            tokens: [{
                name: 'TEST05',
                telegramUrl: null,
                discordUrl: null,
                coverImage: null,
                createdOnMintmeSite: true,
                decimals: 12,
                holdersCount: 1,
                symbol: 'TEST05',
                cryptoSymbol: 'WEB',
                deploymentStatus: 'not-deployed',
                blocked: false,
                quiet: false,
                ownerId: 5,
                identifier: 'TOK000000000007',
                subunit: 4,
            }],
            image: {
                url: '/media/default_profile.png',
                avatar_small: 'https://localhost/media/cache/resolve/avatar_small/media/default_profile.png',
                avatar_middle: 'https://localhost/media/cache/resolve/avatar_middle/media/default_profile.png',
                avatar_large: 'https://localhost/media/cache/resolve/avatar_large/media/default_profile.png',
            },
        },
    },
    taker: null,
    status: 'pending',
    id: 3,
    timestamp: 1668438700,
    createdTimestamp: 1668438700,
    side: 1,
    amount: '0.002000000000',
    price: '100.000000000000000000',
    fee: '0.002000000000',
    market: {
        base: {
            name: 'Webchain',
            symbol: 'WEB',
            subunit: 4,
            tradable: true,
            exchangeble: true,
            isToken: false,
            image: {
                url: '/media/default_mintme.svg',
                avatar_small: 'https://localhost/media/cache/resolve/avatar_small/media/default_mintme.svg',
                avatar_middle: 'https://localhost/media/cache/resolve/avatar_middle/media/default_mintme.svg',
                avatar_large: 'https://localhost/media/cache/resolve/avatar_large/media/default_mintme.svg',
            },
            identifier: 'WEB',
        },
        quote: {
            name: 'TEST05',
            telegramUrl: null,
            discordUrl: null,
            image: {
                url: '/media/token_avatars/T.png',
                avatar_small: 'https://localhost/media/cache/resolve/avatar_small/media/token_avatars/T.png',
                avatar_middle: 'https://localhost/media/cache/resolve/avatar_middle/media/token_avatars/T.png',
                avatar_large: 'https://localhost/media/cache/resolve/avatar_large/media/token_avatars/T.png',
            },
            coverImage: null,
            createdOnMintmeSite: true,
            decimals: 12,
            holdersCount: 1,
            symbol: 'TEST05',
            cryptoSymbol: 'WEB',
            deploymentStatus: 'not-deployed',
            blocked: false,
            quiet: false,
            ownerId: 5,
            identifier: 'TOK000000000007',
            subunit: 4,
        },
        identifier: 'TOK000000000007WEB',
    },
}];

const fakeOrderList = [
    {
        id: 1,
        price: '0.00000001',
        amount: '1000',
        sum: '0.00000001',
        maker: {
            id: 1,
            username: 'test',
            avatar: 'test',
        },
        createdTimestamp: 1,
        owner: true,
        orderId: 1,
        ownerId: 1,
        highlightClass: '',
        traderAvatar: 'test',
    },
];

const fakeExpectOrderList = [
    {
        amount: new Decimal('1000.00000000'),
        createdTimestamp: 1,
        highlightClass: '',
        id: 1,
        maker: {
            avatar: 'test',
            id: 1,
            username: 'test',
        },
        orderId: 1,
        owner: true,
        ownerId: 1,
        price: '0.00000001',
        sum: '0.00000001',
        traderAvatar: 'test',
    },
];

describe('TradeOrders', () => {
    let wrapper;
    let mutations;
    let store;

    beforeEach(() => {
        mutations = {
            setSellOrders: jest.fn(),
            setBuyOrders: jest.fn(),
        };

        store = createSharedTestStore(mutations);

        wrapper = shallowMount(TradeOrders, {
            localVue,
            sync: false,
            store: store,
            propsData: createTradeOrders(),
            directives: {
                'b-tooltip': {},
            },
        });

        moxios.install();
    });

    afterEach(() => {
        moxios.uninstall();
    });

    it('should return sell', async () => {
        await wrapper.setData({isSellSide: true});

        expect(wrapper.vm.typeOrder).toEqual('sell');
    });

    it('should return buy', async () => {
        await wrapper.setData({isSellSide: false});

        expect(wrapper.vm.typeOrder).toEqual('buy');
    });

    it('should return symbol', () => {
        expect(wrapper.vm.getSymbol(wrapper.vm.market.quote)).toEqual('TEST');
    });

    it('should return cryptoSymbol', async () => {
        await wrapper.setData(wrapper.vm.market.quote.ownerId = 1);

        expect(wrapper.vm.getSymbol(wrapper.vm.market.quote)).toEqual('WEB');
    });

    it('getTooltipConfig should return null', async () => {
        await wrapper.setData({rebrandingFunc: () => 'test', maxLengthToTruncate: 10});

        expect(wrapper.vm.getTooltipConfig('test')).toBeNull();
    });

    it('getTooltipConfig should return object', async () => {
        await wrapper.setData({rebrandingFunc: () => 'test', maxLengthToTruncate: 1});

        expect(wrapper.vm.getTooltipConfig('test')).toEqual({
            title: 'test',
            boundary: 'window',
            customClass: 'tooltip-custom',
        });
    });

    it('should call updateBuyOrders', () => {
        const spyUpdateOrders = jest.spyOn(wrapper.vm, 'updateOrders');

        wrapper.vm.updateBuyOrders(true);

        expect(spyUpdateOrders).toHaveBeenCalled();
    });

    it('should call updateSellOrders', () => {
        const spyUpdateOrders = jest.spyOn(wrapper.vm, 'updateOrders');

        wrapper.vm.updateSellOrders(true);

        expect(spyUpdateOrders).toHaveBeenCalled();
    });

    it('should call updateOrders', () => {
        const spyUpdateOrders = jest.spyOn(wrapper.vm, 'updateOrders');

        wrapper.vm.updateOrders(true, 'sell');

        expect(spyUpdateOrders).toHaveBeenCalled();
    });

    it('should return ordersList', async () => {
        await wrapper.setData({ordersList: [fakeOrderList]});

        expect(wrapper.vm.ordersList).toEqual([fakeOrderList]);
    });

    it('sould return a groupByPrice', () => {
        expect(wrapper.vm.groupByPrice(fakeOrderList)).toStrictEqual(fakeExpectOrderList);
    });

    it('should call removeOrderModal', async () => {
        const spyRemoveOrderModal = jest.spyOn(wrapper.vm, 'removeOrderModal');

        await wrapper.setData({isSellSide: true});

        wrapper.vm.removeOrderModal(fakeMarket);

        expect(spyRemoveOrderModal).toHaveBeenCalled();
    });

    it('should call removeOrderModal if isSellSide is false', async () => {
        const spyRemoveOrderModal = jest.spyOn(wrapper.vm, 'removeOrderModal');

        await wrapper.setData({isSellSide: false});
        await wrapper.setProps({buyOrders: fakeSellBuyOrder});

        wrapper.vm.removeOrderModal(fakeSellBuyOrder[0]);

        expect(spyRemoveOrderModal).toHaveBeenCalled();
    });

    it('should call removeOrder', async () => {
        const spyRemoveOrder = jest.spyOn(wrapper.vm, 'removeOrder');

        await wrapper.setData({removeOrders: fakeSellBuyOrder});
        wrapper.vm.removeOrder();

        expect(spyRemoveOrder).toHaveBeenCalled();
    });

    it('should call removeOrder when axios call orders_cancel', async (done) => {
        const spyRemoveOrder = jest.spyOn(wrapper.vm, 'removeOrder');

        await wrapper.setData({removeOrders: fakeSellBuyOrder});

        moxios.stubRequest('orders_сancel', {
            status: 200,
            response: {
                data: {
                    message: 'test',
                },
            },
        });

        wrapper.vm.removeOrder();

        moxios.wait(() => {
            expect(spyRemoveOrder).toHaveBeenCalled();
            done();
        });
    });

    it('should call removeOrder with error response when axios call orders_cancel', async (done) => {
        await wrapper.setData({removeOrders: fakeSellBuyOrder});
        const notifyErrorSpy = jest.spyOn(wrapper.vm, 'notifyError');

        moxios.stubRequest('orders_сancel', {
            status: 400,
            response: {
                data: {
                    error: 'test',
                },
            },
        });

        wrapper.vm.removeOrder();

        moxios.wait(() => {
            expect(notifyErrorSpy).toHaveBeenCalled();
            done();
        });
    });

    it('should call notifyError in removeOrder', async (done) => {
        const spyNotifyError = jest.spyOn(wrapper.vm, 'notifyError');

        await wrapper.setData({removeOrders: fakeSellBuyOrder});

        moxios.stubRequest('orders_сancel', {
            status: 400,
            response: {
                data: {
                    error: 'test',
                },
            },
        });

        wrapper.vm.removeOrder();

        moxios.wait(() => {
            expect(spyNotifyError).toHaveBeenCalled();
            done();
        });
    });

    it('should call setSellOrders', async () => {
        await wrapper.setData({isSellSide: true});

        await wrapper.setProps({sellOrders: fakeSellBuyOrder});

        expect(mutations.setSellOrders).toHaveBeenCalled();
    });
});
