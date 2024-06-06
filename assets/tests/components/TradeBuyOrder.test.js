import {shallowMount, createLocalVue} from '@vue/test-utils';
import TradeBuyOrder from '../../js/components/trade/TradeBuyOrder';
import moxios from 'moxios';
import Vuex from 'vuex';
import tradeBalance from '../../js/storage/modules/trade_balance';
import orders from '../../js/storage/modules/orders';
import {DepositModalMixin} from '../../js/mixins';
import axios from 'axios';

describe('TradeBuyOrder', () => {
    beforeEach(() => {
        moxios.install();
    });
    afterEach(() => {
        moxios.uninstall();
    });

    const $routing = {generate: () => 'URL'};
    const localVue = createLocalVue();
    localVue.use(Vuex);
    localVue.use({
        install(Vue, options) {
            Vue.prototype.$t = (val) => val;
            Vue.prototype.$axios = {retry: axios, single: axios};
            Vue.prototype.$logger = {error: (val) => {}};
        },
    });
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

    const wrapper = shallowMount(TradeBuyOrder, {
        store,
        localVue,
        mixins: [DepositModalMixin],
        mocks: {
            $routing,
            $toasted: {show: () => {}},
        },
        propsData: {
            loginUrl: 'loginUrl',
            signupUrl: 'signupUrl',
            loggedIn: false,
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
            buyOrders: [],
        },
    });
    it('hide buy order  contents and show loading instead', async () => {
        await wrapper.setProps({balanceLoaded: false});
        expect(wrapper.findComponent('font-awesome-icon-stub').exists()).toBe(true);
        expect(wrapper.findComponent('div.card-body > div.row').exists()).toBe(false);

        await wrapper.setProps({balanceLoaded: true});
        expect(wrapper.findComponent('font-awesome-icon-stub').exists()).toBe(false);
        expect(wrapper.findComponent('div.card-body > div > div').exists()).toBe(true);
    });


    it('show login & logout buttons if not logged in', async () => {
        expect(wrapper.findComponent('button[id="buy-login-url"]').exists()).toBe(true);
        expect(wrapper.findComponent('button[id="buy-signup-url"]').exists()).toBe(true);

        await wrapper.setProps({loggedIn: true});
        expect(wrapper.findComponent('button[id="buy-login-url"]').exists()).toBe(false);
        expect(wrapper.findComponent('button[id="buy-signup-url"]').exists()).toBe(false);
    });

    it('can make order if price and amount not null', (done) => {
        moxios.stubRequest(/.*/, {
            status: 200,
            response: {result: 1},
        });
        wrapper.vm.placeOrder();
        wrapper.vm.buyPrice = 2;
        wrapper.vm.buyAmount = 2;
        wrapper.vm.placeOrder();
        done();
    });

    it('should show phone verify modal if user is not totally authenticated', () => {
        moxios.stubRequest('token_place_order', {
            status: 200,
            response: {
                error: true,
                type: 'trading',
            },
        });

        wrapper.vm.placeOrder();

        moxios.wait(() => {
            wrapper.vm.$emit('making-order-prevented');
            expect(wrapper.emitted().making-order-prevented).toBeTruthy();
            done();
        });
    });

    it('should reset order price and amount properly', () => {
        wrapper.vm.buyPrice = 2;
        wrapper.vm.buyAmount = 1;
        wrapper.vm.useMarketPrice = false;
        wrapper.vm.resetOrder();
        expect(wrapper.vm.buyPrice).toBe('');
        expect(wrapper.vm.buyAmount).toBe('');

        wrapper.vm.buyPrice = 2;
        wrapper.vm.buyAmount = 2;
        wrapper.vm.useMarketPrice = true;
        wrapper.vm.resetOrder();
        expect(wrapper.vm.buyPrice).toBe('');
        expect(wrapper.vm.buyAmount).toBe('');
    });

    describe('balanceClicked', () => {
        const event = {
            target: {
                tagName: 'span',
            },
        };

        it('should add the correct amount to match the full balance', () => {
            wrapper.vm.immutableBalance = 10;
            store.commit('orders/setSellOrders', [{price: 5, amount: 2}]);
            wrapper.vm.balanceClicked(event);

            expect(wrapper.vm.buyAmount).toBe('2');
            expect(wrapper.vm.buyPrice).toBe('5');

            store.commit('orders/setSellOrders', []);
        });

        it('shouldn\'t change price if the price edited manually', () => {
            wrapper.vm.immutableBalance = 10;
            store.commit('orders/setSellOrders', [{price: 6, amount: 2}]);
            wrapper.vm.buyPrice = 2;
            wrapper.vm.priceManuallyEdited = true;
            wrapper.vm.balanceClicked(event);

            expect(wrapper.vm.buyAmount).toBe('5');
            expect(wrapper.vm.buyPrice).toBe(2);

            store.commit('orders/setSellOrders', []);
        });

        it('should change price if the price edited manually but has 0 value', () => {
            wrapper.vm.immutableBalance = 10;
            store.commit('orders/setSellOrders', [{price: 5, amount: 2}]);
            wrapper.vm.buyPrice = '00';
            wrapper.vm.priceManuallyEdited = true;
            wrapper.vm.balanceClicked(event);

            expect(wrapper.vm.buyAmount).toBe('2');
            expect(wrapper.vm.buyPrice).toBe('5');

            store.commit('orders/setSellOrders', []);
        });

        it('should change price if the price edited manually but has null value', () => {
            wrapper.vm.immutableBalance = 10;
            store.commit('orders/setSellOrders', [{price: 5, amount: 2}]);
            wrapper.vm.buyPrice = '';
            wrapper.vm.priceManuallyEdited = true;
            wrapper.vm.balanceClicked(event);

            expect(wrapper.vm.buyAmount).toBe('2');
            expect(wrapper.vm.buyPrice).toBe('5');

            store.commit('orders/setSellOrders', []);
        });

        it(
            'Deposit more link click - should not add the balance to the amount input, price/amount not changing',
            () => {
                wrapper.vm.immutableBalance = 100;
                store.commit('orders/setSellOrders', [{price: 7, amount: 2}]);
                wrapper.vm.buyAmount = '0';
                wrapper.vm.buyPrice = '0';
                event.target.tagName = 'a';
                wrapper.vm.balanceClicked(event);

                expect(wrapper.vm.buyAmount).toBe('0');
                expect(wrapper.vm.buyPrice).toBe('0');

                store.commit('orders/setSellOrders', []);
            }
        );
    });

    describe('parsFloatInput', () => {
        it('change price if input is . character', () => {
            store.commit('tradeBalance/setBuyPriceInput', '.');
            wrapper.vm.buyAmount = 1;
            wrapper.vm.useMarketPrice = false;
            expect(wrapper.vm.buyPrice).toBe('0.');
        });

        const dataProvider = ['2', '2.', '2.0', '2.2', '.2'];

        dataProvider.forEach((item) => {
            it('change price if input is . character', () => {
                store.commit('tradeBalance/setBuyPriceInput', item);
                wrapper.vm.buyAmount = 1;
                wrapper.vm.useMarketPrice = false;
                expect(wrapper.vm.buyPrice).toBe(item);
            });
        });
    });
});
