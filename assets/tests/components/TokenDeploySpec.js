import {createLocalVue, mount} from '@vue/test-utils';
import TokenDeploy from '../../js/components/token/TokenDeploy';
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
 * @param {Boolean} deployed
 * @return {Wrapper<Vue>}
 */
function mockTokenDeploy(balanceFetched, isOwner = true, deployed = false) {
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
            deployedProp: deployed,
            usdCost: 49,
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

    it('should be not exist if balances not fetched yet', () => {
        const wrapper = mockTokenDeploy(false);
        expect(wrapper.find('div').exists()).to.be.false;
        wrapper.vm.balance = 999;
        wrapper.vm.webCost = 999;
        expect(wrapper.find('div').exists()).to.be.true;
    });

    it('should be hidden if not owner & not deployed', () => {
        const wrapper = mockTokenDeploy(true);
        wrapper.vm.webCost = 999;
        wrapper.vm.isOwner = false;
        wrapper.vm.deployed = false;
        expect(wrapper.find('button').exists()).to.be.false;
        expect(wrapper.find('.deployed-icon').exists()).to.be.false;
    });

    it('should has deployed icon if deployed', () => {
        const wrapper = mockTokenDeploy(true);
        wrapper.vm.deployed = true;
        wrapper.vm.isOwner = true;
        expect(wrapper.find('button').exists()).to.be.false;
        expect(wrapper.find('.deployed-icon').exists()).to.be.true;
        wrapper.vm.isOwner = false;
        expect(wrapper.find('button').exists()).to.be.false;
        expect(wrapper.find('.deployed-icon').exists()).to.be.true;
    });

    describe('button', () => {
        it('should be visible if not deployed & isOwner', () => {
            const wrapper = mockTokenDeploy(true);
            wrapper.vm.isOwner = true;
            wrapper.vm.deployed = false;
            expect(wrapper.find('button').exists()).to.be.true;
        });

        it('should not open modal if toke has not release period', () => {
            const wrapper = mockTokenDeploy(true);
            wrapper.vm.hasReleasePeriod = false;
            wrapper.vm.setModalVisible(true);
            expect(wrapper.vm.modalVisible).to.be.false;
            wrapper.vm.hasReleasePeriod = true;
            wrapper.vm.setModalVisible(true);
            expect(wrapper.vm.modalVisible).to.be.true;
        });
    });

    describe('modal', () => {
        it('should disabled button if the cost is higher than the balance or is deploying', () => {
            const wrapper = mockTokenDeploy(true);
            wrapper.vm.balance = 999;
            wrapper.vm.webCost = 99;
            wrapper.vm.deploying = false;
            expect(wrapper.vm.btnModalDisabled).to.be.false;

            wrapper.vm.balance = 999;
            wrapper.vm.webCost = 99;
            wrapper.vm.deploying = true;
            expect(wrapper.vm.btnModalDisabled).to.be.true;

            wrapper.vm.balance = 999;
            wrapper.vm.webCost = 9999;
            wrapper.vm.deploying = false;
            expect(wrapper.vm.btnModalDisabled).to.be.true;
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
    });

    describe('deploy() function', () => {
        it('should work correctly', (done) => {
            const wrapper = mockTokenDeploy(true, true, false);
            wrapper.vm.modalVisible = true;
            wrapper.vm.deploy();
            expect(wrapper.vm.deployed).to.be.false;

            moxios.stubRequest('token_deploy', {status: 200, response: true});

            moxios.wait(() => {
                expect(wrapper.vm.deployed).to.be.true;
                done();
            });
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

            let wrapper = mockTokenDeploy(false, true, false);
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

            let wrapper = mockTokenDeploy(false, true, true);
            moxios.wait(() => {
                expect(wrapper.vm.balance).to.deep.equal(null);
                expect(wrapper.vm.webCost).to.deep.equal(null);
                done();
            });
        });

        it('should not be called on mounted if is not owner', (done) => {
            moxios.stubRequest('token_deploy_balances', {status: 200, response: {
                    balance: 999,
                    webCost: 99,
                }});

            let wrapper = mockTokenDeploy(false, false, false);
            moxios.wait(() => {
                expect(wrapper.vm.balance).to.deep.equal(null);
                expect(wrapper.vm.webCost).to.deep.equal(null);
                done();
            });
        });
    });
});
