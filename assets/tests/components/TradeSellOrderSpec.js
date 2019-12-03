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
        modules: {makeOrder},
    });

    const wrapper = shallowMount(TradeSellOrder, {
        store,
        localVue,
        mocks: {
            $routing,
        },
        propsData: {
            loginUrl: 'loginUrl',
            signupUrl: 'signupUrl',
            loggedIn: false,
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
        },
    });

    it('show login & logout buttons if not logged in', () => {
        expect(wrapper.find('a[href="loginUrl"]').exists()).to.deep.equal(true);
        expect(wrapper.find('a[href="signupUrl"]').exists()).to.deep.equal(true);
        wrapper.vm.loggedIn = true;
        expect(wrapper.find('a[href="loginUrl"]').exists()).to.deep.equal(false);
        expect(wrapper.find('a[href="signupUrl"]').exists()).to.deep.equal(false);
    });

    it('can make order if price and amount not null', (done) => {
        moxios.stubRequest(/.*/, {
            status: 200,
            response: {result: 1},
        });
        expect(wrapper.vm.showModal).to.deep.equal(false);
        wrapper.vm.placeOrder();
        expect(wrapper.vm.showModal).to.deep.equal(false);
        wrapper.vm.sellPrice = 2;
        wrapper.vm.sellAmount = 2;
        wrapper.vm.placeOrder();
        moxios.wait(() => {
            expect(wrapper.vm.showModal).to.deep.equal(true);
            done();
        });
    });

    describe('useMarketPrice', () => {
        it('should be disabled if marketPrice not greater than zero', () => {
            wrapper.vm.marketPrice = 0;
            expect(wrapper.vm.disabledMarketPrice).to.be.true;
            wrapper.vm.marketPrice = 2;
            expect(wrapper.vm.disabledMarketPrice).to.be.false;
        });

        it('should be unchecked if it is disabled', () => {
            wrapper.vm.marketPrice = 2;
            wrapper.vm.useMarketPrice = true;
            wrapper.vm.marketPrice = 0;
            expect(wrapper.vm.useMarketPrice).to.be.false;
        });
    });

    describe('balanceClicked', () => {
        it('should add all the balance to the amount input', () => {
            wrapper.vm.immutableBalance = 5;
            wrapper.vm.marketPrice = 7;
            wrapper.vm.balanceClicked();

            expect(wrapper.vm.sellAmount).to.deep.equal('5');
            expect(wrapper.vm.sellPrice).to.deep.equal('7');
        });

        it('shouldn\'t add price if the price edited manually', () => {
            wrapper.vm.immutableBalance = 5;
            wrapper.vm.marketPrice = 7;
            wrapper.vm.sellPrice = 2;
            wrapper.vm.balanceManuallyEdited = true;
            wrapper.vm.balanceClicked();

            expect(wrapper.vm.sellAmount).to.deep.equal('5');
            expect(wrapper.vm.sellPrice).to.deep.equal(2);
        });

        it('should add price if the price edited manually but has 0 value', () => {
            wrapper.vm.immutableBalance = 5;
            wrapper.vm.marketPrice = 7;
            wrapper.vm.sellPrice = '000';
            wrapper.vm.balanceManuallyEdited = false;
            wrapper.vm.balanceClicked();

            expect(wrapper.vm.sellAmount).to.deep.equal('5');
            expect(wrapper.vm.sellPrice).to.deep.equal('7');
        });

        it('should add price if the price edited manually but has null value', () => {
            wrapper.vm.immutableBalance = 5;
            wrapper.vm.marketPrice = 7;
            wrapper.vm.sellPrice = null;
            wrapper.vm.balanceManuallyEdited = false;
            wrapper.vm.balanceClicked();

            expect(wrapper.vm.sellAmount).to.deep.equal('5');
            expect(wrapper.vm.sellPrice).to.deep.equal('7');
        });
    });
});
