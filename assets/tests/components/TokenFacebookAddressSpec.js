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
                appId: 'fooId',
                tokenName: 'fooTokenName',
           },
        });
        expect(wrapper.vm.computedAddress).to.be.equal('Add Facebook address');
    });

    it('should be equal "Add Test address" when address props is not blank', () => {
        const wrapper = shallowMount(TokenFacebookAddress, {
            mocks: {
                $routing,
            },
            propsData: {
                address: 'Add Test address',
                appId: 'fooId',
                tokenName: 'fooTokenName',
            },
        });
        expect(wrapper.vm.computedAddress).to.be.equal('Add Test address');
    });

    it('should be contain "AddressTestLength012345678901234567..." in the address field', () => {
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
                address: 'AddressTestLength012345678901234567890',
                appId: 'fooId',
                tokenName: 'fooTokenName',
            },
        });
        expect(wrapper.html()).to.contain('AddressTestLength012345678901234567...');
    });

    it('should be blank when pages array is empty', () => {
        const wrapper = shallowMount(TokenFacebookAddress, {
            mocks: {
                $routing,
            },
            propsData: {
                address: '',
                appId: 'fooId',
                tokenName: 'fooTokenName',
            },
        });
        expect(wrapper.vm.selectedUrl).to.be.deep.equal('');
    });

    it('should be contain "test_pages_name" and "test_pages_link" when pages array props contains value', () => {
        const wrapper = shallowMount(TokenFacebookAddress, {
            mocks: {
                $routing,
            },
            data() {
                return {
                    pages: [{link: 'test_pages_link', name: 'test_pages_name'}],
                };
            },
            propsData: {
                address: '',
                appId: 'fooId',
                tokenName: 'fooTokenName',
            },
        });
        expect(wrapper.vm.selectedUrl).to.be.equal('test_pages_link');
        expect(wrapper.find('option').attributes('value')).to.be.equal('test_pages_link');
        expect(wrapper.html()).to.contain('test_pages_name');
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
                appId: 'fooId',
                tokenName: 'fooTokenName',
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

    it('emit "savePageTest" when the function savePage() is called', () => {
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
                appId: 'fooId',
                tokenName: 'fooTokenName',
            },
        });
        wrapper.vm.savePage();

        expect(wrapper.emitted('savePageTest').length).to.be.equal(1);
    });

    it('emit "deleteAddressTest" when the function deleteAddress() is called', () => {
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
                appId: 'fooId',
                tokenName: 'fooTokenName',
            },
        });
        wrapper.vm.selectedUrl = 'foo';
        wrapper.vm.deleteAddress();

        expect(wrapper.vm.selectedUrl).to.be.deep.equal('');
        expect(wrapper.emitted('deleteAddressTest').length).to.be.equal(1);
    });
});
