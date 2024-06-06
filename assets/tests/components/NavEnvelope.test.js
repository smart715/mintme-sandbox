import {shallowMount, createLocalVue} from '@vue/test-utils';
import NavEnvelope from '../../js/components/chat/NavEnvelope.vue';
import axios from 'axios';
import moxios from 'moxios';

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
            Vue.prototype.$logger = {error: () => {}};
        },
    });
    return localVue;
}

/**
 * @param {Object} props
 * @return {Wrapper<Vue>}
 */
function mockNavEnvelope(props = {}) {
    return shallowMount(NavEnvelope, {
        localVue: mockVue(),
        propsData: {
            url: '',
            ...props,
        },
    });
}

describe('NavEnvelope', () => {
    beforeEach(() => {
        moxios.install();
    });

    afterEach(() => {
        moxios.uninstall();
    });

    it('Verify that "countTxt" works correctly', async () => {
        const wrapper = mockNavEnvelope();

        await wrapper.setData({
            count: 50,
        });

        expect(wrapper.vm.countTxt).toBe(50);

        await wrapper.setData({
            count: 100,
        });

        expect(wrapper.vm.countTxt).toBe('99+');
    });

    it('Verify that "loadCount" works correctly', (done) => {
        const wrapper = mockNavEnvelope();

        moxios.stubRequest('get_unread_messages_count', {
            status: 200,
            response: 50,
        });

        wrapper.vm.loadCount();

        moxios.wait(() => {
            expect(wrapper.vm.count).toBe(50);
            done();
        });
    });
});
