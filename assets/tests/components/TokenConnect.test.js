import {shallowMount, createLocalVue} from '@vue/test-utils';
import TokenConnect from '../../js/components/token/deploy/TokenConnect';
import {GENERAL} from '../../js/utils/constants';
import Vuex from 'vuex';
import moxios from 'moxios';
import axios from 'axios';
import moment from 'moment';

const localVue = mockVue();

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.use(Vuex);
    localVue.use({
        install(Vue) {
            Vue.prototype.$routing = {generate: (val) => val};
            Vue.prototype.$axios = {retry: axios, single: axios};
            Vue.prototype.$toasted = {show: () => {}};
            Vue.prototype.$t = (val) => val;
            Vue.prototype.$te = () => false;
            Vue.prototype.$logger = {error: (val) => {}};
        },
    });

    return localVue;
}

/**
 * @param {Object} props
 * @return {Object}
 */
function createSharedTestProps(props = {}) {
    return {
        disabledServicesConfig: disabledServicesConfigTest,
        tokenName: 'tokenNameTest',
        deployCrypto: {},
        enabled: true,
        explorerUrls: {
            'WEB': mintmeExplorerUrlTest,
            'ETH': ethExplorerUrlTest,
            'BNB': bnbExplorerUrlTest,
        },
        ...props,
    };
}

/**
 * @param {Object} getters
 * @return {Vuex.Store}
 */
function createSharedTestStore(getters) {
    return new Vuex.Store({
        modules: getters,
    });
}

/**
 * @param {Object} props
 * @param {Object} computed
 * @param {Vuex.Store} store
 * @return {Wrapper<Vue>}
 */
function mockTokenConnect(props = {}, computed, store) {
    TokenConnect.methods.selectDefaultCrypto = () => false;
    TokenConnect.methods.fetchConnectCosts = () => false;

    return shallowMount(TokenConnect, {
        localVue: localVue,
        sync: false,
        store: store,
        propsData: createSharedTestProps(props),
        computed: {
            ...computed,
        },
    });
}

const currentDate = moment(new Date()).format(GENERAL.dateFormat);

const mintmeExplorerUrlTest = 'mintmeExplorerUrlTest';
const ethExplorerUrlTest = 'ethExplorerUrlTest';
const bnbExplorerUrlTest = 'bnbExplorerUrlTest';

const disabledServicesConfigTest = `
    {
        "allServicesDisabled": false,
        "deployDisabled": false,
        "blockchainDeployStatus": {"MINTME": true}
    }
`;

const getIndexedDeploysTest = ['WEB'];

const getBalancesTest = {
    'WEB': {available: 100},
};

const getDeploysTest2 = [
    {
        pending: false,
        crypto: {
            symbol: 'WEB',
        },
        available: 100,
        symbol: 'WEB',
        subunit: 4,
        txHash: 'txHashTest',
    },
];

const getDeploysTest1 = [
    {
        pending: true,
        crypto: 'MINTME',
        available: 100,
        symbol: 'WEB',
        subunit: 4,
    },
];

describe('TokenConnect', () => {
    let wrapper;
    let store;
    let getters;

    beforeEach(() => {
        getters = {
            tokenInfo: {
                namespaced: true,
                getters: {
                    getDeploys: () => getDeploysTest1,
                    getIndexedDeploys: () => getIndexedDeploysTest,
                },
            },
            tradeBalance: {
                namespaced: true,
                getters: {
                    getBalances: () => getBalancesTest,
                    isServiceUnavailable: () => false,
                },
            },
            user: {
                namespaced: true,
                getters: {
                    getId: () => 1,
                },
            },
            crypto: {
                namespaced: true,
                getters: {
                    getCryptosMap: () => {
                        return {
                            'BTC': {subunit: 8, blockchainAvailable: true, moneySymbol: 'BTC'},
                            'WEB': {subunit: 4, blockchainAvailable: true, moneySymbol: 'WEB'},
                            'ETH': {subunit: 6, blockchainAvailable: true, moneySymbol: 'ETH'},
                        };
                    },
                },
                actions: {
                    updateCrypto: (symbol) => {},
                },
            },
        },

        store = createSharedTestStore(getters);

        moxios.install();

        wrapper = mockTokenConnect({}, {}, store);
    });

    afterEach(() => {
        moxios.uninstall();
    });

    it('Verify that "pendingCrypto" return the correct value', () => {
        expect(wrapper.vm.pendingCrypto).toBe('MINTME');
    });

    it('Verify that "isConnecting" return the correct value', async () => {
        await wrapper.setData({
            isRequesting: true,
        });

        expect(wrapper.vm.isConnecting).toBeTruthy();
    });

    it('Verify that "balance" return the correct value', async () => {
        await wrapper.setData({
            selectedCrypto: 'WEB',
        });

        expect(wrapper.vm.balance).toBe(100);
    });

    it('Verify that "cost" return the correct value', async () => {
        await wrapper.setData({
            costs: {
                'WEB': 99,
            },
            selectedCrypto: 'WEB',
        });

        expect(wrapper.vm.cost).toBe(99);
    });

    it('Verify that "balancePrecision" return the correct value', async () => {
        await wrapper.setData({
            selectedCrypto: 'WEB',
        });

        expect(wrapper.vm.balancePrecision).toBe(4);
    });

    it('Verify that "costExceeds" return the correct value', async () => {
        await wrapper.setData({
            costs: {
                'WEB': 99,
            },
            selectedCrypto: 'WEB',
        });

        expect(wrapper.vm.costExceeds).toBeFalsy();

        await wrapper.setData({
            costs: {
                'WEB': 200,
            },
        });

        expect(wrapper.vm.costExceeds).toBeTruthy();
    });

    it('Verify that "btnDisabled" return the correct value', async () => {
        await wrapper.setData({
            costs: {
                'WEB': 99,
            },
            selectedCrypto: 'WEB',
            isRequesting: true,
        });

        expect(wrapper.vm.btnDisabled).toBeTruthy();
    });

    it('Verify that "isDeploymentDisabled" return the correct value', () => {
        expect(wrapper.vm.isDeploymentDisabled).toBeFalsy();
    });

    it('Verify that "selectedCryptoRebranded" return the correct value', async () => {
        await wrapper.setData({
            selectedCrypto: 'WEB',
        });

        expect(wrapper.vm.selectedCryptoRebranded).toBe('MINTME');
    });

    it('Verify that "availableCryptos" return the correct value', () => {
        expect(wrapper.vm.availableCryptos).toEqual(['WEB']);
    });

    it('Verify that "isFullConnected" return the correct value', () => {
        expect(wrapper.vm.isFullConnected).toBeFalsy();
    });

    it('Verify that "services" return the correct value', () => {
        const value = JSON.parse(disabledServicesConfigTest);

        expect(wrapper.vm.services).toEqual(value);
    });

    it('Verify that "translationContext" return the correct value', async () => {
        const value = {'blockchain': 'MINTME'};

        await wrapper.setData({
            selectedCrypto: 'WEB',
        });

        expect(wrapper.vm.translationContext).toEqual(expect.objectContaining(value));
    });

    it('Verify that "deploysData" return the correct value', () => {
        expect(wrapper.vm.deploysData).toEqual([]);
    });

    it('Verify that "parseDate" works correctly', () => {
        const date = new Date();

        expect(wrapper.vm.parseDate(date)).toBe(currentDate);
    });

    describe('Post "token_deploy" success', () => {
        let wrapper;
        let store;
        let getters;

        beforeEach(() => {
            getters = {
                tokenInfo: {
                    namespaced: true,
                    getters: {
                        getDeploys: () => getDeploysTest2,
                        getIndexedDeploys: () => getIndexedDeploysTest,
                    },
                },
                tradeBalance: {
                    namespaced: true,
                    getters: {
                        getBalances: () => getBalancesTest,
                        isServiceUnavailable: () => false,
                    },
                },
                user: {
                    namespaced: true,
                    getters: {
                        getId: () => 1,
                    },
                },
            };

            const computed = {
                isDeploymentDisabled() {
                    return false;
                },
                isConnecting() {
                    return false;
                },
                btnDisabled() {
                    return false;
                },
            };

            store = createSharedTestStore(getters);

            wrapper = mockTokenConnect({}, computed, store);

            moxios.install();
        });

        afterEach(() => {
            moxios.uninstall();
        });

        it('Verify that "doConnection" works correctly', (done) => {
            moxios.stubRequest('token_deploy', {
                status: 200,
                response: {},
            });

            wrapper.vm.doConnection();

            moxios.wait(() => {
                expect(wrapper.emitted('pending')).toBeTruthy();
                expect(wrapper.vm.isRequesting).toBeFalsy();
                done();
            });
        });
    });

    describe('Verify different values of "getDeploys"', () => {
        let wrapper;
        let store;
        let getters;

        beforeEach(() => {
            getters = {
                tokenInfo: {
                    namespaced: true,
                    getters: {
                        getDeploys: () => getDeploysTest2,
                        getIndexedDeploys: () => getIndexedDeploysTest,
                    },
                },
                tradeBalance: {
                    namespaced: true,
                    getters: {
                        getBalances: () => getBalancesTest,
                        isServiceUnavailable: () => false,
                    },
                },
                user: {
                    namespaced: true,
                    getters: {
                        getId: () => 1,
                    },
                },
            },

            store = createSharedTestStore(getters);

            wrapper = mockTokenConnect({}, {}, store);
        });

        it('Verify different values of "rewardsMaxLimit"', async () => {
            const value = {
                chainSymbol: 'MINTME',
                cryptoSymbol: 'WEB',
                date: currentDate,
                txHashUrl: 'mintmeExplorerUrlTest/tx/txHashTest',
            };

            expect(wrapper.vm.deploysData).toEqual([value]);
        });
    });
});
