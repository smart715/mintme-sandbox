import {createLocalVue, shallowMount} from '@vue/test-utils';
import TokenName from '../../js/components/token/TokenName';
import moxios from 'moxios';
import axios from 'axios';

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
        },
    });
    return localVue;
}

const tokenNameShort = 'tokenTest';
const tokenNameLong = 'tokenNameTestLong';
const directives = {
    'b-tooltip': {},
};

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
    //         expect(wrapper.findComponent('input').exists()).toBe(false);
    //         expect(wrapper.vm.editingName).toBe(false);
    //
    //         wrapper.vm.editName();
    //
    //         let input = wrapper.findComponent('input');
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

    it('Verify that `checkLengthName` returns the correct boolean value', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(TokenName, {
            localVue,
            propsData: {
                name: 'tokenTest',
            },
            directives,
        });

        wrapper.setData({
            maxLengthToTruncate: 13,
        });

        expect(wrapper.vm.checkLengthName(tokenNameShort)).toBe(false);
        expect(wrapper.vm.checkLengthName(tokenNameLong)).toBe(true);
    });

    it('Check that `checkTabName` returns the correct name', async () => {
        const localVue = mockVue();
        const wrapper = shallowMount(TokenName, {
            localVue,
            propsData: {
                name: tokenNameShort,
                tabIndex: 2,
            },
            directives,
        });

        expect(wrapper.vm.checkTabName()).toBe('token.trade.token_name');

        await wrapper.setProps({tabIndex: 1});
        expect(wrapper.vm.checkTabName()).toBe('token.posts.token_name');

        await wrapper.setProps({tabIndex: 4});
        expect(wrapper.vm.checkTabName()).toBe('token.voting.token_name');

        await wrapper.setProps({tabIndex: 0});
        expect(wrapper.vm.checkTabName()).toBe(tokenNameShort);
    });

    it('can not be edited if not editable', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(TokenName, {
            localVue,
            propsData: {
                name: 'foo',
                identifier: 'bar',
                editable: false,
            },
            directives,
        });
        expect(wrapper.findComponent('svg').exists()).toBe(false);
    });
    it('setPageTitle method should change the page by number 1', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(TokenName, {
            localVue,
            propsData: {
                name: 'foo',
                identifier: 'bar',
                editable: false,
                tabIndex: 1,
            },
            directives,
        });
        wrapper.vm.setPageTitle();
        expect(document.title).toBe('page.pair.title_posts');
    });
    it('setPageTitle method should change the page by number 2', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(TokenName, {
            localVue,
            propsData: {
                name: 'foo',
                identifier: 'bar',
                editable: false,
                tabIndex: 2,
            },
            directives,
        });
        wrapper.vm.setPageTitle();
        expect(document.title).toBe('page.pair.title_market_tab');
    });
});
