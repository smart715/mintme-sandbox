import {createLocalVue, shallowMount} from '@vue/test-utils';
import Vuex from 'vuex';
import {status} from '../../js/storage/modules/websocket';
import Wallet from '../../js/components/wallet/Wallet';
import Decimal from 'decimal.js';
import {webSymbol} from '../../js/utils/constants';
import moxios from 'moxios';
import axios from 'axios';

/**
 * @param {object} params
 * @return {string}
 */
function subRouting(params) {
    return '' + (params.name ? '-' + params.name : '')
            + (params.base ? '-' + params.base : '')
            + (params.quote ? '-' + params.quote : '');
}

const $routing = {
    generate: (val, params) => {
        return val + (params ? subRouting(params) : '');
    },
};

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
            Vue.prototype.$store = new Vuex.Store({
                modules: {status},
            });
            Vue.prototype.$toasted = {show: (val) => val};
            Vue.prototype.$t = (val) => val;
        },
    });
    return localVue;
};

let propsForTestCorrectlyRenders = {
    withdrawUrl: 'withdraw_url',
    createTokenUrl: 'createTokenUrl',
    tradingUrl: 'tradingUrl',
    depositMore: 'depositMore',
    twofa: 'twofa',
    websocketUrl: '',
    disabledCrypto: '["CRYPTO"]',
    disabledServicesConfig: '{"depositDisabled":false,"withdrawalsDisabled":false,"deployDisabled":false}',
};

const assertData = {foo: {name: 'foo', available: 1}, bar: {name: 'bar', available: 1}, baz: {name: 'baz', available: 0}};
const expectData = [{name: 'foo', available: 1}, {name: 'bar', available: 1}, {name: 'baz', available: 0}];
const expectedTokenData = [{name: 'foo', available: 1}, {name: 'bar', available: 1}];

let assertTokens = {};
assertTokens['oTokenName'] = {};
assertTokens['oTokenName'] = {identifier: 'identifier', owner: 'owner', available: '0.5000'};

describe('Wallet', () => {
    beforeEach(() => {
        moxios.install();
    });

    afterEach(() => {
        moxios.uninstall();
    });

    it('should compute hasTokens correctly', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(Wallet, {
            localVue,
            propsData: propsForTestCorrectlyRenders,
        });
        wrapper.vm.tokens = null;
        expect(wrapper.vm.hasTokens).toBe(false);
        wrapper.vm.tokens = [{foo: {name: 'foo'}}];
        expect(wrapper.vm.hasTokens).toBe(true);
    });

    it('should compute allTokens correctly', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(Wallet, {
            localVue,
            propsData: propsForTestCorrectlyRenders,
        });
        wrapper.vm.tokens = null;
        wrapper.vm.predefinedTokens = null;
        expect(wrapper.vm.allTokens).toMatchObject({});
        wrapper.vm.tokens = {foo: {name: 'foo'}};
        wrapper.vm.predefinedTokens = {bar: {name: 'bar'}};
        expect(wrapper.vm.allTokens).toMatchObject({bar: {name: 'bar'}, foo: {name: 'foo'}});
    });

    it('should compute allTokensName correctly', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(Wallet, {
            localVue,
            propsData: propsForTestCorrectlyRenders,
        });
        wrapper.vm.tokens = {foo: {identifier: 'foo'}, bar: {identifier: 'bar'}};
        expect(wrapper.vm.allTokensName).toEqual(['foo', 'bar']);
    });

    it('should compute predefinedItems correctly', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(Wallet, {
            localVue,
            propsData: propsForTestCorrectlyRenders,
        });
        wrapper.vm.predefinedTokens = null;
        expect(wrapper.vm.predefinedItems).toEqual([]);
        wrapper.vm.predefinedTokens = assertData;
        expect(wrapper.vm.predefinedItems).toMatchObject(expectData);
    });

    it('should compute items correctly', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(Wallet, {
            localVue,
            propsData: propsForTestCorrectlyRenders,
        });
        wrapper.vm.tokens = null;
        expect(wrapper.vm.items).toEqual([]);
        wrapper.vm.tokens = assertData;
        expect(wrapper.vm.items).toMatchObject(expectedTokenData);
    });

    it('should compute showLoadingIconP correctly', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(Wallet, {
            localVue,
            propsData: propsForTestCorrectlyRenders,
        });
        wrapper.vm.predefinedTokens = null;
        expect(wrapper.vm.showLoadingIconP).toBe(true);
        wrapper.vm.predefinedTokens = [{name: 'foo'}];
        expect(wrapper.vm.showLoadingIconP).toBe(false);
    });

    it('should compute showLoadingIcon correctly', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(Wallet, {
            localVue,
            propsData: propsForTestCorrectlyRenders,
        });
        wrapper.vm.tokens = null;
        expect(wrapper.vm.showLoadingIcon).toBe(true);
        wrapper.vm.tokens = [{name: 'foo'}];
        expect(wrapper.vm.showLoadingIcon).toBe(false);
    });

    it('should set data correctly when the function openWithdraw() is called', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(Wallet, {
            localVue,
            propsData: propsForTestCorrectlyRenders,
        });
        wrapper.vm.showModal = false;
        wrapper.setProps({twofa: 'foo'});
        wrapper.vm.predefinedTokens = {};
        wrapper.vm.predefinedTokens[webSymbol] = {fee: '0.500000000000000000', available: '.01'};
        wrapper.vm.openWithdraw(webSymbol, '0.500000000000000000', '0.800000000000000000', 8, false, false, webSymbol);
        expect(wrapper.vm.showModal).toBe(true);
        expect(wrapper.vm.selectedCurrency).toBe(webSymbol);
        expect(wrapper.vm.isTokenModal).toBe(false);
        expect(wrapper.vm.withdraw.fee).toBe('0.5');
        expect(wrapper.vm.withdraw.baseFee).toBe('0');
        expect(wrapper.vm.withdraw.availableBase).toBe('.01');
        expect(wrapper.vm.withdraw.amount).toBe('0.8');
        expect(wrapper.vm.withdraw.subunit).toBe(8);
    });

    it('should set showModal correctly when the function closeWithdraw() is called', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(Wallet, {
            localVue,
            propsData: propsForTestCorrectlyRenders,
        });
        wrapper.vm.showModal = true;
        wrapper.vm.closeWithdraw();
        expect(wrapper.vm.showModal).toBe(false);
    });

    describe('openDeposit', () => {
        it('should set properties correctly without $axios request', () => {
            const localVue = mockVue();
            const wrapper = shallowMount(Wallet, {
                localVue,
                propsData: propsForTestCorrectlyRenders,
            });
            wrapper.vm.openDeposit(webSymbol, 8);
            expect(wrapper.vm.depositAddress).toBe('wallet.loading');
            expect(wrapper.vm.depositDescription).toBe('wallet.send_to_address');
            expect(wrapper.vm.selectedCurrency).toBe(webSymbol);
            expect(wrapper.vm.deposit.fee).toBeUndefined();
            expect(wrapper.vm.isTokenModal).toBe(false);
            expect(wrapper.vm.deposit.min).toBe(undefined);
            expect(wrapper.vm.showDepositModal).toBe(true);
        });

        it('should do $axios request and set properties correctly when result of $axios request is empty', (done) => {
            const localVue = mockVue();
            const wrapper = shallowMount(Wallet, {
                localVue,
                propsData: propsForTestCorrectlyRenders,
            });
            wrapper.vm.deposit.fee = 'foo';
            wrapper.vm.openDeposit(webSymbol, 8);

            moxios.stubRequest('deposit_fee', {
                status: 200,
            });

            moxios.wait(() => {
                expect(wrapper.vm.deposit.fee).toBeUndefined();
                done();
            });
        });

        it('should do $axios request and set properties correctly when result of $axios request is 0.0', (done) => {
            const localVue = mockVue();
            const wrapper = shallowMount(Wallet, {
                localVue,
                propsData: propsForTestCorrectlyRenders,
            });
            wrapper.vm.deposit.fee = 'foo';
            wrapper.vm.openDeposit(webSymbol, 8);

            moxios.stubRequest('deposit_fee', {
                status: 200,
                response: 0.0,
            });

            moxios.wait(() => {
                expect(wrapper.vm.deposit.fee).toBeUndefined();
                done();
            });
        });

        it('should do $axios request and set properties correctly when result of $axios request is not empty', (done) => {
            const localVue = mockVue();
            const wrapper = shallowMount(Wallet, {
                localVue,
                propsData: propsForTestCorrectlyRenders,
            });
            wrapper.vm.deposit.fee = '.01';
            wrapper.vm.openDeposit(webSymbol, 8);

            moxios.stubRequest(/deposit_info.*/, {
                status: 200,
                response: {fee: 0.5},
            });

            moxios.wait(() => {
                expect(wrapper.vm.deposit.fee).toBe('0.5');
                done();
            });
        });
    });

    describe('openDepositMore', () => {
        it('should do $axios request and set properties correctly', (done) => {
            const localVue = mockVue();
            const wrapper = shallowMount(Wallet, {
                localVue,
                propsData: propsForTestCorrectlyRenders,
            });
            wrapper.setData({depositMore: webSymbol});
            wrapper.vm.predefinedTokens = {};
            wrapper.vm.predefinedTokens[wrapper.vm.depositMore] = {subunit: 8};
            wrapper.vm.depositAddresses = {};
            wrapper.vm.depositAddresses[wrapper.vm.depositMore] = 'foo';
            wrapper.vm.openDepositMore();

            moxios.stubRequest(/deposit_info.*/, {
                status: 200,
                response: {fee: 0.5},
            });

            moxios.wait(() => {
                expect(wrapper.vm.deposit.fee).toBe('0.5');
                done();
            });
        });
    });

    describe('updateBalances', () => {
        it('should do $axios request and set properties correctly without $axios request', () => {
            const localVue = mockVue();
            const wrapper = shallowMount(Wallet, {
                localVue,
                propsData: propsForTestCorrectlyRenders,
            });
            wrapper.vm.predefinedTokens = {};
            wrapper.vm.predefinedTokens['token'] = {identifier: 'oTokenName'};
            wrapper.vm.tokens = {};
            wrapper.vm.tokens['token'] = {identifier: 'oTokenName'};
            wrapper.vm.updateBalances(assertTokens);
            expect(wrapper.vm.predefinedTokens['token'].available).toBe('0.5000');
            expect(wrapper.vm.tokens['token'].available).toBe('0.5000');
        });

        it('should do $axios request and set properties correctly when result of $axios request is not empty', (done) => {
            const localVue = mockVue();
            const wrapper = shallowMount(Wallet, {
                localVue,
                propsData: propsForTestCorrectlyRenders,
            });
            wrapper.vm.predefinedTokens = {};
            wrapper.vm.predefinedTokens['token'] = {identifier: 'oTokenName'};
            wrapper.vm.tokens = {};
            wrapper.vm.tokens['token'] = {identifier: 'oTokenName', owner: 'owner'};
            wrapper.vm.updateBalances(assertTokens);

            moxios.stubRequest('lock-period-token', {
                status: 200,
                response: {frozenAmount: '0.05'},
            });

            moxios.wait(() => {
                expect(wrapper.vm.tokens['token'].available).toMatchObject(new Decimal(assertTokens['oTokenName'].available).sub('0.05'));
                done();
            });
        });

        it('should do $axios request and set properties correctly when result of $axios request is empty', (done) => {
            const localVue = mockVue();
            const wrapper = shallowMount(Wallet, {
                localVue,
                propsData: propsForTestCorrectlyRenders,
            });
            wrapper.vm.predefinedTokens = {};
            wrapper.vm.predefinedTokens['token'] = {identifier: 'oTokenName'};
            wrapper.vm.tokens = {};
            wrapper.vm.tokens['token'] = {identifier: 'oTokenName', owner: 'owner'};
            wrapper.vm.updateBalances(assertTokens);

            moxios.stubRequest('lock-period-token', {
                status: 200,
            });

            moxios.wait(() => {
                expect(wrapper.vm.tokens['token'].available).toBe('0.5000');
                done();
            });
        });
    });

    it('should set showDepositModal correctly when the function closeDeposit() is called', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(Wallet, {
            localVue,
            propsData: propsForTestCorrectlyRenders,
        });
        wrapper.vm.showDepositModal = true;
        wrapper.vm.closeDeposit();
        expect(wrapper.vm.showDepositModal).toBe(false);
    });

    it('should return correctly value when the function tokensToArray() is called', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(Wallet, {
            localVue,
            propsData: propsForTestCorrectlyRenders,
        });
        expect(wrapper.vm.tokensToArray(assertData)).toMatchObject(expectData);
    });

    it('should return correctly url when the function generatePairUrl() is called', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(Wallet, {
            localVue,
            propsData: propsForTestCorrectlyRenders,
        });
        expect(wrapper.vm.generatePairUrl({name: 'foo'})).toBe('token_show-foo');
    });

    it('should return correctly url when the function generateCoinUrl() is called', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(Wallet, {
            localVue,
            propsData: propsForTestCorrectlyRenders,
        });
        wrapper.vm.predefinedTokens = {BTC: {name: 'BTC'}, WEB: {name: 'WEB'}};
        expect(wrapper.vm.generateCoinUrl({exchangeble: false})).toBe('coin-WEB');
        expect(wrapper.vm.generateCoinUrl({exchangeble: true, tradable: false, name: 'foo'})).toBe('coin-BTC-WEB');
        expect(wrapper.vm.generateCoinUrl({exchangeble: true, tradable: true, name: 'foo'})).toBe('coin-BTC-foo');
    });
});
