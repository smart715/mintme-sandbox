import {createLocalVue, shallowMount} from '@vue/test-utils';
import noBadWordsMixin from '../../js/mixins/no_bad_words';
import moxios from 'moxios';
import axios from 'axios';
import Vue from 'vue';

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.use({
        install(Vue) {
            Vue.prototype.$axios = {retry: axios, single: axios};
            Vue.prototype.$routing = {generate: (val) => val};
            Vue.prototype.$t = (val) => val;
        },
    });

    return localVue;
}

describe('noBadWordsMixin', function() {
    beforeEach(() => {
        moxios.install();
    });

    afterEach(() => {
        moxios.uninstall();
        localStorage.clear();
    });

    const Component = Vue.component('foo', {
        data() {
            return {
                fieldBadWordMessage: '',
                field: '',
            };
        },
        mixins: [noBadWordsMixin],
        template: '<div></div>',
    });
    const wrapper = shallowMount(Component, {
        localVue: mockVue(),
    });

    it('should work correctly when NoBadWordsValidator method invoked', async (done) => {
        wrapper.vm.field = 'TEST';

        moxios.stubRequest('get_censor_config', {
            status: 200,
            response: {
                censorChecks: ['/TEST/i'],
            },
        });

        await wrapper.vm.noBadWordsValidator('field', 'fieldBadWordMessage');

        expect(wrapper.vm.fieldBadWordMessage).toBe('bad_word.found');
        done();
    });

    it('shouldn\'t bad a whitelisted word', async (done) => {
        wrapper.vm.field = 'TEST';

        moxios.stubRequest('get_censor_config', {
            status: 200,
            response: {
                blacklistedWords: ['TEST'],
                whitelistedWords: ['TEST'],
            },
        });

        await wrapper.vm.noBadWordsValidator('field', 'fieldBadWordMessage');

        expect(wrapper.vm.fieldBadWordMessage).toBe('');
        done();
    });

    it('shouldn\'t proceed validation when field is empty', async (done) => {
        wrapper.vm.field = '';

        await wrapper.vm.noBadWordsValidator('field', 'fieldBadWordMessage');

        expect(wrapper.vm.fieldBadWordMessage).toBe('');
        done();
    });
});
