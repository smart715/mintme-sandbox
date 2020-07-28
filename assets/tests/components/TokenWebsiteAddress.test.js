import {mount, createLocalVue} from '@vue/test-utils';
import TokenWebsiteAddress from '../../js/components/token/website/TokenWebsiteAddress';
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

    it('open confirm dialog', () => {
        const localVue = mockVue();
        const wrapper = mount(TokenWebsiteAddress, {
            localVue,
            data: {
                showWebsiteError: false,
                showConfirmWebsiteModal: false,
            },
            propsData: {editingWebsite: true},
        });

        wrapper.find('input').setValue('https://example.com');
        wrapper.vm.checkWebsiteUrl();
        expect(wrapper.vm.showWebsiteError).toBe(false);
        expect(wrapper.vm.showConfirmWebsiteModal).toBe(true);
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

        expect(wrapper.vm.showWebsiteError).toBe(true);
    });

    it('show invitation text when link is not specified', () => {
        const localVue = mockVue();
        const wrapper = mount(TokenWebsiteAddress, {
            localVue,
            propsData: {
                editingWebsite: false,
            },
        });
        expect(wrapper.find('#website-link').text()).toBe('Add Website');
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
        expect(wrapper.find('#website-link').text()).toBe(wrapper.vm.currentWebsite);
    });
});
