import {createLocalVue, mount} from '@vue/test-utils';
import TokenDeploy from '../../js/components/token/deploy/TokenDeploy';
import moxios from 'moxios';
import Vuex from 'vuex';
import makeOrder from '../../js/storage/modules/make_order';
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
function mockTokenDeploy(balanceFetched, isOwner = true, status = 'not-deployed', twofa = false) {
    const store = new Vuex.Store({
        modules: {makeOrder},
    });
    const wrapper = mount(TokenDeploy, {
        store,
        localVue: mockVue(),
        propsData: {
            name: 'foo',
            hasReleasePeriod: false,
            isOwner: isOwner,
            precision: 4,
            statusProp: status,
            twofa: twofa,
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
        wrapper.vm.hasReleasePeriod = true;
        expect(wrapper.find('.loading-spinner').exists()).to.be.true;
        wrapper.vm.balance = 999;
        wrapper.vm.webCost = 999;
        expect(wrapper.find('.loading-spinner').exists()).to.be.false;
    });

    it('show message that editing of token release period is needed', () => {
        const wrapper = mockTokenDeploy(true);
        const message = wrapper.find('.bg-info');
        expect(message.exists()).to.be.true;
        expect(message.text()).to.equal('Please edit token release period before deploying.');
    });

    it('should disabled button if the cost is higher than the balance or is deploying', () => {
        const wrapper = mockTokenDeploy(true);
        wrapper.vm.balance = 999;
        wrapper.vm.webCost = 99;
        wrapper.vm.deploying = false;
        expect(wrapper.vm.btnDisabled).to.be.false;

        wrapper.vm.balance = 999;
        wrapper.vm.webCost = 99;
        wrapper.vm.deploying = true;
        expect(wrapper.vm.btnDisabled).to.be.true;

        wrapper.vm.balance = 999;
        wrapper.vm.webCost = 9999;
        wrapper.vm.deploying = false;
        expect(wrapper.vm.btnDisabled).to.be.true;
    });

    it('should show exceed cost message if the cost is higher than the balance', () => {
        const wrapper = mockTokenDeploy(true);
        wrapper.vm.balance = 999;
        wrapper.vm.webCost = 99;
        expect(wrapper.vm.costExceed).to.be.false;

        wrapper.vm.balance = 999;
        wrapper.vm.webCost = 9999;
        expect(wrapper.vm.costExceed).to.be.true;
    });

    it('deploy() function should work correctly', (done) => {
        const wrapper = mockTokenDeploy(true, true, 'not-deployed');
        wrapper.vm.deploy();
        expect(wrapper.vm.status).to.deep.equal('not-deployed');

        moxios.stubRequest('token_deploy', {status: 200, response: true});

        moxios.wait(() => {
            expect(wrapper.vm.status).to.deep.equal('pending');
            done();
        });
    });

    describe('fetchBalances() function', () => {
        it('should work correctly', (done) => {
            const wrapper = mockTokenDeploy(false);
            wrapper.vm.fetchBalances();
            expect(wrapper.vm.balance).to.deep.equal(null);
            expect(wrapper.vm.webCost).to.deep.equal(null);

            moxios.stubRequest('token_deploy_balances', {status: 200, response: {
                balance: 999,
                webCost: 99,
                }});

            moxios.wait(() => {
                expect(wrapper.vm.balance).to.deep.equal(999);
                expect(wrapper.vm.webCost).to.deep.equal(99);
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
                expect(wrapper.vm.balance).to.deep.equal(999);
                expect(wrapper.vm.webCost).to.deep.equal(99);
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
                expect(wrapper.vm.balance).to.deep.equal(0);
                expect(wrapper.vm.webCost).to.deep.equal(0);
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
                expect(wrapper.vm.balance).to.deep.equal(0);
                expect(wrapper.vm.webCost).to.deep.equal(0);
                done();
            });
        });
    });

    describe('2fa modal', () => {
        it('is displayed after submit if 2fa is enabled', () => {
            const wrapper = mockTokenDeploy(true, true, 'not-deployed', true);
            wrapper.vm.hasReleasePeriod = true;
            expect(wrapper.vm.showTwoFactorModal).to.deep.equal(false);
            wrapper.find('.btn-primary').trigger('click');
            expect(wrapper.vm.showTwoFactorModal).to.deep.equal(true);
        });


        it('is not displayed after submit if 2fa is disabled', () => {
            const wrapper = mockTokenDeploy(true, true, 'not-deployed', false);
            wrapper.vm.hasReleasePeriod = true;
            expect(wrapper.vm.showTwoFactorModal).to.deep.equal(false);
            wrapper.find('.btn-primary').trigger('click');
            expect(wrapper.vm.showTwoFactorModal).to.deep.equal(false);
        });
    });
});
