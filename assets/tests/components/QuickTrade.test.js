import {createLocalVue, shallowMount} from '@vue/test-utils';
import '../vueI18nfix.js';
import QuickTrade from '../../js/components/QuickTrade';
import moxios from 'moxios';
import axios from 'axios';
import Vuex from 'vuex';
import {webSymbol, btcSymbol, tokSymbol, MINTME, ethSymbol} from '../../js/utils/constants';

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.use({
        install(Vue, options) {
            Vue.prototype.$sanitize = (val) => val;
            Vue.prototype.$axios = {retry: axios, single: axios};
            Vue.prototype.$routing = {generate: (val) => val};
            Vue.prototype.$toasted = {show: () => {}};
            Vue.prototype.$t = (val) => val;
        },
    });

    return localVue;
}

const localVue = mockVue();
localVue.use(Vuex);

const store = new Vuex.Store({
    modules: {
        websocket: {
            namespaced: true,
            actions: {
                addOnOpenHandler: () => {},
                addMessageHandler: () => {},
            },
        },
        tradeBalance: {
            namespaced: true,
            getters: {
                getBalances: function() {
                    return {
                        BTC: {available: '10'},
                        ETH: {available: '10'},
                        WEB: {available: '10'},
                        USDC: {available: '10'},
                        foo: {available: '10'},
                        bar: {available: '10'},
                    };
                },
            },
        },
    },
});

/**
 * @param {Object} props
 * @return {Wrapper}
 */
function mockQuickTrade(props = {}) {
    return shallowMount(QuickTrade, {
        store,
        localVue,
        propsData: {
            loggedIn: true,
            isToken: true,
            websocketUrl: '',
            params: {
                donation_fee: .01,
            },
            market: {
                base: {
                    name: 'bar',
                    symbol: 'bar',
                },
                quote: {
                    name: 'foo',
                    symbol: 'foo',
                },
                identifier: 'bar',
            },
            disabledServicesConfig: `{
                "depositDisabled": false,
                "withdrawalsDisabled": false,
                "deployDisabled": false
            }`,
            ...props,
        },
    });
}

describe('QuickTrade', () => {
    beforeEach(() => {
        moxios.install();
    });

    afterEach(() => {
        moxios.uninstall();
    });

    it('should render correctly for logged in user', () => {
        const wrapper = mockQuickTrade();
        expect(wrapper.vm.dropdownText).toBe(MINTME.symbol);
        expect(wrapper.vm.isCurrencySelected).toBe(true);
        expect(wrapper.vm.buttonDisabled).toBe(true);
        expect(wrapper.vm.isAmountValid).toBe(false);
        expect(wrapper.find('.all-button').exists()).toBe(true);
        expect(wrapper.find('#show-balance').exists()).toBe(true);
    });

    it('should renders correctly for not logged in user', () => {
        const wrapper = mockQuickTrade({
            loggedIn: false,
        });

        expect(wrapper.vm.dropdownText).toBe(MINTME.symbol);
        expect(wrapper.vm.buttonDisabled).toBe(true);
        expect(wrapper.vm.isAmountValid).toBe(false);
        expect(wrapper.find('.all-button').exists()).toBe(false);
        expect(wrapper.find('#show-balance').exists()).toBe(false);
    });

    it('should renders correctly for logged in user and load balance for selected currency', () => {
        const wrapper = mockQuickTrade();

        // WEB (default)
        expect(wrapper.vm.dropdownText).toBe('MINTME');
        expect(wrapper.vm.isCurrencySelected).toBe(true);
        expect(wrapper.vm.buttonDisabled).toBe(true);
        expect(wrapper.vm.isAmountValid).toBe(false);
        expect(wrapper.find('b-dropdown-stub').exists()).toBe(true);

        // Select ETH
        wrapper.vm.onSelect(ethSymbol);
        expect(wrapper.vm.isCurrencySelected).toBe(true);
        expect(wrapper.vm.balanceLoaded).toBe(true);
        expect(wrapper.vm.isAmountValid).toBe(false);
        expect(wrapper.vm.selectedCurrency).toBe(ethSymbol);

        // Select BTC
        wrapper.vm.onSelect(btcSymbol);
        expect(wrapper.vm.isCurrencySelected).toBe(true);
        expect(wrapper.vm.balanceLoaded).toBe(true);
        expect(wrapper.vm.isAmountValid).toBe(false);
        expect(wrapper.vm.selectedCurrency).toBe(btcSymbol);
    });

    it('should rebrand selected currency WEB -> MINTME', () => {
        const wrapper = mockQuickTrade();

        wrapper.vm.selectedCurrency = webSymbol;
        expect(wrapper.vm.rebrandedCurrency).toBe(MINTME.symbol);
    });

    it('should properly check if currency selected', () => {
        const wrapper = mockQuickTrade();

        wrapper.vm.selectedCurrency = '';
        expect(wrapper.vm.isCurrencySelected).toBe(false);

        wrapper.vm.selectedCurrency = webSymbol;
        expect(wrapper.vm.isCurrencySelected).toBe(true);

        wrapper.vm.selectedCurrency = tokSymbol;
        expect(wrapper.vm.isCurrencySelected).toBe(false);

        wrapper.vm.selectedCurrency = btcSymbol;
        expect(wrapper.vm.isCurrencySelected).toBe(true);
    });

    it('should properly check insufficient funds', () => {
        const wrapper = mockQuickTrade();

        wrapper.vm.amount = '100';
        expect(wrapper.vm.insufficientFunds).toBe(true);

        wrapper.vm.amount = '5';
        expect(wrapper.vm.insufficientFunds).toBe(false);
    });

    it('should properly check amount to donate', () => {
        const wrapper = mockQuickTrade({
            params: {
                donation_fee: 1,
                minBtcAmount: 0.000001,
                minMintmeAmount: 0.0001,
            },
        });

        wrapper.vm.amount = '';
        expect(wrapper.vm.isAmountValid).toBe(false);

        wrapper.vm.amount = '0.0';
        expect(wrapper.vm.isAmountValid).toBe(false);

        wrapper.vm.amount = '0.001';
        expect(wrapper.vm.isAmountValid).toBe(true);

        wrapper.vm.amount = '0.0000';
        expect(wrapper.vm.isAmountValid).toBe(false);

        wrapper.vm.amount = '0.0001';
        expect(wrapper.vm.isAmountValid).toBe(true);

        wrapper.vm.selectedCurrency = btcSymbol;
        wrapper.vm.amount = 0.0000001;
        expect(wrapper.vm.isAmountValid).toBe(false);
        wrapper.vm.amount = 0.000001;
        expect(wrapper.vm.isAmountValid).toBe(true);

        wrapper.vm.selectedCurrency = webSymbol;
        wrapper.vm.amount = 0.00001;
        expect(wrapper.vm.isAmountValid).toBe(false);
        wrapper.vm.amount = 0.0001;
        expect(wrapper.vm.isAmountValid).toBe(true);
    });

    it('should properly disable button', () => {
        const wrapper = mockQuickTrade({
            params: {
                donation_fee: .01,
                minMintmeAmount: 0.0001,
            },
        });

        expect(wrapper.vm.buttonDisabled).toBe(true);

        wrapper.setProps({loggedIn: true});
        expect(wrapper.vm.buttonDisabled).toBe(true);

        wrapper.vm.selectedCurrency = webSymbol;
        expect(wrapper.vm.buttonDisabled).toBe(true);

        wrapper.vm.amount = '100';
        expect(wrapper.vm.buttonDisabled).toBe(true);

        wrapper.vm.amount = '5';
        expect(wrapper.vm.buttonDisabled).toBe(false);

        wrapper.vm.isCheckingTrade = true;
        expect(wrapper.vm.buttonDisabled).toBe(true);

        wrapper.vm.isCheckingTrade = false;
        wrapper.vm.isTradeInProgress = true;
        expect(wrapper.vm.buttonDisabled).toBe(true);
    });

    // renames done
    it('can check trade if logged in and currency selected and amount null', (done) => {
        const wrapper = mockQuickTrade({
            params: {
                minBtcAmount: '0.000001',
                minMintmeAmount: '0.0001',
            },
        });

        moxios.stubRequest('check_quick_trade', {
            status: 200,
            response: {
                amountToReceive: '2.5674',
                worth: '0',
                ordersSummary: '0',
            },
        });

        wrapper.vm.amount = '50';
        wrapper.vm.checkTrade();
        expect(wrapper.vm.isCheckingTrade).toBe(true);

        moxios.wait(() => {
            expect(wrapper.vm.amountToReceive).toBe('2.5674');
            expect(wrapper.vm.isCheckingTrade).toBe(false);
            done();
        });
    });

    it('can make trade if logged in and currency selected and amount/amount to receive not null', (done) => {
        const wrapper = mockQuickTrade();

        wrapper.setData({
            selectedCurrency: webSymbol,
            amount: 5,
            amountToReceive: 2,
        });

        moxios.stubRequest('make_quick_trade', {
            status: 200, response: {},
        });

        wrapper.vm.makeTrade();
        expect(wrapper.vm.isTradeInProgress).toBe(true);

        moxios.wait(() => {
            expect(wrapper.vm.amount).toBe(0);
            expect(wrapper.vm.amountToReceive).toBe(0);
            wrapper.vm.$nextTick(() => {
                expect(wrapper.vm.isTradeInProgress).toBe(false);
                done();
            });
        });
    });

    it('can use all funds to trade', () => {
        const wrapper = mockQuickTrade({
            params: {
                minBtcAmount: '0.000001',
                minMintmeAmount: '0.0001',
            },
        });

        wrapper.vm.selectedCurrency = webSymbol;
        wrapper.vm.amount = 5;
        wrapper.vm.all();

        expect(wrapper.vm.amount).toBe('10');

        // Insufficient funds
        wrapper.vm.amount = 5;
        wrapper.vm.all();

        expect(wrapper.vm.amount).toBe('10');
    });

    it('should reset amount and amount to receive on calling resetAmount()', () => {
        const wrapper = mockQuickTrade();

        wrapper.vm.amount = 50;
        wrapper.vm.amountToReceive = 7;
        wrapper.vm.resetAmount();

        expect(wrapper.vm.amount).toBe(0);
        expect(wrapper.vm.amountToReceive).toBe(0);
    });

    it('should show phone verify modal if user is not totally authenticated', () => {
        const wrapper = mockQuickTrade();

        moxios.stubRequest('make_quick_trade', {
            status: 200,
            response: {
                error: true,
                type: 'donation',
            },
        });

        wrapper.vm.makeTrade();

        moxios.wait(() => {
            done();
            expect(wrapper.vm.addPhoneModalVisible).toBe(true);
        });
    });
});
