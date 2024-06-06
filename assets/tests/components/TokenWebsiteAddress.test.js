import {shallowMount, createLocalVue} from '@vue/test-utils';
import TokenWebsiteAddress from '../../js/components/token/website/TokenWebsiteAddress';
import moxios from 'moxios';
import axios from 'axios';
import {MInput} from '../../js/components/UI';

const localVue = mockVue();

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.use({
        install(Vue, options) {
            Vue.prototype.$axios = {retry: axios, single: axios};
            Vue.prototype.$routing = {generate: (val) => val};
            Vue.prototype.$t = (val) => val;
            Vue.prototype.$toasted = {show: () => {}};
            Vue.prototype.$logger = {error: (val) => {}};
        },
    });

    return localVue;
}

/**
 * @param {Object} props
 * @return {Object}
 */
function createWebsiteAddressProps(props = {}) {
    return {
        currentWebsite: 'https://www.example.com',
        editingWebsite: true,
        tokenName: 'MySuperToken',
        ...props,
    };
}

const fakeParsedWebsite = 'https://example.com/very/long/website/with/many/symbols/more/then/85/symbols/more-than-85';

describe('TokenWebsiteAddress', () => {
    let wrapper;

    beforeEach(() => {
        wrapper = shallowMount(TokenWebsiteAddress, {
            localVue: localVue,
            propsData: createWebsiteAddressProps(),
        });

        moxios.install();
    });

    afterEach(() => {
        moxios.uninstall();
    });

    it('open confirm dialog', async () => {
        await wrapper.setData({
            showWebsiteError: false,
            showConfirmWebsiteModal: false,
        });

        wrapper.findComponent(MInput).vm.$emit('input', 'https://example.com');
        wrapper.vm.checkWebsiteUrl();

        expect(wrapper.vm.showWebsiteError).toBeFalsy();
        expect(wrapper.vm.showConfirmWebsiteModal).toBeTruthy();
    });

    it('do not save parsed incorrect link', async () => {
        await wrapper.setData({
            showWebsiteError: false,
        });

        wrapper.findComponent(MInput).vm.$emit('input', 'incorrect_link');
        wrapper.vm.checkWebsiteUrl();

        expect(wrapper.vm.showWebsiteError).toBe(true);
    });

    it('show invitation text when link is not specified', async () => {
        await wrapper.setProps({
            currentWebsite: '',
        });

        expect(wrapper.vm.computedWebsiteUrl).toBe('token.website.empty_address');
    });

    it('show link when specified', () => {
        expect(wrapper.vm.computedWebsiteUrl).toBe(wrapper.vm.currentWebsite);
    });

    it('should return true if website length less than 85', async () => {
        await wrapper.setData({
            websiteTruncateLength: 85,
            parsedWebsite: 'https://example.com',
        });

        expect(wrapper.vm.disabledTooltip).toBeTruthy();
    });

    it('should return a number for translation context', async () => {
        await wrapper.setData({
            websiteMaxLength: 85,
        });

        expect(wrapper.vm.translationContext).toEqual({maxWebsiteLength: 85});
    });

    it('should return false if website length greater than or equal 85', async () => {
        await wrapper.setData({
            websiteTruncateLength: 85,
            parsedWebsite: fakeParsedWebsite,
        });

        expect(wrapper.vm.disabledTooltip).toBeFalsy();
    });

    it('should return true if fileError.title and fileError.details are not empty', async () => {
        await wrapper.setData({
            fileError: {
                title: 'error',
                details: 'details',
            },
        });

        expect(wrapper.vm.fileErrorVisible).toBeTruthy();
    });

    it('should set showWebsiteError to false if newWebsite is empty', async () => {
        await wrapper.setData({
            showWebsiteError: true,
            newWebsite: '',
        });

        wrapper.vm.editWebsite();

        expect(wrapper.vm.showWebsiteError).toBeFalsy();
    });

    it('should call checkWebsiteUrl if newWebsite is not empty and not equal to websiteUrl', async () => {
        await wrapper.setData({
            showWebsiteError: false,
            newWebsite: 'https://example.com',
        });

        wrapper.vm.editWebsite();

        expect(wrapper.vm.showWebsiteError).toBeFalsy();
    });

    it('should set editing to false if editingWebsite is false', async () => {
        await wrapper.setProps({
            editingWebsite: false,
        });

        await wrapper.setData({
            editing: false,
        });

        expect(wrapper.vm.editing).toBeFalsy();
        expect(wrapper.vm.submitting).toBeFalsy();
    });

    it('should set newWebsite to null and call saveWebsite', async () => {
        await wrapper.setData({
            newWebsite: 'https://example.com',
        });

        wrapper.vm.deleteWebsite();

        expect(wrapper.vm.newWebsite).toBe('');
    });

    it('should set fileError to empty object and showConfirmWebsiteModal to true', async () => {
        await wrapper.setData({
            fileError: {
                title: 'error',
                details: 'details',
            },
            showConfirmWebsiteModal: false,
        });

        wrapper.vm.closeFileErrorModal();

        expect(wrapper.vm.fileError).toEqual({});
        expect(wrapper.vm.showConfirmWebsiteModal).toBeTruthy();
    });

    it('should set fileError to empty object', async () => {
        await wrapper.setData({
            fileError: {
                title: 'error',
                details: 'details',
            },
        });

        wrapper.vm.clearFileError();

        expect(wrapper.vm.fileError).toEqual({});
    });

    it('should set editing to true and emit toogleEdit', async () => {
        await wrapper.setData({
            editing: false,
        });

        wrapper.vm.toggleEdit();

        expect(wrapper.vm.editing).toBeTruthy();
        expect(wrapper.emitted().toggleEdit).toBeTruthy();
    });

    it('should set editing to false', async () => {
        await wrapper.setData({
            editing: true,
        });

        wrapper.vm.toggleEdit();

        expect(wrapper.vm.editing).toBeFalsy();
    });

    it('should return true if submiting its true', async () => {
        await wrapper.setData({
            submitting: true,
        });

        wrapper.vm.saveWebsite();

        expect(wrapper.vm.submitting).toBeTruthy();
    });

    it('should saveWebsite call notifySuccess if response.data.verified is true', async (done) => {
        await wrapper.setData({
            confirmWebsiteUrl: 'https://example.com',
        });

        moxios.stubRequest(wrapper.vm.confirmWebsiteUrl, {
            status: 200,
            response: {
                verified: true,
            },
        });

        wrapper.vm.saveWebsite();

        moxios.wait(() => {
            expect(wrapper.emitted('saveWebsite')[0]).toStrictEqual(['https://www.example.com']);
            done();
        });
    });

    it('should set saveWebsite fileError to response.data.errors.fileError', async (done) => {
        await wrapper.setData({
            confirmWebsiteUrl: 'https://example.com',
            fileError: {
                title: 'error',
                details: 'details',
            },
        });

        moxios.stubRequest(wrapper.vm.confirmWebsiteUrl, {
            status: 200,
            response: {
                data: {
                    message: 'fileError',
                },
                errors: {
                    fileError: {
                        title: 'error',
                        details: 'details',
                    },
                },
            },
        });

        wrapper.vm.saveWebsite();

        moxios.wait(() => {
            expect(wrapper.vm.fileError).toEqual(
                {
                    title: 'error',
                    details: 'details',
                },
            );
            done();
        });
    });

    it('should saveWebsite response data.errors', async (done) => {
        const notifyErrorSpy = jest.spyOn(wrapper.vm, 'notifyError').mockImplementation();
        await wrapper.setData({
            confirmWebsiteUrl: 'https://example.com',
        });

        moxios.stubRequest(wrapper.vm.confirmWebsiteUrl, {
            status: 200,
            response: {
                data: {
                    message: 'error',
                },
                errors: [
                    'error',
                ],
            },
        });

        wrapper.vm.saveWebsite();

        moxios.wait(() => {
            expect(notifyErrorSpy).toHaveBeenCalled();
            done();
        });
    });

    it('verify when saveWebsite not response', async (done) => {
        const notifyErrorSpy = jest.spyOn(wrapper.vm, 'notifyError').mockImplementation();
        await wrapper.setData({
            confirmWebsiteUrl: 'https://example.com',
        });

        moxios.stubRequest(wrapper.vm.confirmWebsiteUrl, {
            status: 400,
            response: {
                data: {
                    message: 'error',
                },
            },
        });

        wrapper.vm.saveWebsite();

        moxios.wait(() => {
            expect(notifyErrorSpy).toHaveBeenCalled();
            done();
        });
    });
});
