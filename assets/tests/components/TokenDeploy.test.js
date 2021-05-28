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
        wrapper.vm.balance = 999999;
        wrapper.vm.webCost = 999;
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
        wrapper.setData({
            webCost: 999,
            balance: 999,
        });
        wrapper.vm.balance = 999;
        wrapper.vm.webCost = 999;
        expect(wrapper.find('.loading-spinner').exists()).toBe(false);
    });

    it('show message that editing of token release period is needed', () => {
        const wrapper = mockTokenDeploy(true);
        const message = wrapper.find('.bg-info');
        expect(message.exists()).toBe(true);
        expect(message.text()).toBe('token.deploy.edit_release_period');
    });

    it('should disabled button if the cost is higher than the balance or is deploying', () => {
        const wrapper = mockTokenDeploy(true);
        wrapper.vm.balance = 999;
        wrapper.vm.webCost = 99;
        wrapper.vm.deploying = false;
        expect(wrapper.vm.btnDisabled).toBe(false);

        wrapper.vm.balance = 999;
        wrapper.vm.webCost = 99;
        wrapper.vm.deploying = true;
        expect(wrapper.vm.btnDisabled).toBe(true);

        wrapper.vm.balance = 999;
        wrapper.vm.webCost = 9999;
        wrapper.vm.deploying = false;
        expect(wrapper.vm.btnDisabled).toBe(true);
    });

    it('should show exceed cost message if the cost is higher than the balance', () => {
        const wrapper = mockTokenDeploy(true);
        wrapper.vm.balance = 999;
        wrapper.vm.webCost = 99;
        expect(wrapper.vm.costExceed).toBe(false);

        wrapper.vm.balance = 999;
        wrapper.vm.webCost = 9999;
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
            expect(wrapper.vm.balance).toBe(null);
            expect(wrapper.vm.webCost).toBe(null);

            moxios.stubRequest('token_deploy_balances', {status: 200, response: {
                balance: 999,
                webCost: 99,
                }});

            moxios.wait(() => {
                expect(wrapper.vm.balance).toBe(999);
                expect(wrapper.vm.webCost).toBe(99);
                done();
            });
        });

        it('should be called on mounted if isOwner and token is not deployed', (done) => {
            moxios.stubRequest('token_deploy_balances', {status: 200, response: {
                    balance: 999,
                    webCost: 99,
                }});

            let wrapper = mockTokenDeploy(false, true, 'not-deployed');
            moxios.wait(() => {
                expect(wrapper.vm.balance).toBe(999);
                expect(wrapper.vm.webCost).toBe(99);
                done();
            });
        });

        it('should not be called on mounted if isOwner and token is deployed', (done) => {
            moxios.stubRequest('token_deploy_balances', {status: 200, response: {
                    balance: 999,
                    webCost: 99,
                }});

            let wrapper = mockTokenDeploy(false, true, 'deployed');
            moxios.wait(() => {
                expect(wrapper.vm.balance).toBe(0);
                expect(wrapper.vm.webCost).toBe(0);
                done();
            });
        });

        it('should not be called on mounted if is not owner', (done) => {
            moxios.stubRequest('token_deploy_balances', {status: 200, response: {
                    balance: 999,
                    webCost: 99,
                }});

            let wrapper = mockTokenDeploy(false, false, 'not-deployed');
            moxios.wait(() => {
                expect(wrapper.vm.balance).toBe(0);
                expect(wrapper.vm.webCost).toBe(0);
                done();
            });
        });
    });
});
