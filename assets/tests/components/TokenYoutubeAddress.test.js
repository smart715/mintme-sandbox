import {createLocalVue, shallowMount} from '@vue/test-utils';
import TokenYoutubeAddress from '../../js/components/token/youtube/TokenYoutubeAddress';
import moxios from 'moxios';
import axios from 'axios';

const localVue = mockVue();

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.use({
        install(Vue) {
            Vue.prototype.$axios = {single: axios};
            Vue.prototype.$routing = {generate: (val, params) => val};
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
function mockTokenYoutubeAddress(props = {}) {
    return shallowMount(TokenYoutubeAddress, {
        stubs: ['b-tooltip'],
        localVue: localVue,
        propsData: createSharedTestProps(props),
    });
}

/**
 * @param {Object} props
 * @return {Object}
 */
function createSharedTestProps(props = {}) {
    return {
        channelId: 'testChannelId',
        clientId: 'testClientId',
        editable: false,
        tokenName: 'testTokenName',
        ...props,
    };
}

describe('TokenYoutubeAddress', () => {
    beforeEach(() => {
        moxios.install();
    });

    afterEach(() => {
        moxios.uninstall();
    });

    it('should compute computedChannel correctly', async () => {
        const wrapper = mockTokenYoutubeAddress();
        expect(wrapper.vm.computedChannel).toBe('https://www.youtube.com/channel/testChannelId');

        await wrapper.setData({
            currentChannelId: false,
        });
        expect(wrapper.vm.computedChannel).toBe('token.youtube.empty_address');
    });

    it('should set youtube url correctly when the function buildYoutubeUrl() is called', () => {
        const wrapper = mockTokenYoutubeAddress();
        expect(wrapper.vm.buildYoutubeUrl('foo')).toBe('https://www.youtube.com/channel/foo');
    });

    it(
        `do $axios request, set currentChannelId and submitting correctly and emit "saveYoutube"
        when submitting data is false and the function saveYoutubeChannel() is called`,
        async (done) => {
            const wrapper = mockTokenYoutubeAddress();

            wrapper.vm.notifySuccess = jest.fn();

            moxios.stubRequest('token_update', {
                status: 200,
            });

            wrapper.vm.saveYoutubeChannel('foo');

            moxios.wait(() => {
                expect(wrapper.vm.currentChannelId).toBe('foo');
                expect(wrapper.vm.submitting).toBe(false);
                expect(wrapper.emitted('saveYoutube').length).toBe(1);
                done();
            });
        }
    );

    it('do not $axios when submitting data is true and the function saveYoutubeChannel() is called', async () => {
        const wrapper = mockTokenYoutubeAddress();

        await wrapper.setData({
            submitting: true,
        });

        wrapper.vm.saveYoutubeChannel('foo');
        expect(wrapper.vm.currentChannelId).toBe('testChannelId');
    });

    it('call saveYoutubeChannel(\'\') when deleteChannel() is called', () => {
        const wrapper = mockTokenYoutubeAddress();

        wrapper.vm.saveYoutubeChannel = (channelId) => {
            if ('' === channelId) {
                wrapper.vm.$emit('deleteChannelTest');
            }
        };

        wrapper.vm.deleteChannel();
        expect(wrapper.emitted('deleteChannelTest').length).toBe(1);
    });
});
