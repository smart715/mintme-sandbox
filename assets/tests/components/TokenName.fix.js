import {createLocalVue, shallowMount} from '@vue/test-utils';
import TokenName from '../../js/components/token/TokenName';
import moxios from 'moxios';
import axiosPlugin from '../../js/axios';
import axios from 'axios';

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.use(axiosPlugin);
    localVue.use({
        install(Vue, options) {
            Vue.prototype.$axios = {retry: axios, single: axios};
            Vue.prototype.$routing = {generate: (val) => val};
        },
    });
    return localVue;
}

describe('TokenName', () => {
    beforeEach(() => {
        moxios.install();
    });

    afterEach(() => {
        moxios.uninstall();
    });

    // Commented due the component hard reloading. Consider to resolve TODO and uncomment it
    // it('can be edited if editable', (done) => {
    //     const localVue = mockVue();
    //     const wrapper = mount(TokenName, {
    //         localVue,
    //         propsData: {
    //             name: 'foo',
    //             csrfToken: 'csrfToken',
    //             updateUrl: 'updateUrl',
    //             editable: true,
    //         },
    //     });
    //     moxios.stubRequest('updateUrl', {
    //         status: 204,
    //         response: [],
    //     });
    //
    //     moxios.stubRequest('is_token_exchanged', {
    //         status: 200,
    //         response: false,
    //     });
    //
    //     moxios.wait(() => {
    //         expect(wrapper.find('input').exists()).toBe(false);
    //         expect(wrapper.vm.editingName).toBe(false);
    //
    //         wrapper.vm.editName();
    //
    //         let input = wrapper.find('input');
    //
    //         expect(input.exists()).toBe(true);
    //         expect(wrapper.vm.editingName).toBe(true);
    //
    //         input.setValue('bar');
    //         wrapper.vm.editName();
    //
    //         expect(wrapper.vm.currentName).toBe('bar');
    //         expect(wrapper.vm.newName).toBe('bar');
    //         done();
    //     });
    // });

    it('can not be edited if not editable', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(TokenName, {
            localVue,
            propsData: {
                name: 'foo',
                identifier: 'bar',
                editable: false,
            },
        });
        expect(wrapper.find('svg').exists()).toBe(false);
    });
});
