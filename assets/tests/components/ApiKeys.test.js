import {createLocalVue, shallowMount} from '@vue/test-utils';
import ApiKeys from '../../js/components/ApiKeys';
import moxios from 'moxios';
import axios from 'axios';

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.use({
        install(Vue, options) {
            Vue.prototype.$routing = {generate: (val) => val};
            Vue.prototype.$axios = {retry: axios, single: axios};
            Vue.prototype.$t = (val) => val;
            Vue.prototype.$logger = {error: () => {}};
        },
    });
    return localVue;
}

/**
 * @param {Object} propsData
 * @param {Object} data
 * @return {Wrapper<Vue>}
 */
function createWrapper(propsData = {}, data = {}) {
    return shallowMount(ApiKeys, {
        localVue: mockVue(),
        propsData,
        data: () => data,
    });
}

describe('ApiKey', () => {
    beforeEach(() => {
        moxios.install();
    });

    afterEach(() => {
        moxios.uninstall();
    });

    it('generates keys', (done) => {
        const apiKeys = {
            publicKey: 'foo',
            plainPrivateKey: 'bar',
        };
        const wrapper = createWrapper({apiKeys}, {});

        expect(wrapper.vm.keys.publicKey).toBe(apiKeys.publicKey);
        expect(wrapper.vm.keys.plainPrivateKey).toBe(apiKeys.plainPrivateKey);

        const newApiKeys = {
            publicKey: 'baz',
            plainPrivateKey: 'qux',
        };

        moxios.stubRequest('post_keys', {
            status: 201,
            response: newApiKeys,
        });

        wrapper.vm.generate();

        moxios.wait(() => {
            expect(wrapper.vm.keys.publicKey).toBe(newApiKeys.publicKey);
            expect(wrapper.vm.keys.plainPrivateKey).toBe(newApiKeys.plainPrivateKey);
            done();
        });
    });

    it('removes keys', (done) => {
        const apiKeys = {
            publicKey: 'foo',
            plainPrivateKey: 'bar',
        };

        const wrapper = createWrapper({apiKeys}, {});

        moxios.stubRequest('delete_keys', {
            status: 203,
        });

        wrapper.vm.invalidate();

        moxios.wait(() => {
            expect(wrapper.vm.keys).toMatchObject({});
            expect(wrapper.vm.existed).toBe(false);
            done();
        });
    });
});
