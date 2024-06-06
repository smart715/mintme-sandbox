import {createLocalVue, shallowMount} from '@vue/test-utils';
import TokenDeploy from '../../js/components/token/deploy/TokenDeploy';
import moxios from 'moxios';
import Vuex from 'vuex';
import tradeBalance from '../../js/storage/modules/trade_balance';
import tokenSettings from '../../js/storage/modules/token_settings';
import axios from 'axios';

/**
 * @return {Component}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.use(Vuex);
    localVue.use({
        install(Vue, options) {
            Vue.prototype.$axios = {retry: axios, single: axios};
            Vue.prototype.$routing = {generate: (val) => val};
            Vue.prototype.$toasted = {info: (val) => val};
            Vue.prototype.$toasted = {show: () => {}};
            Vue.prototype.$t = (val) => val;
            Vue.prototype.$te = () => false;
            Vue.prototype.$logger = {error: (val) => {}};
        },
    });

    return localVue;
}


/**
 * @param {Boolean} balanceFetched
 * @return {Vuex.Store}
 */
function createStore(balanceFetched) {
    if (balanceFetched) {
        tradeBalance.state.balances = {WEB: {available: 999999, bonus: 0}};
    } else {
        tradeBalance.state.balances = null;
    }

    return new Vuex.Store({
        modules: {
            tradeBalance,
            tokenSettings,
            user: {
                namespaced: true,
                getters: {
                    getId: () => 1,
                },
            },
            websocket: {
                namespaced: true,
                actions: {
                    addMessageHandler: () => {},
                },
            },
            crypto: {
                namespaced: true,
                getters: {
                    getCryptosMap: () => {
                        return {
                            'BTC': {blockchainAvailable: true, moneySymbol: 'BTC'},
                            'WEB': {blockchainAvailable: true, moneySymbol: 'WEB'},
                            'ETH': {blockchainAvailable: true, moneySymbol: 'ETH'},
                            'BNB': {blockchainAvailable: true, moneySymbol: 'BNB'},
                        };
                    },
                },
                actions: {
                    updateCrypto: (symbol) => {},
                },
            },
        },
    });
}

/**
 * @param {Vuex.Store} store
 * @param {String|null} crypto
 * @param {String|null} amount
 * @param {String|null} bonusAmount
 * @return {undefined}
 */
function setBalance(store, crypto = null, amount = null, bonusAmount = null) {
    if (null === crypto && null === amount) {
        store.commit('tradeBalance/setBalances', null);
        return;
    }

    store.commit('tradeBalance/setBalances', {
        [crypto]: {
            available: amount,
            bonus: bonusAmount,
        },
    });
}

/**
 * @param {Vuex.Store} store
 * @param {Boolean} hasReleasePeriod
 * @return {undefined}
 */
function setHasReleasePeriod(store, hasReleasePeriod) {
    store.commit('tokenSettings/setHasReleasePeriod', hasReleasePeriod);
}

/**
 * @param {Boolean} balanceFetched
 * @param {Boolean} isOwner
 * @param {String} status
 * @param {Object} disabledServicesConfig
 * @return {Wrapper<Vue>}
 */
function mockTokenDeploy(balanceFetched, isOwner = true, status = 'not-deployed', disabledServicesConfig = null) {
    const defaultDisabledServicesConfig = {
        depositDisabled: false,
        withdrawalsDisabled: false,
        deployDisabled: false,
        blockchainDeployStatus: {
            ETH: false,
            BNB: false,
            MINTME: true,
        },
    };
    disabledServicesConfig = disabledServicesConfig
        ? JSON.stringify(disabledServicesConfig)
        : JSON.stringify(defaultDisabledServicesConfig);

    const localVue = mockVue();
    const wrapper = shallowMount(TokenDeploy, {
        store: createStore(balanceFetched),
        localVue,
        propsData: {
            name: 'foo',
            hasReleasePeriod: false,
            isOwner: isOwner,
            precision: 4,
            statusProp: status,
            websocketUrl: '',
            disabledServicesConfig: disabledServicesConfig,
        },
    });

    if (balanceFetched) {
        wrapper.vm.costs = {WEB: 999};
    }

    return wrapper;
}

describe('TokenDeploy', () => {
    beforeEach(() => {
        moxios.install();
    });

    afterEach(() => {
        moxios.uninstall();
    });

    it('show loading icon if balances not fetched yet', async () => {
        const wrapper = mockTokenDeploy(true);
        wrapper.vm.costs = {WEB: 9999};

        setHasReleasePeriod(wrapper.vm.$store, true);

        expect(wrapper.findComponent('.spinner-border').exists()).toBe(false);

        setBalance(wrapper.vm.$store, null);
        wrapper.vm.costs = null;

        await wrapper.vm.$nextTick();

        expect(wrapper.findComponent('.spinner-border').exists()).toBe(true);
    });

    it('show message that editing of token release period is needed', async () => {
        const wrapper = mockTokenDeploy(true);

        setHasReleasePeriod(wrapper.vm.$store, false);
        await wrapper.vm.$nextTick();

        const message = wrapper.findComponent('.text-muted');
        expect(message.exists()).toBe(true);
        expect(message.text()).toContain('token.deploy.edit_release_period_1');
        expect(message.text()).toContain('token.deploy.edit_release_period_2');
        expect(message.text()).toContain('token.deploy.edit_release_period_3');
    });

    describe('deploy btn', () => {
        it('should disable it if the cost is higher than the balance', async () => {
            const wrapper = mockTokenDeploy(true);

            setBalance(wrapper.vm.$store, 'WEB', '999', '1');
            wrapper.vm.costs = {WEB: 9999};
            wrapper.vm.deploying = false;
            await wrapper.vm.$nextTick();

            expect(wrapper.vm.btnDisabled).toBe(true);
        });

        it('should disabled it if is deploying', () => {
            const wrapper = mockTokenDeploy(true);

            setBalance(wrapper.vm.$store, 'WEB', '999', '1');
            wrapper.vm.deploying = false;
            expect(wrapper.vm.btnDisabled).toBe(false);

            wrapper.vm.deploying = true;
            expect(wrapper.vm.btnDisabled).toBe(true);
        });
    });

    it('should show exceed cost message if the cost is higher than the balance', async () => {
        const wrapper = mockTokenDeploy(true);

        setBalance(wrapper.vm.$store, 'WEB', '98', '1');
        await wrapper.vm.$nextTick();

        wrapper.vm.costs = {WEB: 99};
        expect(wrapper.vm.costExceeds).toBe(false);

        wrapper.vm.costs = {WEB: 9999};
        expect(wrapper.vm.costExceeds).toBe(true);
    });

    it('deploy() function should work correctly', (done) => {
        const wrapper = mockTokenDeploy(true, true, 'pending');
        wrapper.vm.deploy();
        expect(wrapper.vm.status).toBe('pending');

        moxios.stubRequest('token_deploy', {status: 200, response: true});

        moxios.wait(() => {
            expect(wrapper.vm.status).toBe('pending');
            done();
        });
    });

    describe('fetchCosts() function', () => {
        it('should work correctly', (done) => {
            const wrapper = mockTokenDeploy(false);

            expect(wrapper.vm.costs).toBe(null);

            moxios.stubRequest('token_deploy_costs', {
                status: 200,
                response: {
                    WEB: 99,
                },
            });

            wrapper.vm.fetchCosts();

            wrapper.vm.fetchCosts();

            moxios.wait(() => {
                expect(wrapper.vm.cost).toBe('99');
                done();
            });
        });

        it('should be called on mounted if isOwner and token is not deployed', (done) => {
            moxios.stubRequest('token_deploy_costs', {
                status: 200,
                response: {
                    WEB: 99,
                },
            });

            const wrapper = mockTokenDeploy(false, true, 'not-deployed');

            moxios.wait(() => {
                expect(wrapper.vm.cost).toBe('99');
                done();
            });
        });

        it('should not be called on mounted if isOwner and token is deployed', (done) => {
            moxios.stubRequest('token_deploy_costs', {
                status: 200,
                response: {
                    WEB: 99,
                },
            });

            const wrapper = mockTokenDeploy(false, true, 'deployed');

            moxios.wait(() => {
                expect(wrapper.vm.cost).toBe('0');
                done();
            });
        });

        it('should not be called on mounted if is not owner', (done) => {
            moxios.stubRequest('token_deploy_balances', {
                status: 200, response: {
                    WEB: 99,
                },
            });

            const wrapper = mockTokenDeploy(false, false, 'not-deployed');
            moxios.wait(() => {
                expect(wrapper.vm.cost).toBe('0');
                done();
            });
        });
    });

    describe('blockchainContext computed property', () => {
        describe('should return blockchain name properly, if only one is available', () => {
            it('WEB => MINTME', () => {
                const disabledServicesConfig = {
                    depositDisabled: false,
                    withdrawalsDisabled: false,
                    deployDisabled: false,
                    blockchainDeployStatus: {
                        ETH: false,
                        BNB: false,
                        MINTME: true,
                    },
                };
                const wrapper = mockTokenDeploy(
                    false,
                    true,
                    'not-deployed',
                    disabledServicesConfig
                );

                expect(wrapper.vm.blockchainContext.blockchainName).toEqual('MINTME');
            });

            it('BNB => BSC', () => {
                const disabledServicesConfig = {
                    depositDisabled: false,
                    withdrawalsDisabled: false,
                    deployDisabled: false,
                    blockchainDeployStatus: {
                        ETH: false,
                        BNB: true,
                        MINTME: false,
                    },
                };
                const wrapper = mockTokenDeploy(
                    false,
                    true,
                    'not-deployed',
                    disabledServicesConfig
                );

                expect(wrapper.vm.blockchainContext.blockchainName).toEqual('BSC');
            });

            it('ETH => ETH', () => {
                const disabledServicesConfig = {
                    depositDisabled: false,
                    withdrawalsDisabled: false,
                    deployDisabled: false,
                    blockchainDeployStatus: {
                        ETH: true,
                        BNB: false,
                        MINTME: false,
                    },
                };
                const wrapper = mockTokenDeploy(
                    false,
                    true,
                    'not-deployed',
                    disabledServicesConfig
                );

                expect(wrapper.vm.blockchainContext.blockchainName).toEqual('ETH');
            });
        });

        describe('should be null if there is no available blockchains for deploying', () => {
            it('all is false => null', () => {
                const disabledServicesConfig = {
                    depositDisabled: false,
                    withdrawalsDisabled: false,
                    deployDisabled: false,
                    blockchainDeployStatus: {
                        ETH: false,
                        BNB: false,
                        MINTME: false,
                    },
                };
                const wrapper = mockTokenDeploy(
                    false,
                    true,
                    'not-deployed',
                    disabledServicesConfig
                );

                expect(wrapper.vm.blockchainContext).toBe(null);
            });
        });
    });

    describe('isDeploymentDisabled computed property', () => {
        it('should be true if all blockchains are disabled', () => {
            const disabledServicesConfig = {
                depositDisabled: false,
                withdrawalsDisabled: false,
                deployDisabled: false,
                blockchainDeployStatus: {
                    ETH: false,
                    BNB: false,
                    MINTME: false,
                },
                allServicesDisabled: false,
            };
            const wrapper = mockTokenDeploy(
                false,
                true,
                'not-deployed',
                disabledServicesConfig
            );

            expect(wrapper.vm.isDeploymentDisabled).toBe(true);
        });

        it('should be true if all services are disabled', () => {
            const disabledServicesConfig = {
                depositDisabled: false,
                withdrawalsDisabled: false,
                deployDisabled: false,
                blockchainDeployStatus: {
                    ETH: false,
                    BNB: true,
                    MINTME: true,
                },
                allServicesDisabled: true,
            };
            const wrapper = mockTokenDeploy(
                false,
                true,
                'not-deployed',
                disabledServicesConfig
            );

            expect(wrapper.vm.isDeploymentDisabled).toBe(true);
        });

        it('should be true if all deployment is disabled', () => {
            const disabledServicesConfig = {
                depositDisabled: false,
                withdrawalsDisabled: false,
                deployDisabled: true,
                blockchainDeployStatus: {
                    ETH: false,
                    BNB: true,
                    MINTME: true,
                },
                allServicesDisabled: false,
            };
            const wrapper = mockTokenDeploy(
                false,
                true,
                'not-deployed',
                disabledServicesConfig
            );

            expect(wrapper.vm.isDeploymentDisabled).toBe(true);
        });

        it('should be false if all statement are false', () => {
            const disabledServicesConfig = {
                depositDisabled: false,
                withdrawalsDisabled: false,
                deployDisabled: false,
                blockchainDeployStatus: {
                    ETH: false,
                    BNB: true,
                    MINTME: true,
                },
                allServicesDisabled: false,
            };
            const wrapper = mockTokenDeploy(
                false,
                true,
                'not-deployed',
                disabledServicesConfig
            );

            expect(wrapper.vm.isDeploymentDisabled).toBe(false);
        });
    });

    describe('services computed property should be properly parsed from json', () => {
        it('1', () => {
            const disabledServicesConfig = {
                depositDisabled: false,
                withdrawalsDisabled: false,
                deployDisabled: false,
                blockchainDeployStatus: {
                    ETH: false,
                    BNB: false,
                    MINTME: false,
                },
                allServicesDisabled: false,
            };
            const wrapper = mockTokenDeploy(
                false,
                true,
                'not-deployed',
                disabledServicesConfig
            );

            expect(wrapper.vm.services).toEqual({
                depositDisabled: false,
                withdrawalsDisabled: false,
                deployDisabled: false,
                blockchainDeployStatus: {
                    ETH: false,
                    BNB: false,
                    MINTME: false,
                },
                allServicesDisabled: false,
            });
        });

        it('2', () => {
            const disabledServicesConfig = {
                depositDisabled: false,
                withdrawalsDisabled: false,
                deployDisabled: false,
                blockchainDeployStatus: {
                    ETH: false,
                    BNB: false,
                    MINTME: false,
                },
                someNewConfigParameter: 12345,
            };
            const wrapper = mockTokenDeploy(
                false,
                true,
                'not-deployed',
                disabledServicesConfig
            );

            expect(wrapper.vm.services).toEqual({
                depositDisabled: false,
                withdrawalsDisabled: false,
                deployDisabled: false,
                blockchainDeployStatus: {
                    ETH: false,
                    BNB: false,
                    MINTME: false,
                },
                someNewConfigParameter: 12345,
            });
        });
    });

    describe('blockchainDeployStatus method', () => {
        describe('should properly return blockchain availability', () => {
            it('1', () => {
                const disabledServicesConfig = {
                    depositDisabled: false,
                    withdrawalsDisabled: false,
                    deployDisabled: false,
                    blockchainDeployStatus: {
                        ETH: false,
                        BNB: false,
                        MINTME: false,
                    },
                };
                const wrapper = mockTokenDeploy(
                    false,
                    true,
                    'not-deployed',
                    disabledServicesConfig
                );

                expect(wrapper.vm.blockchainDeployStatus('WEB')).toBe(false);
                expect(wrapper.vm.blockchainDeployStatus('ETH')).toBe(false);
                expect(wrapper.vm.blockchainDeployStatus('BNB')).toBe(false);
            });

            it('2', () => {
                const disabledServicesConfig = {
                    depositDisabled: false,
                    withdrawalsDisabled: false,
                    deployDisabled: false,
                    blockchainDeployStatus: {
                        ETH: false,
                        BNB: true,
                        MINTME: true,
                    },
                };
                const wrapper = mockTokenDeploy(
                    false,
                    true,
                    'not-deployed',
                    disabledServicesConfig
                );

                expect(wrapper.vm.blockchainDeployStatus('WEB')).toBe(true);
                expect(wrapper.vm.blockchainDeployStatus('ETH')).toBe(false);
                expect(wrapper.vm.blockchainDeployStatus('BNB')).toBe(true);
            });
        });
    });
    describe('selectedCurrency', () => {
        describe('should be properly set by default', () => {
            it('1', () => {
                const disabledServicesConfig = {
                    depositDisabled: false,
                    withdrawalsDisabled: false,
                    deployDisabled: false,
                    blockchainDeployStatus: {
                        ETH: false,
                        BNB: false,
                        MINTME: true,
                    },
                };
                const wrapper = mockTokenDeploy(
                    false,
                    true,
                    'not-deployed',
                    disabledServicesConfig
                );

                expect(wrapper.vm.selectedCurrency).toBe(wrapper.vm.availableCurrencies[0]);
            });
            it('2', () => {
                const disabledServicesConfig = {
                    depositDisabled: false,
                    withdrawalsDisabled: false,
                    deployDisabled: false,
                    blockchainDeployStatus: {
                        ETH: true,
                        BNB: true,
                        MINTME: false,
                    },
                };
                const wrapper = mockTokenDeploy(
                    false,
                    true,
                    'not-deployed',
                    disabledServicesConfig
                );

                expect(wrapper.vm.selectedCurrency).toBe(wrapper.vm.availableCurrencies[0]);
            });
            it('3', () => {
                const disabledServicesConfig = {
                    depositDisabled: false,
                    withdrawalsDisabled: false,
                    deployDisabled: false,
                    blockchainDeployStatus: {
                        ETH: false,
                        BNB: false,
                        MINTME: false,
                    },
                };
                const wrapper = mockTokenDeploy(
                    false,
                    true,
                    'not-deployed',
                    disabledServicesConfig
                );

                expect(wrapper.vm.selectedCurrency).toBe(null);
            });
        });
    });
});
