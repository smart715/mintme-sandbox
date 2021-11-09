import {createLocalVue, shallowMount} from '@vue/test-utils';
import ImageUploader from '../../js/components/ImageUploader';
import axios from 'axios';
import moxios from 'moxios';

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.use({
        install(Vue, options) {
            Vue.prototype.$axios = {retry: axios, single: axios};
            Vue.prototype.$routing = {generate: (val) => val};
        },
    });
    return localVue;
}

describe('ImageUploader', () => {
    beforeEach(() => {
        moxios.install();
    });

    afterEach(() => {
        moxios.uninstall();
    });

    it('should upload a file', () => {
        const wrapper = shallowMount(ImageUploader, {
            localVue: mockVue(),
            propsData: {
                type: 'token',
            },
        });

        moxios.stubRequest('media_upload', {
            status: 200,
            response: {
                image: 'dummy.jpg',
            },
        });

        wrapper.vm.upload('dummy.jpg');

        moxios.wait(() => {
            expect(wrapper.emitted().upload).toBe(true);
            done();
        });
    });

    it('should call notifyError() when uploaded image is invalid', () => {
        const wrapper = shallowMount(ImageUploader, {
            localVue: mockVue(),
            propsData: {
                type: 'token',
            },
            methods: {
                notifyError: function() {
                    this.$emit('errormessage');
                },
            },
        });

        moxios.stubRequest('media_upload', {
            status: 400,
            response: {
                data: {
                    message: 'Invalid media type',
                },
            },
        });

        wrapper.vm.upload('dummy.jpg');

        moxios.wait(() => {
            expect(wrapper.emitted().errormessage).toBe(true);
            done();
        });
    });
});
