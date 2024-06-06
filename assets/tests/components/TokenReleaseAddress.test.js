import Vue from 'vue';
import Vuelidate from 'vuelidate';
import Toasted from 'vue-toasted';
import {shallowMount, createLocalVue} from '@vue/test-utils';
import TokenReleaseAddress from '../../js/components/token/TokenReleaseAddress';
import tradeBalanceModule from '../../js/storage/modules/trade_balance';
import tokenInfoModule from '../../js/storage/modules/token_info';
import axios from 'axios';
import {MButton, MInput} from '../../js/components/UI';
import {DepositModalMixin} from '../../js/mixins';
import Vuex from 'vuex';
import moxios from 'moxios';

Vue.use(Vuelidate);
Vue.use(Toasted);
Vue.use(Vuex);

const newAddress = '0x1111111111111111111111111111111111111111';

const tokenCrypto = {
    name: 'Webchain',
    symbol: 'WEB',
    subunit: 4,
    tradable: true,
    exchangeble: true,
    isToken: false,
    image: {},
    identifier: 'WEB',
};

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.use(Vuex);
    localVue.use({
        install(Vue) {
            Vue.prototype.$axios = {retry: axios, single: axios};
            Vue.prototype.$routing = {generate: (val) => val};
            Vue.prototype.$t = (val) => val;
            Vue.prototype.$logger = {error: (val) => {}};
        },
    });
    return localVue;
}

/**
 * @return {Vuex.Store}
 */
function createStore() {
    const tradeBalance = Object.assign({}, tradeBalanceModule);

    tradeBalance.state.balances = {WEB: {available: 999999}};

    const tokenInfo = Object.assign({}, tokenInfoModule);

    tokenInfo.state.deploys = [
        {
            crypto: {
                symbol: 'WEB',
                subunit: 8,
            },
        },
    ];

    return new Vuex.Store({
        modules: {
            tradeBalance,
            tokenInfo,
            user: {
                namespaced: true,
                getters: {
                    getId: () => 1,
                },
            },
        },
    });
}

/**
 * @return {Wrapper<Vue>}
 */
function mockReleaseAddress() {
    return shallowMount(TokenReleaseAddress, {
        store: createStore(),
        localVue: mockVue(),
        mixins: [DepositModalMixin],
        propsData: {
            tokenCrypto,
            releaseAddress: 'foobar',
            isTokenDeployed: true,
            twofa: false,
            disabledServicesConfig: `{
                "depositDisabled": false,
                "tokenDepositsDisabled": false,
                "allServicesDisabled": false
            }`,
        },
    });
}

describe('TokenReleaseAddress', () => {
    beforeEach(() => {
        moxios.install();
    });

    afterEach(() => {
        moxios.uninstall();
    });

    it('renders correctly with assigned props', () => {
        const wrapper = mockReleaseAddress();
        expect(wrapper.vm.currentAddress).toBe('foobar');
    });

    describe('can be edited if deployed only', () => {
        let wrapper;

        beforeEach(() => {
            wrapper = mockReleaseAddress();
        });

        it('When "isTokenDeployed" is true', async () => {
            await wrapper.setProps({
                isTokenDeployed: true,
            });

            expect(wrapper.findComponent(MInput).exists()).toBe(true);
        });
        it('When "isTokenDeployed" is false', async () => {
            await wrapper.setProps({
                isTokenDeployed: false,
                twofa: true,
            });

            expect(wrapper.findComponent(MInput).exists()).toBe(false);
        });
    });

    it('Check if networkName returns the correct network text', async () => {
        const wrapper = mockReleaseAddress();

        const translationKeyWeb = 'dynamic.blockchain_WEB_name';
        const tokenCryptoETH = {symbol: 'ETH'};
        const ethNetworkName = 'ETH';

        expect(wrapper.vm.networkName).toStrictEqual(translationKeyWeb);

        await wrapper.setProps({
            tokenCrypto: tokenCryptoETH,
        });

        expect(wrapper.vm.networkName).toStrictEqual(ethNetworkName);
    });

    describe('2fa modal', () => {
        it('is displayed after submit if 2fa is enabled', async () => {
            const wrapper = mockReleaseAddress();

            await wrapper.setProps({
                twofa: true,
            });

            await wrapper.setData({
                contractFee: '0.1',
                contractFeeSecondsLeft: 5,
            });

            expect(wrapper.vm.showTwoFactorModal).toBe(false);
            wrapper.findComponent(MInput).vm.$emit('input', newAddress);
            wrapper.findComponent(MButton).vm.$emit('click');
            expect(wrapper.vm.showTwoFactorModal).toBe(true);
        });

        it('is not displayed after submit if 2fa is disabled', async () => {
            const wrapper = mockReleaseAddress();

            await wrapper.setProps({
                twofa: false,
            });

            await wrapper.setData({
                contractFee: '0.1',
                contractFeeSecondsLeft: 5,
            });

            expect(wrapper.vm.showTwoFactorModal).toBe(false);
            wrapper.findComponent(MInput).vm.$emit('input', newAddress);
            wrapper.findComponent(MButton).vm.$emit('click');
            expect(wrapper.vm.showTwoFactorModal).toBe(false);
        });
    });

    it('fetchContractFee: should set data properly', async (done) => {
        const wrapper = mockReleaseAddress();

        await wrapper.setProps({
            twofa: true,
        });

        moxios.stubRequest('token_contract_fee', {status: 200, response: 0.2});

        wrapper.vm.fetchContractFee();

        moxios.wait(() => {
            expect(wrapper.vm.contractFee).toBe(0.2);
            expect(wrapper.vm.contractFeeNeedUpdate).toBe(false);
            done();
        });
    });

    it('Check that "closeTwoFactorModal" works correctly', async () => {
        const wrapper = mockReleaseAddress();

        await wrapper.setData({
            showTwoFactorModal: true,
        });

        wrapper.vm.closeTwoFactorModal();

        expect(wrapper.vm.showTwoFactorModal).toBe(false);
    });

    it('Check that "setUpdatingState" works correctly', async () => {
        const wrapper = mockReleaseAddress();

        await wrapper.setProps({
            releaseAddress: '',
        });

        wrapper.vm.setUpdatingState();

        expect(wrapper.vm.currentAddress).toBe('0x');
    });
});
