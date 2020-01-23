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

    const $url = 'URL';
    const $routing = {generate: () => $url};
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
        wrapper.vm.placeOrder();
        wrapper.vm.sellPrice = 2;
        wrapper.vm.sellAmount = 2;
        wrapper.vm.placeOrder();
        done();
    });

    describe('Deposit more link', function() {
        it('show deposit more link if logged in', () => {
            wrapper.vm.loggedIn = false;
            expect(wrapper.vm.showDepositMoreLink).to.be.false;

            wrapper.vm.loggedIn = true;
            expect(wrapper.vm.showDepositMoreLink).to.be.true;
        });

        it('check order input class for not logged/logged in', () => {
            wrapper.vm.loggedIn = false;
            expect(wrapper.vm.orderInputClass).to.deep.equal('w-100');

            wrapper.vm.loggedIn = true;
            expect(wrapper.vm.orderInputClass).to.deep.equal('w-50');
        });

        it('depositMoreLink', () => {
            wrapper.vm.action = 'buy';
            expect(wrapper.vm.depositMoreLink).to.deep.equal($url);

            wrapper.vm.action = 'exchange';
            expect(wrapper.vm.depositMoreLink).to.deep.equal(undefined);

            wrapper.vm.action = 'sell';
            expect(wrapper.vm.depositMoreLink).to.deep.equal($url);
        });

        it('marketIdentifier', () => {
            wrapper.vm.action = 'buy';
            expect(wrapper.vm.marketIdentifier).to.deep.equal(wrapper.vm.market.base.identifier);

            wrapper.vm.action = 'exchange';
            expect(wrapper.vm.marketIdentifier).to.deep.equal('');

            wrapper.vm.action = 'sell';
            expect(wrapper.vm.marketIdentifier).to.deep.equal(wrapper.vm.market.quote.identifier);
        });

        it('isMarketBTCOrWEB', () => {
            wrapper.vm.action = 'buy';
            expect(wrapper.vm.isMarketBTCOrWEB).to.be.true;

            wrapper.vm.action = 'exchange';
            expect(wrapper.vm.isMarketBTCOrWEB).to.be.false;

            wrapper.vm.action = 'sell';
            expect(wrapper.vm.isMarketBTCOrWEB).to.be.true;
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
        let event = {
            target: {
                tagName: 'span',
            },
        };

        it('should add all the balance to the amount input', () => {
            wrapper.vm.immutableBalance = 5;
            wrapper.vm.marketPrice = 7;
            wrapper.vm.balanceClicked(event);

            expect(wrapper.vm.sellAmount).to.deep.equal('5');
            expect(wrapper.vm.sellPrice).to.deep.equal('7');
        });

        it('shouldn\'t add price if the price edited manually', () => {
            wrapper.vm.immutableBalance = 5;
            wrapper.vm.marketPrice = 7;
            wrapper.vm.sellPrice = 2;
            wrapper.vm.balanceManuallyEdited = true;
            wrapper.vm.balanceClicked(event);

            expect(wrapper.vm.sellAmount).to.deep.equal('5');
            expect(wrapper.vm.sellPrice).to.deep.equal(2);
        });

        it('should add price if the price edited manually but has 0 value', () => {
            wrapper.vm.immutableBalance = 5;
            wrapper.vm.marketPrice = 7;
            wrapper.vm.sellPrice = '000';
            wrapper.vm.balanceManuallyEdited = false;
            wrapper.vm.balanceClicked(event);

            expect(wrapper.vm.sellAmount).to.deep.equal('5');
            expect(wrapper.vm.sellPrice).to.deep.equal('7');
        });

        it('should add price if the price edited manually but has null value', () => {
            wrapper.vm.immutableBalance = 5;
            wrapper.vm.marketPrice = 7;
            wrapper.vm.sellPrice = null;
            wrapper.vm.balanceManuallyEdited = false;
            wrapper.vm.balanceClicked(event);

            expect(wrapper.vm.sellAmount).to.deep.equal('5');
            expect(wrapper.vm.sellPrice).to.deep.equal('7');
        });

        it('Deposit more link click - should not add the balance to the amount input, price/amount not changing', () => {
            wrapper.vm.immutableBalance = 50;
            wrapper.vm.marketPrice = 17;
            wrapper.vm.sellAmount = '0';
            wrapper.vm.sellPrice = '0';
            event.target.tagName = 'a';
            wrapper.vm.balanceClicked(event);

            expect(wrapper.vm.sellAmount).to.deep.equal('0');
            expect(wrapper.vm.sellPrice).to.deep.equal('0');
        });
    });
});
