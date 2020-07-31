import {shallowMount, createLocalVue} from '@vue/test-utils';
import TradeSellOrder from '../../js/components/trade/TradeSellOrder';
import Axios from '../../js/axios';
import moxios from 'moxios';
import Vuex from 'vuex';
import makeOrder from '../../js/storage/modules/make_order';

describe('TradeSellOrder', () => {
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
    const store = new Vuex.Store({
        modules: {
            makeOrder,
            websocket: {
                namespaced: true,
                actions: {
                    addMessageHandler: () => {},
                },
            },
        },
    });

    const wrapper = shallowMount(TradeSellOrder, {
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

    it('hide sell order  contents and show loading instead', () => {
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
        wrapper.vm.sellPrice = 2;
        wrapper.vm.sellAmount = 2;
        wrapper.vm.placeOrder();
        done();
    });

    describe('useMarketPrice', () => {
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
        wrapper.vm.sellPrice = 3;
        wrapper.vm.sellAmount = 1;
        wrapper.vm.useMarketPrice = false;
        wrapper.vm.resetOrder();
        expect(wrapper.vm.sellPrice).toBe(0);
        expect(wrapper.vm.sellAmount).toBe(0);

        wrapper.setProps({marketPrice: 1});
        wrapper.vm.sellAmount = 2;
        wrapper.vm.useMarketPrice = true;
        wrapper.vm.resetOrder();
        expect(wrapper.vm.sellPrice).toBe('1');
        expect(wrapper.vm.sellAmount).toBe(0);
    });

    it('should update market price properly', () => {
        wrapper.vm.sellPrice = 1.5;
        wrapper.vm.useMarketPrice = false;
        wrapper.vm.updateMarketPrice();
        expect(wrapper.vm.sellPrice).toBe(0);

        wrapper.setProps({marketPrice: '7.0'});
        wrapper.vm.useMarketPrice = true;
        wrapper.vm.updateMarketPrice();
        expect(wrapper.vm.sellPrice).toBe('7');

        wrapper.setProps({marketPrice: 0});
        wrapper.vm.useMarketPrice = true;
        wrapper.setProps({disabledMarketPrice: true});
        wrapper.vm.updateMarketPrice();
        expect(wrapper.vm.sellPrice).toBe(0);
        expect(wrapper.vm.useMarketPrice).toBe(false);
    });

    describe('balanceClicked', () => {
        let event = {
            target: {
                tagName: 'span',
            },
        };

        it('should add all the balance to the amount input', () => {
            wrapper.vm.immutableBalance = 5;
            wrapper.setProps({marketPrice: 7});
            wrapper.vm.balanceClicked(event);

            expect(wrapper.vm.sellAmount).toBe('5');
            expect(wrapper.vm.sellPrice).toBe('7');
        });

        it('shouldn\'t add price if the price edited manually', () => {
            wrapper.vm.immutableBalance = 5;
            wrapper.setProps({marketPrice: 7});
            wrapper.vm.sellPrice = 2;
            wrapper.vm.balanceManuallyEdited = true;
            wrapper.vm.balanceClicked(event);

            expect(wrapper.vm.sellAmount).toBe('5');
            expect(wrapper.vm.sellPrice).toBe(2);
        });

        it('should add price if the price edited manually but has 0 value', () => {
            wrapper.vm.immutableBalance = 5;
            wrapper.setProps({marketPrice: 7});
            wrapper.vm.sellPrice = '000';
            wrapper.vm.balanceManuallyEdited = false;
            wrapper.vm.balanceClicked(event);

            expect(wrapper.vm.sellAmount).toBe('5');
            expect(wrapper.vm.sellPrice).toBe('7');
        });

        it('should add price if the price edited manually but has null value', () => {
            wrapper.vm.immutableBalance = 5;
            wrapper.setProps({marketPrice: 7});
            wrapper.vm.sellPrice = null;
            wrapper.vm.balanceManuallyEdited = false;
            wrapper.vm.balanceClicked(event);

            expect(wrapper.vm.sellAmount).toBe('5');
            expect(wrapper.vm.sellPrice).toBe('7');
        });

        it('Deposit more link click - should not add the balance to the amount input, price/amount not changing', () => {
            wrapper.vm.immutableBalance = 50;
            wrapper.setProps({marketPrice: 17});
            wrapper.vm.sellAmount = '0';
            wrapper.vm.sellPrice = '0';
            event.target.tagName = 'a';
            wrapper.vm.balanceClicked(event);

            expect(wrapper.vm.sellAmount).toBe('0');
            expect(wrapper.vm.sellPrice).toBe('0');
        });
    });
});
