import {mount, createLocalVue} from '@vue/test-utils';
import TokenWebsiteAddress from '../../js/components/token/TokenWebsiteAddress';
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

describe('TokenWebsiteAddress', () => {
    beforeEach(() => {
        moxios.install();
    });

    afterEach(() => {
        moxios.uninstall();
    });

    it('save correct link', (done) => {
        const localVue = mockVue();
        const wrapper = mount(TokenWebsiteAddress, {
            localVue,
            data: {
                showWebsiteError: false,
                showConfirmWebsiteModal: false,
            },
            propsData: {
                editingWebsite: true,
                confirmWebsiteUrl: 'confirm_website',
            },
        });

        wrapper.find('input').setValue('https://example.com');
        wrapper.vm.checkWebsiteUrl();
        expect(wrapper.vm.showWebsiteError).to.equal(false);
        expect(wrapper.vm.showConfirmWebsiteModal).to.equal(true);
        wrapper.vm.confirmWebsite();

        moxios.stubRequest('confirm_website', {
            response: {verified: true},
        });

        moxios.wait(() => {
            expect(wrapper.emitted().saveWebsite[0]).to.deep.equal(['https://example.com']);
            done();
        });
    });

    it('do not save incorrect link', () => {
        const localVue = mockVue();
        const wrapper = mount(TokenWebsiteAddress, {
            localVue,
            data: {
                showWebsiteError: false,
            },
            propsData: {
                editingWebsite: true,
            },
        });

        wrapper.find('input').setValue('incorrect_link');
        wrapper.vm.checkWebsiteUrl();

        expect(wrapper.vm.showWebsiteError).to.equal(true);
    });

    it('show invitation text when link is not specified', () => {
        const localVue = mockVue();
        const wrapper = mount(TokenWebsiteAddress, {
            localVue,
            propsData: {
                editingWebsite: false,
            },
        });
        expect(wrapper.find('#website-link').text()).to.equal('Add Website');
    });

    it('show link when specified', () => {
        const localVue = mockVue();
        const wrapper = mount(TokenWebsiteAddress, {
            localVue,
            propsData: {
                currentWebsite: 'https://example.com',
                editingWebsite: false,
            },
        });
        expect(wrapper.find('#website-link').text()).to.equal(wrapper.vm.currentWebsite);
    });

    it('show truncated link when and too long', () => {
        const localVue = mockVue();
        const wrapper = mount(TokenWebsiteAddress, {
            localVue,
            propsData: {
                currentWebsite: 'https://example.com'.padEnd(100, '0'),
                editingWebsite: false,
            },
        });
        expect(wrapper.find('#website-link').text()).to.equal('https://example.com0000000000000000..');
    });
});
