import {createLocalVue, shallowMount} from '@vue/test-utils';
import TokenFacebookAddress from '../../js/components/token/facebook/TokenFacebookAddress';
import moxios from 'moxios';
import axios from 'axios';

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.component('b-tooltip', {});
    localVue.use({
        install(Vue) {
            Vue.prototype.$axios = {single: axios};
            Vue.prototype.$routing = {generate: (val) => val};
            Vue.prototype.$t = (val) => val;
        },
    });
    return localVue;
}

describe('TokenFacebookAddress', () => {
    beforeEach(() => {
        moxios.install();
    });

    afterEach(() => {
        moxios.uninstall();
    });

    it('should be equal "Add Facebook address" when address props is blank', () => {
        const wrapper = shallowMount(TokenFacebookAddress, {
            localVue: mockVue(),
            propsData: {
                address: '',
                appId: 'foo id',
                tokenName: 'foo token name',
           },
        });
        expect(wrapper.vm.computedAddress).toBe('token.facebook.empty_address');
    });

    it('should equal to address props when address props is not empty', () => {
        const wrapper = shallowMount(TokenFacebookAddress, {
            localVue: mockVue(),
            propsData: {
                address: 'foo address',
                appId: 'foo id',
                tokenName: 'foo token name',
            },
        });
        expect(wrapper.vm.computedAddress).toBe('foo address');
    });

    it('should be blank when pages array is empty', () => {
        const wrapper = shallowMount(TokenFacebookAddress, {
            localVue: mockVue(),
            propsData: {
                address: '',
                appId: 'foo id',
                tokenName: 'foo token name',
            },
        });
        expect(wrapper.vm.selectedUrl).toBe('');
    });

    it('should select index zero of pages if pages props not empty', () => {
        const wrapper = shallowMount(TokenFacebookAddress, {
            localVue: mockVue(),
            data() {
                return {
                    pages: [{link: 'foo.com', name: 'foo name'}],
                };
            },
            stubs: {
                Modal: {template: '<div><slot name="body"></slot></div>'},
            },
            propsData: {
                address: '',
                appId: 'foo id',
                tokenName: 'foo token name',
            },
        });
        expect(wrapper.vm.selectedUrl).toBe('foo.com');
        expect(wrapper.find('option').attributes('value')).toBe('foo.com');
        expect(wrapper.html().includes('foo name')).toBe(true);
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
            status: 200,
        });

        moxios.wait(() => {
            expect(wrapper.vm.showConfirmModal).toBe(false);
            expect(wrapper.vm.submitting).toBe(false);
            expect(wrapper.emitted('saveFacebook').length).toBe(1);
            done();
        });
    });

    it('call saveFacebookAddress() when savePage() is called', () => {
        const wrapper = shallowMount(TokenFacebookAddress, {
            localVue: mockVue(),
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

        expect(wrapper.emitted('savePageTest').length).toBe(1);
    });

    it('call saveFacebookAddress() when deleteAddress() is called', () => {
        const wrapper = shallowMount(TokenFacebookAddress, {
            localVue: mockVue(),
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

        expect(wrapper.vm.selectedUrl).toBe('');
        expect(wrapper.emitted('deleteAddressTest').length).toBe(1);
    });
});
