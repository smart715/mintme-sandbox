import {mount, createLocalVue} from '@vue/test-utils';
import TokenReleasePeriod from '../../js/components/token/TokenReleasePeriod';
import moxios from 'moxios';
import axios from 'axios';

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.use(axios);
    localVue.use({
        install(Vue, options) {
            Vue.prototype.$axios = {retry: axios, single: axios};
            Vue.prototype.$routing = {generate: (val) => val};
        },
    });
    return localVue;
}

describe('TokenReleasePeriod', () => {
    beforeEach(() => {
        moxios.install();
    });

    afterEach(() => {
        moxios.uninstall();
    });

    describe('releasedDisabled', () => {
        it('returns true if token is exchanged even if not deployed', (done) => {
            const localVue = mockVue();
            const wrapper = mount(TokenReleasePeriod, {
                localVue,
                propsData: {
                    isTokenExchanged: true,
                    isTokenDeployed: false,
                },
            });

            moxios.stubRequest('lock-period', {status: 200, response: {released: 10}});

            moxios.wait(() => {
                expect(wrapper.vm.releasedDisabled).to.equal(false);
                done();
            });
        });

        it('returns true if token deployed even if not exchanged', (done) => {
            const localVue = mockVue();
            const wrapper = mount(TokenReleasePeriod, {
                localVue,
                propsData: {
                    isTokenExchanged: false,
                    isTokenDeployed: true,
                },
            });

            moxios.stubRequest('lock-period', {status: 200, response: {released: 10}});

            moxios.wait(() => {
                expect(wrapper.vm.releasedDisabled).to.equal(false);
                done();
            });
        });

        it('returns true if token not exchanged and not deployed', () => {
            const localVue = mockVue();
            const wrapper = mount(TokenReleasePeriod, {
                localVue,
                propsData: {
                    isTokenExchanged: false,
                    isTokenDeployed: false,
                },
            });

            expect(wrapper.vm.releasedDisabled).to.equal(false);
        });
    });

    describe('releasePeriodDisabled', () => {
        it('returns true if token not deployed and not exchanged', (done) => {
            const localVue = mockVue();
            const wrapper = mount(TokenReleasePeriod, {
                localVue,
                propsData: {
                    isTokenExchanged: false,
                    isTokenDeployed: false,
                },
            });

            moxios.stubRequest('lock-period', {status: 200, response: {releasePeriod: 10}});

            moxios.wait(() => {
                expect(wrapper.vm.releasePeriodDisabled).to.equal(false);
                done();
            });
        });

        it('returns true if not deployed', () => {
            const localVue = mockVue();
            const wrapper = mount(TokenReleasePeriod, {
                localVue,
                propsData: {
                    isTokenExchanged: true,
                    isTokenDeployed: false,
                },
            });

            expect(wrapper.vm.releasePeriodDisabled).to.equal(false);
        });
    });
});
