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

    it('releasedDisabled returns true', (done) => {
        const localVue = mockVue();
        const wrapper = mount(TokenReleasePeriod, {
            localVue,
            propsData: {isTokenExchanged: true},
        });

        moxios.stubRequest('lock-period', {status: 200, response: {releasePeriod: 10}});

        moxios.wait(() => {
            expect(wrapper.vm.releasedDisabled).to.equal(true);
            done();
        });
    });

    it('releasedDisabled returns false', () => {
        const localVue = mockVue();
        const wrapper = mount(TokenReleasePeriod, {localVue});

        expect(wrapper.vm.releasedDisabled).to.equal(false);
    });
});
