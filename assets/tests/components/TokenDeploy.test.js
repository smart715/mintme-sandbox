import {createLocalVue, shallowMount} from '@vue/test-utils';
import TokenDeploy from '../../js/components/token/deploy/TokenDeploy';
import moxios from 'moxios';
import Vuex from 'vuex';
import tradeBalance from '../../js/storage/modules/trade_balance';
import Axios from '../../js/axios';
import axios from 'axios';

/**
 * @return {VueConstructor}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.use(Axios);
    localVue.use(Vuex);
    localVue.use({
        install(Vue, options) {
            Vue.prototype.$axios = {retry: axios, single: axios};
            Vue.prototype.$routing = {generate: (val) => val};
            Vue.prototype.$toasted = {info: (val) => val};
            Vue.prototype.$toasted = {show: () => {}};
            Vue.prototype.$t = (val) => val;
        },
    });

    return localVue;
}

/**
 * @param {Boolean} balanceFetched
 * @param {Boolean} isOwner
 * @param {String} status
 * @param {Boolean} twofa
 * @return {Wrapper<Vue>}
 */
function mockTokenDeploy(balanceFetched, isOwner = true, status = 'not-deployed') {
    const localVue = mockVue();
    localVue.use(Axios);
    const store = new Vuex.Store({
        modules: {
            tradeBalance,
            websocket: {
                namespaced: true,
                actions: {
                    addMessageHandler: () => {},
                },
            },
        },
    });
    const wrapper = shallowMount(TokenDeploy, {
        store,
        localVue,
        propsData: {
            name: 'foo',
            hasReleasePeriod: false,
            isOwner: isOwner,
            precision: 4,
            statusProp: status,
            websocketUrl: '',
            disabledServicesConfig: '{"depositDisabled":false,"withdrawalsDisabled":false,"deployDisabled":false}',
        },
    });

    if (balanceFetched) {
        wrapper.vm.balances = {WEB: 999999};
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

    it('show loading icon if balances not fetched yet', () => {
        const wrapper = mockTokenDeploy(false);
        wrapper.setProps({hasReleasePeriod: true});
        expect(wrapper.find('.loading-spinner').exists()).toBe(true);
        wrapper.vm.balances = {};
        wrapper.vm.costs = {};
        expect(wrapper.find('.loading-spinner').exists()).toBe(false);
    });

    it('show message that editing of token release period is needed', () => {
        const wrapper = mockTokenDeploy(true);
        const message = wrapper.find('.bg-info');
        expect(message.exists()).toBe(true);
        expect(message.text()).toBe('token.deploy.edit_release_period');
    });

    describe('deploy btn', () => {
        it('should disable it if the cost is higher than the balance', () => {
            const wrapper = mockTokenDeploy(true);
            wrapper.vm.balances = {WEB: 999};
            wrapper.vm.costs = {WEB: 9999};
            wrapper.vm.deploying = false;
            expect(wrapper.vm.btnDisabled).toBe(true);
        });

        it('should disabled it if is deploying', () => {
            const wrapper = mockTokenDeploy(true);
            wrapper.vm.deploying = false;
            expect(wrapper.vm.btnDisabled).toBe(false);

            wrapper.vm.deploying = true;
            expect(wrapper.vm.btnDisabled).toBe(true);
        });
    });

    it('should show exceed cost message if the cost is higher than the balance', () => {
        const wrapper = mockTokenDeploy(true);
        wrapper.vm.balances = {WEB: 999};
        wrapper.vm.costs = {WEB: 99};
        expect(wrapper.vm.costExceed).toBe(false);

        wrapper.vm.balances = {WEB: 999};
        wrapper.vm.costs = {WEB: 9999};
        expect(wrapper.vm.costExceed).toBe(true);
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

    describe('fetchBalances() function', () => {
        it('should work correctly', (done) => {
            const wrapper = mockTokenDeploy(false);
            wrapper.vm.fetchBalances();
            expect(wrapper.vm.balances).toBe(null);
            expect(wrapper.vm.costs).toBe(null);

            moxios.stubRequest('token_deploy_balances', {status: 200, response: {
                balances: {WEB: {available: 999}},
                costs: {WEB: 99},
                },
            });

            moxios.wait(() => {
                expect(wrapper.vm.balance).toBe(999);
                expect(wrapper.vm.cost).toBe(99);
                done();
            });
        });

        it('should be called on mounted if isOwner and token is not deployed', (done) => {
            moxios.stubRequest('token_deploy_balances', {status: 200, response: {
                    balances: {WEB: {available: 999}},
                    costs: {WEB: 99},
                }});

            let wrapper = mockTokenDeploy(false, true, 'not-deployed');
            moxios.wait(() => {
                expect(wrapper.vm.balance).toBe(999);
                expect(wrapper.vm.cost).toBe(99);
                done();
            });
        });

        it('should not be called on mounted if isOwner and token is deployed', (done) => {
            moxios.stubRequest('token_deploy_balances', {status: 200, response: {
                    balances: {WEB: {available: 999}},
                    costs: {WEB: 99},
                }});

            let wrapper = mockTokenDeploy(false, true, 'deployed');
            moxios.wait(() => {
                expect(wrapper.vm.balance).toBe(0);
                expect(wrapper.vm.cost).toBe(0);
                done();
            });
        });

        it('should not be called on mounted if is not owner', (done) => {
            moxios.stubRequest('token_deploy_balances', {status: 200, response: {
                    balances: {WEB: {available: 999}},
                    costs: {WEB: 99},
                },
            });

            let wrapper = mockTokenDeploy(false, false, 'not-deployed');
            moxios.wait(() => {
                expect(wrapper.vm.balance).toBe(0);
                expect(wrapper.vm.cost).toBe(0);
                done();
            });
        });
    });
});
