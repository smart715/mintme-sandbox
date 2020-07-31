import {createLocalVue, shallowMount} from '@vue/test-utils';
import TopHolders from '../../js/components/trade/TopHolders';
import moxios from 'moxios';
import axios from 'axios';

/**
 * @return {VueConstructor}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.use({
        install(Vue, options) {
            Vue.prototype.$axios = {retry: axios, single: axios};
            Vue.prototype.$routing = {generate: (val) => val};
            Vue.prototype.$toasted = {show: () => false};
        },
    });
    localVue.component('font-awesome-icon', {template: ''});
    return localVue;
}

/**
 * @return {Wrapper<Vue>}
 */
function mockTopHolders() {
    return shallowMount(TopHolders, {
        localVue: mockVue(),
        propsData: {
            name: 'TOK1',
        },
    });
}

describe('TopHolders', () => {
    beforeEach(() => {
        moxios.install();
    });

    afterEach(() => {
        moxios.uninstall();
    });

    it('should show loading before getting the traders', (done) => {
        const wrapper = mockTopHolders();

        expect(wrapper.vm.loaded).toBe(false);
        expect(wrapper.find('font-awesome-icon-stub').exists()).toBe(true);
        expect(wrapper.find('b-table').exists()).toBe(false);
        expect(wrapper.vm.traders).toBe(null);

        moxios.stubRequest('top_holders', {status: 200, response: [
            {
                user: {profile: {nickname: 'foo'}},
                timestamp: 1563550710,
                balance: '999',
            },
            {
                user: {profile: {nickname: 'foo'}},
                timestamp: 1563550710,
                balance: '99',
            },
        ]});

        moxios.wait(() => {
            expect(wrapper.vm.loaded).toBe(true);
            expect(wrapper.find('font-awesome-icon-stub').exists()).toBe(false);
            expect(wrapper.find('b-table').exists()).toBe(true);
            done();
        });
    });

    it('should hide the table if there are not traders', () => {
        const wrapper = mockTopHolders();
        wrapper.vm.traders = [];
        expect(wrapper.vm.hasTraders).toBe(false);
        expect(wrapper.find('b-table').exists()).toBe(false);
    });
});
