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

const $store = new Vuex.Store({
    modules: {status},
});

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
            Vue.prototype.$toasted = {show: (val) => val};
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
};

const assertData = {foo: {name: 'foo'}, bar: {name: 'bar'}};
const expectData = [{name: 'foo'}, {name: 'bar'}];

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
        expect(wrapper.vm.hasTokens).to.be.false;
        wrapper.vm.tokens = {foo: 'foo'};
        expect(wrapper.vm.hasTokens).to.be.true;
    });

    it('should compute allTokens correctly', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(Wallet, {
            localVue,
            propsData: propsForTestCorrectlyRenders,
        });
        wrapper.vm.tokens = null;
        wrapper.vm.predefinedTokens = null;
        expect(wrapper.vm.allTokens).to.deep.equal({});
        wrapper.vm.tokens = {foo: 'foo'};
        wrapper.vm.predefinedTokens = {bar: 'bar'};
        expect(wrapper.vm.allTokens).to.deep.equal({foo: 'foo', bar: 'bar'});
    });

    it('should compute allTokensName correctly', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(Wallet, {
            localVue,
            propsData: propsForTestCorrectlyRenders,
        });
        wrapper.vm.tokens = {foo: {identifier: 'foo'}, bar: {identifier: 'bar'}};
        expect(wrapper.vm.allTokensName).to.deep.equal(['foo', 'bar']);
    });

    it('should compute predefinedItems correctly', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(Wallet, {
            localVue,
            propsData: propsForTestCorrectlyRenders,
        });
        wrapper.vm.predefinedTokens = null;
        expect(wrapper.vm.predefinedItems).to.deep.equal([]);
        wrapper.vm.predefinedTokens = assertData;
        expect(wrapper.vm.predefinedItems).to.deep.equal(expectData);
    });

    it('should compute items correctly', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(Wallet, {
            localVue,
            propsData: propsForTestCorrectlyRenders,
        });
        wrapper.vm.tokens = null;
        expect(wrapper.vm.items).to.deep.equal([]);
        wrapper.vm.tokens = assertData;
        expect(wrapper.vm.items).to.deep.equal(expectData);
    });

    it('should compute showLoadingIconP correctly', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(Wallet, {
            localVue,
            propsData: propsForTestCorrectlyRenders,
        });
        wrapper.vm.predefinedTokens = null;
        expect(wrapper.vm.showLoadingIconP).to.be.true;
        wrapper.vm.predefinedTokens = 'foo';
        expect(wrapper.vm.showLoadingIconP).to.be.false;
    });

    it('should compute showLoadingIcon correctly', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(Wallet, {
            localVue,
            propsData: propsForTestCorrectlyRenders,
        });
        wrapper.vm.tokens = null;
        expect(wrapper.vm.showLoadingIcon).to.be.true;
        wrapper.vm.tokens = 'foo';
        expect(wrapper.vm.showLoadingIcon).to.be.false;
    });

    it('should set data correctly when the function openWithdraw() is called', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(Wallet, {
            localVue,
            propsData: propsForTestCorrectlyRenders,
        });
        wrapper.vm.showModal = false;
        wrapper.vm.twofa = '';
        wrapper.vm.openWithdraw('currency', 'fee', 'amount', 'subunit');
        expect(wrapper.vm.showModal).to.be.false;
        wrapper.vm.twofa = 'foo';
        wrapper.vm.predefinedTokens = {};
        wrapper.vm.predefinedTokens[webSymbol] = {fee: '0.500000000000000000', available: true};
        wrapper.vm.openWithdraw(webSymbol, '0.500000000000000000', '0.800000000000000000', 8);
        expect(wrapper.vm.showModal).to.be.true;
        expect(wrapper.vm.selectedCurrency).to.equal(webSymbol);
        expect(wrapper.vm.isTokenModal).to.be.false;
        expect(wrapper.vm.withdraw.fee).to.equal('0.5');
        expect(wrapper.vm.withdraw.webFee).to.equal('0.5');
        expect(wrapper.vm.withdraw.availableWeb).to.be.true;
        expect(wrapper.vm.withdraw.amount).to.equal('0.8');
        expect(wrapper.vm.withdraw.subunit).to.equal(8);
    });

    it('should set showModal correctly when the function closeWithdraw() is called', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(Wallet, {
            localVue,
            propsData: propsForTestCorrectlyRenders,
        });
        wrapper.vm.showModal = true;
        wrapper.vm.closeWithdraw();
        expect(wrapper.vm.showModal).to.be.false;
    });

    describe('openDeposit', () => {
        it('should set properties correctly without $axios request', () => {
            const localVue = mockVue();
            const wrapper = shallowMount(Wallet, {
                localVue,
                propsData: propsForTestCorrectlyRenders,
            });
            wrapper.vm.openDeposit(webSymbol, 8);
            expect(wrapper.vm.depositAddress).to.equal('Loading..');
            expect(wrapper.vm.depositDescription).to.equal('Send WEB to the address above.');
            expect(wrapper.vm.selectedCurrency).to.equal(webSymbol);
            expect(wrapper.vm.deposit.fee).to.be.undefined;
            expect(wrapper.vm.isTokenModal).to.be.false;
            expect(wrapper.vm.deposit.min).to.equal('1');
            expect(wrapper.vm.showDepositModal).to.be.true;
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
                expect(wrapper.vm.deposit.fee).to.be.undefined;
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
                expect(wrapper.vm.deposit.fee).to.be.undefined;
                done();
            });
        });

        it('should do $axios request and set properties correctly when result of $axios request is not empty', (done) => {
            const localVue = mockVue();
            const wrapper = shallowMount(Wallet, {
                localVue,
                propsData: propsForTestCorrectlyRenders,
            });
            wrapper.vm.deposit.fee = 'foo';
            wrapper.vm.openDeposit(webSymbol, 8);

            moxios.stubRequest('deposit_fee', {
                status: 200,
                response: 0.5,
            });

            moxios.wait(() => {
                expect(wrapper.vm.deposit.fee).to.equal('0.5');
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
            wrapper.vm.depositMore = webSymbol;
            wrapper.vm.predefinedTokens = {};
            wrapper.vm.predefinedTokens[wrapper.vm.depositMore] = {subunit: 8};
            wrapper.vm.depositAddresses = {};
            wrapper.vm.depositAddresses[wrapper.vm.depositMore] = 'foo';
            wrapper.vm.openDepositMore();

            moxios.stubRequest('deposit_fee', {
                status: 200,
                response: 0.5,
            });

            moxios.wait(() => {
                expect(wrapper.vm.deposit.fee).to.equal('0.5');
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
            expect(wrapper.vm.predefinedTokens['token'].available).to.equal('0.5000');
            expect(wrapper.vm.tokens['token'].available).to.equal('0.5000');
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
                expect(wrapper.vm.tokens['token'].available).to.deep.equal(new Decimal(assertTokens['oTokenName'].available).sub('0.05'));
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
                expect(wrapper.vm.tokens['token'].available).to.equal('0.5000');
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
        expect(wrapper.vm.showDepositModal).to.be.false;
    });

    it('should return correctly value when the function tokensToArray() is called', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(Wallet, {
            localVue,
            propsData: propsForTestCorrectlyRenders,
        });
        expect(wrapper.vm.tokensToArray(assertData)).to.deep.equal(expectData);
    });

    it('should return correctly url when the function generatePairUrl() is called', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(Wallet, {
            localVue,
            propsData: propsForTestCorrectlyRenders,
        });
        expect(wrapper.vm.generatePairUrl({name: 'foo'})).to.equal('token_show-foo');
    });

    it('should return correctly url when the function generateCoinUrl() is called', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(Wallet, {
            localVue,
            propsData: propsForTestCorrectlyRenders,
        });
        wrapper.vm.predefinedTokens = {BTC: {name: 'BTC'}, WEB: {name: 'WEB'}};
        expect(wrapper.vm.generateCoinUrl({exchangeble: false})).to.equal('coin-WEB');
        expect(wrapper.vm.generateCoinUrl({exchangeble: true, tradable: false, name: 'foo'})).to.equal('coin-BTC-WEB');
        expect(wrapper.vm.generateCoinUrl({exchangeble: true, tradable: true, name: 'foo'})).to.equal('coin-BTC-foo');
    });
});
