import {createLocalVue, shallowMount} from '@vue/test-utils';
import '../vueI18nfix.js';
import QuickTrade from '../../js/components/QuickTrade';
import moxios from 'moxios';
import axios from 'axios';
import Vuex from 'vuex';
import {webSymbol, btcSymbol, MINTME, ethSymbol, tokenDeploymentStatus} from '../../js/utils/constants';
import market from '../../js/storage/modules/market';
import {MDropdown} from '../../js/components/UI/index.js';
import AddPhoneAlertModal from '../../js/components/modal/AddPhoneAlertModal';

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.use(Vuex);
    localVue.use({
        install(Vue, options) {
            Vue.prototype.$sanitize = (val) => val;
            Vue.prototype.$axios = {retry: axios, single: axios};
            Vue.prototype.$routing = {generate: (val) => val};
            Vue.prototype.$toasted = {show: () => {}};
            Vue.prototype.$t = (val) => val;
            Vue.prototype.$logger = {error: () => {}};
        },
    });

    return localVue;
}

const localVue = mockVue();

const mockMarket = {
    base: {
        name: 'Webchain',
        symbol: 'WEB',
        subunit: 8,
        identifier: 'WEB',
    },
    identifier: 'TOK0000000000001',
    quote: {
        name: 'token',
        symbol: 'token',
        subunit: 4,
        identifier: 'token',
    },
};
market.state.currentMarketIndex = 'WEB';
market.state.markets = {'WEB': mockMarket};

const store = new Vuex.Store({
    modules: {
        rates: {
            mutations: {
                setRates: jest.fn(),
                setRequesting: jest.fn(),
            },
            state: {
                setRates: 0,
                setRequesting: 0,
            },
            namespaced: true,
            getters: {
                getRequesting: () => 0,
                getRates: () => function() {
                    return {
                        WEB: {
                            USD: '0.01',
                        },
                    };
                },
            },
        },
        minOrder: {
            namespaced: true,
            getters: {
                getMinOrder: () => 10,
            },
        },
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
                isServiceUnavailable: () => false,
                getBalances: function() {
                    return {
                        BTC: {available: '10'},
                        ETH: {available: '10'},
                        WEB: {available: '10'},
                        USDC: {available: '10'},
                        token: {available: '10'},
                    };
                },
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
            getters: {
                getDeploymentStatus: () => tokenDeploymentStatus.deployed,
            },
        },
        market,
        crypto: {
            namespaced: true,
            getters: {
                getCryptosMap: () => {
                    return {
                        'BTC': {subunit: 4},
                        'WEB': {subunit: 4},
                        'ETH': {subunit: 4},
                        'BNB': {subunit: 4},
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
    QuickTrade.methods.debouncedCheck = () => null;

    return shallowMount(QuickTrade, {
        store,
        localVue,
        propsData: {
            loggedIn: true,
            isToken: true,
            websocketUrl: '',
            params: {
                buy_fee: {
                    coin: 0.002,
                    token: 0.002,
                },
                sell_fee: {
                    coin: 0.002,
                    token: 0.002,
                },
            },
            minAmounts: {
                'WEB': 0,
                'ETH': 0,
                'BTC': 0,
                'BNB': 0,
            },
            market: mockMarket,
            disabledServicesConfig: `{
                "depositDisabled": false,
                "withdrawalsDisabled": false,
                "deployDisabled": false,
                "tradesDisabled": {}
            }`,
            ...props,
        },
    });
}

global.ClipboardEvent = function ClipboardEvent() {};

describe('QuickTrade', () => {
    beforeEach(() => {
        moxios.install();
    });

    afterEach(() => {
        moxios.uninstall();
    });

    it('should render correctly for logged in user', () => {
        const wrapper = mockQuickTrade();
        expect(wrapper.vm.rebrandedTop).toBe(MINTME.symbol);
        expect(wrapper.vm.buttonDisabled).toBe(true);
        expect(wrapper.vm.isAmountValid).toBe(false);
    });

    it('amountExceedsOrders return false if ordersSummary is less than amount', () => {
        const wrapper = mockQuickTrade();
        wrapper.vm.worth = '1';
        wrapper.vm.amount = '0.5';
        expect(wrapper.vm.amountExceedsOrders).toBe(false);
    });

    it('amountExceedsOrders return true if summaryOrders is more than amount', () => {
        const wrapper = mockQuickTrade();
        wrapper.vm.ordersSummary = '0.5';
        wrapper.vm.amount = '1';
        expect(wrapper.vm.amountExceedsOrders).toBe(true);
    });

    it('should renders correctly for not logged in user', () => {
        const wrapper = mockQuickTrade({
            loggedIn: false,
        });

        expect(wrapper.vm.rebrandedTop).toBe(MINTME.symbol);
        expect(wrapper.vm.buttonDisabled).toBe(true);
        expect(wrapper.vm.isAmountValid).toBe(false);
    });

    it('should renders correctly for logged in user and load balance for selected currency', () => {
        const wrapper = mockQuickTrade();

        // WEB (default)
        expect(wrapper.vm.rebrandedTop).toBe('MINTME');
        expect(wrapper.vm.buttonDisabled).toBe(true);
        expect(wrapper.vm.isAmountValid).toBe(false);
        expect(wrapper.findComponent(MDropdown).exists()).toBe(true);

        // Select ETH
        wrapper.vm.topCurrency = ethSymbol;
        expect(wrapper.vm.balanceLoaded).toBe(true);
        expect(wrapper.vm.isAmountValid).toBe(false);
        expect(wrapper.vm.topCurrency).toBe(ethSymbol);

        // Select BTC
        wrapper.vm.topCurrency = btcSymbol;
        expect(wrapper.vm.balanceLoaded).toBe(true);
        expect(wrapper.vm.isAmountValid).toBe(false);
        expect(wrapper.vm.topCurrency).toBe(btcSymbol);
    });

    it('should rebrand selected currency WEB -> MINTME', () => {
        const wrapper = mockQuickTrade();

        wrapper.vm.topCurrency = webSymbol;
        expect(wrapper.vm.rebrandedTop).toBe(MINTME.symbol);
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
            },
            minAmounts: {
                'BTC': 0.000001,
                'WEB': 0.0001,
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

        wrapper.vm.topCurrency = btcSymbol;
        wrapper.vm.amount = 0.0000001;
        expect(wrapper.vm.isAmountValid).toBe(false);
        wrapper.vm.amount = 0.000001;
        expect(wrapper.vm.isAmountValid).toBe(true);

        wrapper.vm.topCurrency = webSymbol;
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

        wrapper.vm.topCurrency = webSymbol;
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

    it('can check trade if logged in and currency selected and amount null', (done) => {
        const wrapper = mockQuickTrade({
            params: {
                minBtcAmount: '0.000001',
                minMintmeAmount: '0.0001',
            },
        });

        moxios.stubRequest('check_quick_trade_reversed', {
            status: 200,
            response: {
                amount: '0',
                left: '0',
                ordersSummary: '0',
            },
        });

        wrapper.vm.amountToReceive = '2.5674';
        wrapper.vm.checkTradeReversed();
        expect(wrapper.vm.isCheckingTradeReversed).toBe(true);

        moxios.wait(() => {
            expect(wrapper.vm.amount).toBe('0');
            expect(wrapper.vm.isCheckingTradeReversed).toBe(false);
            done();
        });
    });

    it('can make trade if logged in and currency selected and amount/amount to receive not null', (done) => {
        const wrapper = mockQuickTrade({
            params: {
                minBtcAmount: '0.000001',
                minMintmeAmount: '0.0001',
            },
        });

        wrapper.setData({
            topCurrency: webSymbol,
            amount: 5,
            amountToReceive: 2,
        });

        moxios.stubRequest('make_quick_trade', {
            status: 200, response: {},
        });

        wrapper.vm.makeTrade();
        expect(wrapper.vm.isTradeInProgress).toBe(true);

        moxios.wait(() => {
            expect(wrapper.vm.amount).toBe('');
            expect(wrapper.vm.amountToReceive).toBe('');
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

        wrapper.vm.topCurrency = webSymbol;
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

        expect(wrapper.vm.amount).toBe('');
        expect(wrapper.vm.amountToReceive).toBe('');
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

    it('should open confirmModal on phone verification', () => {
        const wrapper = mockQuickTrade();
        const showConfirmationModalStub = jest.spyOn(wrapper.vm, 'showConfirmationModal');

        wrapper.findComponent(AddPhoneAlertModal).vm.$emit('phone-verified');

        expect(showConfirmationModalStub).toBeCalled();
    });

    it('check amount Reversed Input', () => {
        const wrapper = mockQuickTrade();
        event = {
            target: {
                tagName: 'span',
            },
        };
        event.target.value = '1.234';
        event.target.selectionStart = 0;
        event.target.selectionEnd = 4;
        event.charCode = 48;
        event.preventDefault = function() {};
        expect(wrapper.vm.checkAmountReversedInput()).toBe(true);
    });
});
