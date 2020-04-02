import {createLocalVue, mount} from '@vue/test-utils';
import ApiKeys from '../../js/components/ApiKeys';
import moxios from 'moxios';
import axiosPlugin from '../../js/axios';

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.use(axiosPlugin);
    localVue.use({
        install(Vue, options) {
            Vue.prototype.$routing = {generate: (val) => val};
        },
    });
    return localVue;
}

describe('ApiKeys', () => {
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
        const wrapper = mount(ApiKeys, {
            localVue: mockVue(),
            propsData: {
                apiKeys,
            },
        });

        expect(wrapper.vm.keys.publicKey).to.be.equal(apiKeys.publicKey);
        expect(wrapper.vm.keys.plainPrivateKey).to.be.equal(apiKeys.plainPrivateKey);

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
            expect(wrapper.vm.keys.publicKey).to.be.equal(newApiKeys.publicKey);
            expect(wrapper.vm.keys.plainPrivateKey).to.be.equal(newApiKeys.plainPrivateKey);
            done();
        });
    });

    it('removes keys', (done) => {
        const apiKeys = {
            publicKey: 'foo',
            plainPrivateKey: 'bar',
        };
        const wrapper = mount(ApiKeys, {
            localVue: mockVue(),
            propsData: {
                apiKeys,
            },
        });

        moxios.stubRequest('delete_keys', {
            status: 203,
        });

        wrapper.vm.invalidate();

        moxios.wait(() => {
            expect(wrapper.vm.keys).to.deep.equal({});
            expect(wrapper.vm.existed).to.be.false;
            done();
        });
    });
});
