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
        it('returns true if token is exchanged and has lockin even if not deployed', (done) => {
            const localVue = mockVue();
            const wrapper = mount(TokenReleasePeriod, {
                localVue,
                propsData: {
                    isTokenExchanged: true,
                    isTokenNotDeployed: true,
                    hasLockin: true,
                },
            });

            moxios.stubRequest('lock-period', {status: 200, response: {releasePeriod: 10}});

            moxios.wait(() => {
                expect(wrapper.vm.releasedDisabled).to.equal(true);
                done();
            });
        });

        it('returns true if token deployed even if not exchanged', (done) => {
            const localVue = mockVue();
            const wrapper = mount(TokenReleasePeriod, {
                localVue,
                propsData: {
                    isTokenExchanged: false,
                    isTokenNotDeployed: false,
                    hasLockin: true,
                },
            });

            moxios.stubRequest('lock-period', {status: 200, response: {releasePeriod: 10}});

            moxios.wait(() => {
                expect(wrapper.vm.releasedDisabled).to.equal(true);
                done();
            });
        });

        it('returns false if token not exchanged and not deployed', () => {
            const localVue = mockVue();
            const wrapper = mount(TokenReleasePeriod, {
                localVue,
                propsData: {
                    isTokenExchanged: false,
                    isTokenNotDeployed: true,
                    hasLockin: true,
                },
            });

            expect(wrapper.vm.releasedDisabled).to.equal(false);
        });

        it('returns false if token doesn\'t have lockin and is not deployed, even if it is exchanged', () => {
            const localVue = mockVue();
            const wrapper = mount(TokenReleasePeriod, {
                localVue,
                propsData: {
                    isTokenExchanged: true,
                    isTokenNotDeployed: true,
                    hasLockin: false,
                },
            });

            expect(wrapper.vm.releasedDisabled).to.equal(false);
        });
    });

    describe('releasePeriodDisabled', () => {
        it('returns true if token deployed or pending', (done) => {
            const localVue = mockVue();
            const wrapper = mount(TokenReleasePeriod, {
                localVue,
                propsData: {
                    isTokenExchanged: true,
                    isTokenNotDeployed: false,
                },
            });

            moxios.stubRequest('lock-period', {status: 200, response: {releasePeriod: 10}});

            moxios.wait(() => {
                expect(wrapper.vm.releasePeriodDisabled).to.equal(true);
                done();
            });
        });

        it('returns false if not deployed', () => {
            const localVue = mockVue();
            const wrapper = mount(TokenReleasePeriod, {
                localVue,
                propsData: {
                    isTokenExchanged: true,
                    isTokenNotDeployed: true,
                },
            });

            expect(wrapper.vm.releasePeriodDisabled).to.equal(false);
        });
    });
});
