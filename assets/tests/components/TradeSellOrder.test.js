import {shallowMount, createLocalVue} from '@vue/test-utils';
import TradeSellOrder from '../../js/components/trade/TradeSellOrder';
import moxios from 'moxios';
import Vuex from 'vuex';
import tradeBalance from '../../js/storage/modules/trade_balance';
import orders from '../../js/storage/modules/orders';
import {AddPhoneAlertMixin, DepositModalMixin} from '../../js/mixins';
import axios from 'axios';

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.use(Vuex);
    localVue.use({
        install(Vue, options) {
            Vue.prototype.$routing = {generate: (val) => val};
            Vue.prototype.$axios = {retry: axios, single: axios};
            Vue.prototype.$t = (val) => val;
            Vue.prototype.$logger = {error: (val) => {}};
        },
    });
    return localVue;
}

const localVue = mockVue();
const store = new Vuex.Store({
    modules: {
        tradeBalance,
        orders,
        websocket: {
            namespaced: true,
            actions: {
                addMessageHandler: () => {},
            },
        },
        user: {
            namespaced: true,
            getters: {
                getId: () => 1,
            },
        },
        tokenInfo: {
            namespaced: true,
            getters: {getDeploymentStatus: () => true},
        },
        crypto: {
            namespaced: true,
            getters: {
                getCryptosMap: () => {
                    return {
                        'BTC': {},
                        'WEB': {},
                        'ETH': {},
                    };
                },
            },
        },
    },
});

/**
 * @param {number} balance
 * @return {Wrapper<Vue>}
 */
function mockVm(balance = 1) {
    const $routing = {generate: () => 'URL'};

    return shallowMount(TradeSellOrder, {
        store,
        localVue,
        mixins: [
            AddPhoneAlertMixin,
            DepositModalMixin,
        ],
        mocks: {
            $routing,
            $toasted: {show: () => {}},
        },
        directives: {
            'b-tooltip': {},
        },
        computed: {
            immutableBalance: () => balance,
        },
        propsData: {
            loginUrl: 'loginUrl',
            signupUrl: 'signupUrl',
            loggedIn: true,
            balanceLoaded: true,
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
            marketPrice: 2,
            isOwner: false,
            websocketUrl: '',
            tradeDisabled: false,
            disabledServicesConfig: `{
                "depositDisabled": false,
                "tokenDepositsDisabled": false,
                "allServicesDisabled": false
            }`,
            sellOrders: [],
        },
    });
}

describe('TradeSellOrder', () => {
    let wrapper;

    beforeEach(() => {
        moxios.install();

        wrapper = mockVm();
    });
    afterEach(() => {
        moxios.uninstall();
    });

    it('hide sell order contents and show loading instead', async (done) => {
        await wrapper.setProps({balanceLoaded: false});
        expect(wrapper.findComponent('font-awesome-icon-stub').exists()).toBe(true);
        expect(wrapper.findComponent('div.card-body > div.row').exists()).toBe(false);

        await wrapper.setProps({balanceLoaded: true});
        expect(wrapper.findComponent('font-awesome-icon-stub').exists()).toBe(false);
        expect(wrapper.findComponent('div.card-body > div > div').exists()).toBe(true);

        done();
    });

    it('show login & logout buttons if not logged in', async (done) => {
        await wrapper.setProps({
            loggedIn: false,
        });

        expect(wrapper.findComponent('button[id="sell-login-url"]').exists()).toBe(true);
        expect(wrapper.findComponent('button[id="sell-signup-url"]').exists()).toBe(true);

        await wrapper.setProps({loggedIn: true});
        expect(wrapper.findComponent('button[id="sell-login-url"]').exists()).toBe(false);
        expect(wrapper.findComponent('button[id="sell-signup-url"]').exists()).toBe(false);
        expect(wrapper.vm.loggedIn).toBe(true);

        done();
    });

    it('can make order if price and amount not null', (done) => {
        moxios.stubRequest('token_place_order', {
            status: 200,
            response: {result: 1},
        });

        wrapper.vm.placeOrder();
        wrapper.vm.sellPrice = 2;
        wrapper.vm.sellAmount = 2;
        wrapper.vm.placeOrder();

        moxios.wait(() => {
            done();
        });
    });

    it('should show phone verify modal if user is not totally authenticated', (done) => {
        moxios.stubRequest('token_place_order', {
            status: 200,
            response: {
                error: true,
                type: 'make_orders',
            },
        });

        wrapper.vm.placeOrder();

        moxios.wait(() => {
            wrapper.vm.$emit('making-order-prevented');
            expect(wrapper.emitted()['making-order-prevented']).toBeTruthy();
            done();
        });
    });

    describe('useMarketPrice', () => {
        it('should be disabled if marketPrice not greater than zero', () => {
            expect(wrapper.vm.disabledMarketPrice).toBe(true);
            store.commit('orders/setBuyOrders', [{price: 2, amount: 1}]);
            expect(wrapper.vm.disabledMarketPrice).toBe(false);

            store.commit('orders/setBuyOrders', []);
        });

        it('should be unchecked if it is disabled', async () => {
            await wrapper.setProps({marketPrice: 2});
            wrapper.vm.useMarketPrice = true;
            await wrapper.setProps({marketPrice: 0});

            expect(wrapper.vm.useMarketPrice).toBe(false);
        });
    });

    it('should reset order price and amount properly', async () => {
        wrapper.vm.sellPrice = 3;
        wrapper.vm.sellAmount = 1;
        wrapper.vm.resetOrder();
        expect(wrapper.vm.sellPrice).toBe('');
        expect(wrapper.vm.sellAmount).toBe('');

        store.commit('orders/setBuyOrders', [{price: 1, amount: 1}]);
        wrapper.vm.sellAmount = 2;
        wrapper.vm.resetOrder();
        await wrapper.vm.$nextTick();

        expect(wrapper.vm.sellPrice).toBe(1);
        expect(wrapper.vm.sellAmount).toBe('');

        store.commit('orders/setBuyOrders', []);
    });

    describe('balanceClicked', () => {
        const event = {
            target: {
                tagName: 'span',
            },
        };

        it('should add all the balance to the amount input', async () => {
            wrapper = mockVm(5);
            store.commit('orders/setBuyOrders', [{price: 7, amount: 1}]);
            wrapper.vm.balanceClicked(event);
            await wrapper.vm.$nextTick();

            expect(wrapper.vm.sellAmount).toBe('');
            expect(wrapper.vm.sellPrice).toBe(7);

            store.commit('orders/setBuyOrders', []);
        });

        it('shouldn\'t add price if the price edited manually', async () => {
            wrapper = mockVm(5);
            store.commit('orders/setBuyOrders', [{price: 7, amount: 1}]);
            await wrapper.vm.$nextTick();
            await wrapper.setData({sellPrice: 2, priceManuallyEdited: true});
            wrapper.vm.balanceClicked(event);
            await wrapper.vm.$nextTick();

            expect(wrapper.vm.sellAmount).toBe('');
            expect(wrapper.vm.sellPrice).toBe(2);

            store.commit('orders/setBuyOrders', []);
        });

        it('should change price if the price edited manually but has 0 value', async () => {
            wrapper = mockVm(5);
            store.commit('orders/setBuyOrders', [{price: 7, amount: 1}]);
            wrapper.vm.sellPrice = '000';
            wrapper.vm.priceManuallyEdited = true;
            wrapper.vm.balanceClicked(event);
            await wrapper.vm.$nextTick();

            expect(wrapper.vm.sellAmount).toBe('');
            expect(wrapper.vm.sellPrice).toBe(7);

            store.commit('orders/setBuyOrders', []);
        });

        it('should add price if the price edited manually but has null value', async () => {
            wrapper = mockVm(5);
            store.commit('orders/setBuyOrders', [{price: 7, amount: 1}]);
            wrapper.vm.sellPrice = null;

            wrapper.vm.priceManuallyEdited = true;
            wrapper.vm.balanceClicked(event);
            await wrapper.vm.$nextTick();

            expect(wrapper.vm.sellAmount).toBe('');
            expect(wrapper.vm.sellPrice).toBe(7);

            store.commit('orders/setBuyOrders', []);
        });

        it(
            'Deposit more link click - should not add the balance to the amount input, price/amount not changing',
            async () => {
                wrapper = mockVm(50);
                store.commit('orders/setBuyOrders', [{price: 17, amount: 1}]);
                await wrapper.vm.$nextTick();

                await wrapper.setData({sellAmount: '0', sellPrice: '0'});
                event.target.tagName = 'a';
                wrapper.vm.balanceClicked(event);
                await wrapper.vm.$nextTick();

                expect(wrapper.vm.sellAmount).toBe('0');
                expect(wrapper.vm.sellPrice).toBe('0');

                store.commit('orders/setBuyOrders', []);
            }
        );
    });
});
