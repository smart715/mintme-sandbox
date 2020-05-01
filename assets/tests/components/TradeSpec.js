import {createLocalVue, shallowMount} from '@vue/test-utils';
import Vuex from 'vuex';
import {status} from '../../js/storage/modules/websocket';
import Trade from '../../js/components/trade/Trade';
import moxios from 'moxios';
import axios from 'axios';
import {Constants} from '../../js/utils';

const $routing = {generate: (val, params) => val};

const $store = new Vuex.Store({
    modules: {status},
});

let ordersBuy = [{price: '1'}, {price: '2'}];
let ordersSell = [{price: '3'}, {price: '4'}];
let orders = [{price: '1'}, {price: '2'}];

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
        expect(wrapper.find('trade-chart-stub').attributes('websocketurl')).to.be.equal('testWebsocketUrl');
        expect(wrapper.find('trade-chart-stub').attributes('market')).to.be.equal('[object Object]');
        expect(wrapper.find('trade-chart-stub').attributes('mintmesupplyurl')).to.be.equal('testMintmeSupplyUrl');
        expect(wrapper.find('trade-chart-stub').attributes('minimumvolumeformarketcap')).to.be.equal('11');
        expect(wrapper.find('trade-buy-order-stub').attributes('websocketurl')).to.be.equal('testWebsocketUrl');
        expect(wrapper.find('trade-buy-order-stub').attributes('hash')).to.be.equal('testHash');
        expect(wrapper.find('trade-buy-order-stub').attributes('loggedin')).to.be.equal('true');
        expect(wrapper.find('trade-buy-order-stub').attributes('market')).to.be.equal('[object Object]');
        expect(wrapper.find('trade-buy-order-stub').attributes('loginurl')).to.be.equal('testLoginUrl');
        expect(wrapper.find('trade-buy-order-stub').attributes('signupurl')).to.be.equal('testSignupUrl');
        expect(wrapper.find('trade-sell-order-stub').attributes('websocketurl')).to.be.equal('testWebsocketUrl');
        expect(wrapper.find('trade-sell-order-stub').attributes('hash')).to.be.equal('testHash');
        expect(wrapper.find('trade-sell-order-stub').attributes('loggedin')).to.be.equal('true');
        expect(wrapper.find('trade-sell-order-stub').attributes('market')).to.be.equal('[object Object]');
        expect(wrapper.find('trade-sell-order-stub').attributes('loginurl')).to.be.equal('testLoginUrl');
        expect(wrapper.find('trade-sell-order-stub').attributes('signupurl')).to.be.equal('testSignupUrl');
        expect(wrapper.find('trade-sell-order-stub').attributes('isowner')).to.be.equal('true');
        expect(wrapper.find('trade-orders-stub').attributes('market')).to.be.equal('[object Object]');
        expect(wrapper.find('trade-orders-stub').attributes('userid')).to.be.equal('2');
        expect(wrapper.find('trade-orders-stub').attributes('loggedin')).to.be.equal('true');
        expect(wrapper.find('trade-trade-history-stub').attributes('websocketurl')).to.be.equal('testWebsocketUrl');
        expect(wrapper.find('trade-trade-history-stub').attributes('hash')).to.be.equal('testHash');
        expect(wrapper.find('trade-trade-history-stub').attributes('market')).to.be.equal('[object Object]');
    });

    it('should compute baseBalance correctly', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(Trade, {
            localVue,
            propsData: propsForTestCorrectlyRenders,
        });
        wrapper.vm.balances = false;
        expect(wrapper.vm.baseBalance).to.be.false;
        wrapper.vm.balances = [];
        expect(wrapper.vm.baseBalance).to.be.false;
        wrapper.vm.balances = {'BTC': {available: 10}};
        expect(wrapper.vm.baseBalance).to.be.equal(10);
    });

    it('should compute quoteBalance correctly', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(Trade, {
            localVue,
            propsData: propsForTestCorrectlyRenders,
        });
        wrapper.vm.balances = false;
        expect(wrapper.vm.quoteBalance).to.be.false;
        wrapper.vm.balances = [];
        expect(wrapper.vm.quoteBalance).to.be.false;
        wrapper.vm.balances = {'WEB': {available: 11}};
        expect(wrapper.vm.quoteBalance).to.be.equal(11);
    });

    it('should compute balanceLoaded correctly', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(Trade, {
            localVue,
            propsData: propsForTestCorrectlyRenders,
        });
        wrapper.vm.balances = null;
        expect(wrapper.vm.balanceLoaded).to.be.false;
    });

    it('should compute ordersLoaded correctly', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(Trade, {
            localVue,
            propsData: propsForTestCorrectlyRenders,
        });
        wrapper.vm.buyOrders = false;
        wrapper.vm.sellOrders = null;
        expect(wrapper.vm.ordersLoaded).to.be.false;
        wrapper.vm.buyOrders = null;
        wrapper.vm.sellOrders = false;
        expect(wrapper.vm.ordersLoaded).to.be.false;
        wrapper.vm.buyOrders = false;
        wrapper.vm.sellOrders = false;
        expect(wrapper.vm.ordersLoaded).to.be.true;
    });

    it('should compute marketPriceSell correctly', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(Trade, {
            localVue,
            propsData: propsForTestCorrectlyRenders,
        });
        wrapper.vm.buyOrders = false;
        expect(wrapper.vm.marketPriceSell).to.be.equal(0);
        wrapper.vm.buyOrders = [];
        expect(wrapper.vm.marketPriceSell).to.be.equal(0);
        wrapper.vm.buyOrders = [{price: 10}];
        expect(wrapper.vm.marketPriceSell).to.be.equal(10);
    });

    it('should compute marketPriceBuy correctly', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(Trade, {
            localVue,
            propsData: propsForTestCorrectlyRenders,
        });
        wrapper.vm.sellOrders = false;
        expect(wrapper.vm.marketPriceBuy).to.be.equal(0);
        wrapper.vm.sellOrders = [];
        expect(wrapper.vm.marketPriceBuy).to.be.equal(0);
        wrapper.vm.sellOrders = [{price: 11}];
        expect(wrapper.vm.marketPriceBuy).to.be.equal(11);
    });

    it('should set buyOrders and sellOrders correctly when the function saveOrders() is called', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(Trade, {
            localVue,
            propsData: propsForTestCorrectlyRenders,
        });
        wrapper.vm.sellOrders = null;
        wrapper.vm.saveOrders('foo', true);
        expect(wrapper.vm.sellOrders).to.be.equal('foo');
        wrapper.vm.buyOrders = null;
        wrapper.vm.saveOrders('foo', false);
        expect(wrapper.vm.buyOrders).to.be.equal('foo');
    });

    it('should sort price correctly when the function sortOrders() is called', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(Trade, {
            localVue,
            propsData: propsForTestCorrectlyRenders,
        });
        expect(wrapper.vm.sortOrders(orders, true)).to.deep.equal([{price: '1'}, {price: '2'}]);
        expect(wrapper.vm.sortOrders(orders, false)).to.deep.equal([{price: '2'}, {price: '1'}]);
    });

    describe('updateOrders', () => {
        it('should do $axios request and set buyOrders and sellOrders correctly when context is undefined', (done) => {
            const localVue = mockVue();
            const wrapper = shallowMount(Trade, {
                localVue,
                propsData: propsForTestCorrectlyRenders,
            });
            wrapper.vm.updateOrders(false);

            moxios.stubRequest('pending_orders', {
                status: 200,
                response: {buy: ordersBuy, sell: ordersSell},
            });

            moxios.wait(() => {
                expect(wrapper.vm.buyOrders).to.deep.equal([{price: '2'}, {price: '1'}]);
                expect(wrapper.vm.sellOrders).to.deep.equal([{price: '3'}, {price: '4'}]);
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
                response: {buy: [], sell: []},
            });

            moxios.wait(() => {
                expect(wrapper.vm.buyOrders).to.deep.equal([]);
                expect(wrapper.vm.sellOrders).to.deep.equal([]);
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
                response: {buy: ordersBuy, sell: ordersSell},
            });

            moxios.wait(() => {
                expect(wrapper.vm.sellOrders).to.deep.equal([{price: '3'}, {price: '4'}, {price: '3'}, {price: '4'}]);
                expect(wrapper.vm.sellPage).to.be.equal(3);
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
                response: {buy: ordersBuy, sell: ordersSell},
            });

            moxios.wait(() => {
                expect(wrapper.vm.buyOrders).to.deep.equal([{price: '2'}, {price: '1'}, {price: '2'}, {price: '1'}]);
                expect(wrapper.vm.buyPage).to.be.equal(3);
                done();
            });
        });
    });

    describe('updateAssets', () => {
        it('set balances to false when not logged in', () => {
            const localVue = mockVue();
            const wrapper = shallowMount(Trade, {
                localVue,
                propsData: propsForTestCorrectlyRenders,
            });
            wrapper.vm.balances = 'foo';
            wrapper.vm.loggedIn = false;
            wrapper.vm.updateAssets();
            expect(wrapper.vm.balances).to.be.false;
        });

        it('should do $axios request and set balances correctly when market quote symbol is undefined', (done) => {
            const localVue = mockVue();
            const wrapper = shallowMount(Trade, {
                localVue,
                propsData: propsForTestCorrectlyRenders,
            });
            wrapper.vm.updateAssets();
            wrapper.vm.loggedIn = true;

            moxios.stubRequest('tokens', {
                status: 200,
                response: {common: ['foo'], predefined: ['bar']},
            });

            moxios.wait(() => {
                expect(wrapper.vm.balances['WEB']).to.deep.equal({available: '0'});
                done();
            });
        });

        it('should do $axios request and set balances correctly when market quote symbol is defined', (done) => {
            const localVue = mockVue();
            const wrapper = shallowMount(Trade, {
                localVue,
                propsData: propsForTestCorrectlyRenders,
            });
            wrapper.vm.updateAssets();
            wrapper.vm.loggedIn = true;

            moxios.stubRequest('tokens', {
                status: 200,
                response: {common: {WEB: 'foo'}, predefined: ['bar']},
            });

            moxios.wait(() => {
                expect(wrapper.vm.balances).to.deep.equal({'0': 'bar', 'WEB': 'foo'});
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
            expect(wrapper.vm.sellOrders).to.deep.equal(oldSellOrders);
            const oldBuyOrders = wrapper.vm.buyOrders = [{id: 'qwe'}];
            wrapper.vm.processOrders({side: 'bar', id: 'foobar'}, 'foo');
            expect(wrapper.vm.buyOrders).to.deep.equal(oldBuyOrders);
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
                expect(wrapper.vm.sellOrders).to.deep.equal([{id: 'foo', price: 1}, {id: 'bar', price: 2}]);
                expect(wrapper.vm.buyOrders).to.deep.equal([{id: 'qwe', price: 3}, {id: 'foo', price: 1}]);
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
            wrapper.vm.processOrders({side: Constants.WSAPI.order.type.SELL, id: 'foobar'}, Constants.WSAPI.order.status.UPDATE);
            expect(wrapper.vm.sellOrders).to.deep.equal([{id: 'bar', price: 2}]);
            expect(wrapper.vm.buyOrders).to.be.null;
        });

        it('set sellOrders and buyOrders correctly when when \'type\' type matches UPDATE and order is defined', () => {
            const localVue = mockVue();
            const wrapper = shallowMount(Trade, {
                localVue,
                propsData: propsForTestCorrectlyRenders,
            });
            wrapper.vm.sellOrders = [{id: 'bar', price: 2, ctime: 'nestCtime', left: 'testLeft', mtime: 'testMtime'}];
            wrapper.vm.buyOrders = null;
            wrapper.vm.processOrders({side: Constants.WSAPI.order.type.SELL, id: 'bar', price: 2, ctime: 'nestCtime', left: 'testLeft', mtime: 'testMtime'}, Constants.WSAPI.order.status.UPDATE);
            expect(wrapper.vm.sellOrders).to.deep.equal([{id: 'bar', price: 2, ctime: 'nestCtime', left: 'testLeft', mtime: 'testMtime', createdTimestamp: 'nestCtime', amount: 'testLeft', timestamp: 'testMtime'}]);
            expect(wrapper.vm.buyOrders).to.be.null;
        });

        it('set sellOrders and buyOrders correctly when when \'type\' type matches FINISH and order is undefined', () => {
            const localVue = mockVue();
            const wrapper = shallowMount(Trade, {
                localVue,
                propsData: propsForTestCorrectlyRenders,
            });
            wrapper.vm.sellOrders = [{id: 'bar', price: 2}];
            wrapper.vm.buyOrders = null;
            wrapper.vm.processOrders({side: Constants.WSAPI.order.type.SELL, id: 'foobar'}, Constants.WSAPI.order.status.FINISH);
            expect(wrapper.vm.sellOrders).to.deep.equal([{id: 'bar', price: 2}]);
            expect(wrapper.vm.buyOrders).to.be.null;
        });

        it('set sellOrders and buyOrders correctly when when \'type\' type matches FINISH and order is defined', () => {
            const localVue = mockVue();
            const wrapper = shallowMount(Trade, {
                localVue,
                propsData: propsForTestCorrectlyRenders,
            });
            wrapper.vm.sellOrders = [{id: 'bar', price: 2}];
            wrapper.vm.buyOrders = null;
            wrapper.vm.processOrders({side: Constants.WSAPI.order.type.SELL, id: 'bar'}, Constants.WSAPI.order.status.FINISH);
            expect(wrapper.vm.sellOrders).to.deep.equal([]);
            expect(wrapper.vm.buyOrders).to.be.null;
        });
    });
});
