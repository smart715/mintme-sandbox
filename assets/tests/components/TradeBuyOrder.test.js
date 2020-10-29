import {shallowMount, createLocalVue} from '@vue/test-utils';
import TradeBuyOrder from '../../js/components/trade/TradeBuyOrder';
import Axios from '../../js/axios';
import moxios from 'moxios';
import Vuex from 'vuex';
import tradeBalance from '../../js/storage/modules/trade_balance';

describe('TradeBuyOrder', () => {
    beforeEach(() => {
       moxios.install();
    });
    afterEach(() => {
        moxios.uninstall();
    });

    const $routing = {generate: () => 'URL'};
    const localVue = createLocalVue();
    localVue.use(Axios);
    localVue.use(Vuex);
    localVue.use({
        install(Vue, options) {
            Vue.prototype.$t = (val) => val;
        },
    });
    const store = new Vuex.Store({
        modules: {
            tradeBalance,
            websocket: {
                namespaced: true,
                actions: {
                    addMessageHandler: () => {},
                },
            },
        },
    });

    const wrapper = shallowMount(TradeBuyOrder, {
        store,
        localVue,
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
                },
                quote: {
                    name: 'Webchain',
                    symbol: 'WEB',
                    subunit: 4,
                    identifier: 'WEB',
                },
            },
            marketPrice: 2,
            isOwner: false,
            websocketUrl: '',
        },
    });
    it('hide buy order  contents and show loading instead', () => {
        wrapper.setProps({balanceLoaded: false});
        expect(wrapper.find('font-awesome-icon').exists()).toBe(true);
        expect(wrapper.find('div.card-body > div.row').exists()).toBe(false);
        wrapper.setProps({balanceLoaded: true});
        expect(wrapper.find('font-awesome-icon').exists()).toBe(false);
        expect(wrapper.find('div.card-body > div.row').exists()).toBe(true);
    });


    it('show login & logout buttons if not logged in', () => {
        expect(wrapper.find('a[href="loginUrl"]').exists()).toBe(true);
        expect(wrapper.find('a[href="signupUrl"]').exists()).toBe(true);
        wrapper.setProps({loggedIn: true});
        expect(wrapper.find('a[href="loginUrl"]').exists()).toBe(false);
        expect(wrapper.find('a[href="signupUrl"]').exists()).toBe(false);
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

    describe('useMarketPrice', function() {
        it('should be disabled if marketPrice not greater than zero', () => {
            wrapper.setProps({marketPrice: 0});
            expect(wrapper.vm.disabledMarketPrice).toBe(true);
            wrapper.setProps({marketPrice: 2});
            expect(wrapper.vm.disabledMarketPrice).toBe(false);
        });

        it('should be unchecked if it is disabled', () => {
            wrapper.setProps({marketPrice: 2});
            wrapper.vm.useMarketPrice = true;
            wrapper.setProps({marketPrice: 0});
            expect(wrapper.vm.useMarketPrice).toBe(false);
        });
    });

    it('should reset order price and amount properly', () => {
        wrapper.vm.buyPrice = 2;
        wrapper.vm.buyAmount = 1;
        wrapper.vm.useMarketPrice = false;
        wrapper.vm.resetOrder();
        expect(wrapper.vm.buyPrice).toBe(0);
        expect(wrapper.vm.buyAmount).toBe(0);

        wrapper.vm.buyPrice = 2;
        wrapper.vm.buyAmount = 2;
        wrapper.vm.useMarketPrice = true;
        wrapper.vm.resetOrder();
        expect(wrapper.vm.buyPrice).toBe(0);
        expect(wrapper.vm.buyAmount).toBe(0);
    });

    it('should update market price properly', () => {
        wrapper.vm.buyPrice = 2;
        wrapper.vm.useMarketPrice = false;
        wrapper.vm.updateMarketPrice();
        expect(wrapper.vm.buyPrice).toBe(0);

        wrapper.setProps({marketPrice: 5});
        wrapper.vm.useMarketPrice = true;
        wrapper.vm.updateMarketPrice();
        expect(wrapper.vm.buyPrice).toBe('5');

        wrapper.setProps({marketPrice: 0});
        wrapper.setProps({disabledMarketPrice: true});
        wrapper.vm.updateMarketPrice();
        expect(wrapper.vm.buyPrice).toBe(0);
        expect(wrapper.vm.useMarketPrice).toBe(false);
    });

    describe('balanceClicked', () => {
        let event = {
            target: {
                tagName: 'span',
            },
        };

        it('should add the correct amount to match the full balance', () => {
            wrapper.vm.immutableBalance = 10;
            wrapper.setProps({marketPrice: 5});
            wrapper.vm.balanceClicked(event);

            expect(wrapper.vm.buyAmount).toBe('2');
            expect(wrapper.vm.buyPrice).toBe('5');
        });

        it('shouldn\'t add price if the price edited manually', () => {
            wrapper.vm.immutableBalance = 10;
            wrapper.setProps({marketPrice: 5});
            wrapper.vm.buyPrice = 2;
            wrapper.vm.balanceManuallyEdited = true;
            wrapper.vm.balanceClicked(event);

            expect(wrapper.vm.buyAmount).toBe('5');
            expect(wrapper.vm.buyPrice).toBe(2);
        });

        it('should add price if the price edited manually but has 0 value', () => {
            wrapper.vm.immutableBalance = 10;
            wrapper.setProps({marketPrice: 5});
            wrapper.vm.buyPrice = '00';
            wrapper.vm.balanceManuallyEdited = true;
            wrapper.vm.balanceClicked(event);

            expect(wrapper.vm.buyAmount).toBe('2');
            expect(wrapper.vm.buyPrice).toBe('5');
        });

        it('should add price if the price edited manually but has null value', () => {
            wrapper.vm.immutableBalance = 10;
            wrapper.setProps({marketPrice: 5});
            wrapper.vm.buyPrice = null;
            wrapper.vm.balanceManuallyEdited = true;
            wrapper.vm.balanceClicked(event);

            expect(wrapper.vm.buyAmount).toBe('2');
            expect(wrapper.vm.buyPrice).toBe('5');
        });

        it('Deposit more link click - should not add the balance to the amount input, price/amount not changing', () => {
            wrapper.vm.immutableBalance = 100;
            wrapper.setProps({marketPrice: 7});
            wrapper.vm.buyAmount = '0';
            wrapper.vm.buyPrice = '0';
            event.target.tagName = 'a';
            wrapper.vm.balanceClicked(event);

            expect(wrapper.vm.buyAmount).toBe('0');
            expect(wrapper.vm.buyPrice).toBe('0');
        });
    });
});
