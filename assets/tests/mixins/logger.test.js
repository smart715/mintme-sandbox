import {createLocalVue, shallowMount} from '@vue/test-utils';
import LoggerMixin from '../../js/mixins/logger';
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

describe('LoggerMixin', () => {
    beforeEach(() => {
        moxios.install();
    });

    afterEach(() => {
        moxios.uninstall();
    });

    const Component = Vue.component('foo', {
        template: '<div></div>',
        mixins: [LoggerMixin],
    });
    const wrapper = shallowMount(Component, {
        localVue: mockVue(),
    });

    it('triggers sendLogs correctly', (done) => {
        wrapper.vm.sendLogs('info', 'Test log', {});

        moxios.wait(() => {
            let request = moxios.requests.mostRecent();
            request.respondWith({
                status: 200,
                response: {},
            }).then(() => done());
        });
    });
});
