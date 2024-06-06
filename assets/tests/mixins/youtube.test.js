import {createLocalVue, shallowMount} from '@vue/test-utils';
import YoutubeMixin from '../../js/mixins/youtube';
import moxios from 'moxios';
import axios from 'axios';
import Vue from 'vue';

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

describe('YoutubeMixin', function() {
    beforeEach(() => {
        moxios.install();
    });

    afterEach(() => {
        moxios.uninstall();
    });

    const Component = Vue.component('foo', {
        mixins: [YoutubeMixin],
        template: '<div></div>',
    });

    it('authorizedYoutube', async (done) => {
        const wrapper = shallowMount(Component, {
            localVue: mockVue(),
            computed: {
                getIsAuthorizedYoutube: () => true,
            },
        });

        const expectedResponse = {
            isExpired: false,
        };

        wrapper.vm.setIsAuthorizedYoutube = function() {};

        moxios.stubRequest('youtube_token_expired', {
            status: 200,
            response: expectedResponse,
        });

        moxios.wait(() => {
            expect(wrapper.vm.isAuthorizedYoutube).toBe(true);
            done();
        });
    });

    it('unAuthorizedYoutube', async (done) => {
        const wrapper = shallowMount(Component, {
            localVue: mockVue(),
            computed: {
                getIsAuthorizedYoutube: () => false,
            },
        });

        const expectedResponse = {
            isExpired: true,
        };

        wrapper.vm.setIsAuthorizedYoutube = function() {};

        moxios.stubRequest('youtube_token_expired', {
            status: 200,
            response: expectedResponse,
        });

        moxios.wait(() => {
            expect(wrapper.vm.isAuthorizedYoutube).toBe(false);
            done();
        });
    });
});
