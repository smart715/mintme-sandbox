import {createLocalVue, shallowMount} from '@vue/test-utils';
import ImageUploader from '../../js/components/ImageUploader';
import axios from 'axios';
import moxios from 'moxios';

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
        },
    });

    return localVue;
}

/**
 * @param {Object} props
 * @return {Object}
 */
function mockImageUploader(props = {}) {
    return {
        type: 'token',
        purpose: 'cover',
        token: 'MySuperToken',
        ...props,
    };
}

const testImg = 'cuteImg.png';

describe('ImageUploader', () => {
    let wrapper;
    beforeEach(() => {
        wrapper = shallowMount(ImageUploader, {
            localVue: localVue,
            sync: false,
            propsData: mockImageUploader(),
        });

        moxios.install();
    });

    afterEach(() => {
        moxios.uninstall();
    });

    it('should forbid use of input', () => {
        wrapper.vm.uploading = false;

        const input = wrapper.findComponent('input[type="file"]');

        expect(input.element.value).toBe('');
    });

    it('should emit upload event on successful upload', async (done) => {
        const input = wrapper.findComponent('input[type="file"]');
        input.files = testImg;

        await moxios.stubRequest('media_upload', {
            status: 200,
            response: {
                data: {
                    url: 'testUrl',
                },
            },
        });

        await wrapper.vm.upload(input.files[0]);

        moxios.wait(() => {
            expect(wrapper.emitted('upload').length).toBe(1);
            done();
        });
    });

    it('should display error message when upload its invalid', (done) => {
        const notifyErrorSpy = jest.spyOn(wrapper.vm, 'notifyError');

        moxios.stubRequest('media_upload', {
            status: 400,
            response: {
                message: 'testError',
            },
        });

        wrapper.vm.upload(testImg);

        moxios.wait(() => {
            expect(notifyErrorSpy).toHaveBeenCalledWith('testError');
            done();
        });
    });

    it('should upload a file sucessfully', (done) => {
        moxios.stubRequest('media_upload', {
            status: 200,
            response: {
                data: {
                    url: 'testUrl',
                },
            },
        });

        wrapper.vm.upload(testImg);

        moxios.wait(() => {
            expect(wrapper.emitted('upload').length).toBe(1);
            done();
        });
    });
});
