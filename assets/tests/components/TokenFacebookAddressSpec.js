import {createLocalVue, shallowMount} from '@vue/test-utils';
import TokenFacebookAddress from '../../js/components/token/facebook/TokenFacebookAddress';
import moxios from 'moxios';
import axios from 'axios';

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.use(axios);
    localVue.use({
        install(Vue) {
            Vue.prototype.$axios = {single: axios};
            Vue.prototype.$routing = {generate: (val) => val};
        },
    });
    return localVue;
};

let truncateTest = function(val, max) {
    return val.length > max ? val.slice(0, max) + '...' : val;
};

describe('TokenFacebookAddress', () => {
    beforeEach(() => {
        moxios.install();
    });

    afterEach(() => {
        moxios.uninstall();
    });

    const $routing = {generate: (val) => val};

    it('should be equal "Add Facebook address" when address props is blank', () => {
        const wrapper = shallowMount(TokenFacebookAddress, {
            mocks: {
                $routing,
            },
            propsData: {
                address: '',
                appId: 'foo id',
                tokenName: 'foo token name',
           },
        });
        expect(wrapper.vm.computedAddress).to.be.equal('Add Facebook address');
    });

    it('should equal to address props when address props is not empty', () => {
        const wrapper = shallowMount(TokenFacebookAddress, {
            mocks: {
                $routing,
            },
            propsData: {
                address: 'foo address',
                appId: 'foo id',
                tokenName: 'foo token name',
            },
        });
        expect(wrapper.vm.computedAddress).to.be.equal('foo address');
    });

    it('should truncate long address in the address field', () => {
        const wrapper = shallowMount(TokenFacebookAddress, {
            filters: {
                truncate: function(val, max) {
                    return truncateTest(val, max);
                },
            },
            mocks: {
                $routing,
            },
            propsData: {
                address: 'FooAddressLength01234567890123456789012345',
                appId: 'foo id',
                tokenName: 'foo token name',
            },
        });
        expect(wrapper.html()).to.contain('FooAddressLength0123456789012345678...');
    });

    it('should be blank when pages array is empty', () => {
        const wrapper = shallowMount(TokenFacebookAddress, {
            mocks: {
                $routing,
            },
            propsData: {
                address: '',
                appId: 'foo id',
                tokenName: 'foo token name',
            },
        });
        expect(wrapper.vm.selectedUrl).to.be.deep.equal('');
    });

    it('should select index zero of pages if pages props not empty', () => {
        const wrapper = shallowMount(TokenFacebookAddress, {
            mocks: {
                $routing,
            },
            data() {
                return {
                    pages: [{link: 'foo.com', name: 'foo name'}],
                };
            },
            propsData: {
                address: '',
                appId: 'foo id',
                tokenName: 'foo token name',
            },
        });
        expect(wrapper.vm.selectedUrl).to.be.equal('foo.com');
        expect(wrapper.find('option').attributes('value')).to.be.equal('foo.com');
        expect(wrapper.html()).to.contain('foo name');
    });

    it('do $axios request and emit "saveFacebook" when the function saveFacebookAddress() is called', (done) => {
        const localVue = mockVue();
        const wrapper = shallowMount(TokenFacebookAddress, {
            localVue,
            methods: {
                notifySuccess: function(message) {
                    return false;
                },
            },
            propsData: {
                address: '',
                appId: 'foo id',
                tokenName: 'foo token name',
            },
        });
        wrapper.vm.saveFacebookAddress();

        moxios.stubRequest('token_update', {
            status: 202,
        });

        moxios.wait(() => {
            expect(wrapper.vm.showConfirmModal).to.be.false;
            expect(wrapper.vm.submitting).to.be.false;
            expect(wrapper.emitted('saveFacebook').length).to.be.equal(1);
            done();
        });
    });

    it('call saveFacebookAddress() when savePage() is called', () => {
        const wrapper = shallowMount(TokenFacebookAddress, {
            mocks: {
                $routing,
            },
            methods: {
                saveFacebookAddress: function() {
                    wrapper.vm.$emit('savePageTest');
                },
            },
            propsData: {
                address: '',
                appId: 'foo id',
                tokenName: 'foo token name',
            },
        });
        wrapper.vm.savePage();

        expect(wrapper.emitted('savePageTest').length).to.be.equal(1);
    });

    it('call saveFacebookAddress() when deleteAddress() is called', () => {
        const wrapper = shallowMount(TokenFacebookAddress, {
            mocks: {
                $routing,
            },
            methods: {
                saveFacebookAddress: function() {
                    wrapper.vm.$emit('deleteAddressTest');
                },
            },
            propsData: {
                address: '',
                appId: 'foo id',
                tokenName: 'foo token name',
            },
        });
        wrapper.vm.selectedUrl = 'foo.com';
        wrapper.vm.deleteAddress();

        expect(wrapper.vm.selectedUrl).to.be.deep.equal('');
        expect(wrapper.emitted('deleteAddressTest').length).to.be.equal(1);
    });
});
