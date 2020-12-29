import {createLocalVue, shallowMount} from '@vue/test-utils';
import TokenYoutubeAddress from '../../js/components/token/youtube/TokenYoutubeAddress';
import moxios from 'moxios';
import axios from 'axios';

const $routing = {generate: (val, params) => val};

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.use({
        install(Vue) {
            Vue.prototype.$axios = {single: axios};
            Vue.prototype.$routing = $routing;
            Vue.prototype.$t = (val) => val;
        },
    });
    return localVue;
};

let propsForTestCorrectlyRenders = {
    channelId: 'testChannelId',
    clientId: 'testClientId',
    editable: false,
    tokenName: 'testTokenName',
 };

describe('TokenYoutubeAddress', () => {
    beforeEach(() => {
        moxios.install();
    });

    afterEach(() => {
        moxios.uninstall();
    });

    it('renders correctly with assigned props', () => {
        const wrapper = shallowMount(TokenYoutubeAddress, {
            stubs: ['b-tooltip'],
            mocks: {
                $routing,
            },
            propsData: propsForTestCorrectlyRenders,
        });
        expect(wrapper.find('b-tooltip-stub').html()).toContain('testChannelId');
    });

    it('should compute computedChannel correctly', () => {
        const wrapper = shallowMount(TokenYoutubeAddress, {
            stubs: ['b-tooltip'],
            mocks: {
                $routing,
                $t: (val)=> val,
            },
            propsData: propsForTestCorrectlyRenders,
        });
        expect(wrapper.vm.computedChannel).toBe('https://www.youtube.com/channel/testChannelId');

        wrapper.vm.currentChannelId = false;
        expect(wrapper.vm.computedChannel).toBe('token.youtube.empty_address');
    });

    it('should set youtube url correctly when the function buildYoutubeUrl() is called', () => {
        const wrapper = shallowMount(TokenYoutubeAddress, {
            stubs: ['b-tooltip'],
            mocks: {
                $routing,
            },
            propsData: propsForTestCorrectlyRenders,
        });
        expect(wrapper.vm.buildYoutubeUrl('foo')).toBe('https://www.youtube.com/channel/foo');
    });

    it('do $axios request, set currentChannelId and submitting correctly and emit "saveYoutube" when submitting data is false and the function saveYoutubeChannel() is called', (done) => {
        const localVue = mockVue();
        const wrapper = shallowMount(TokenYoutubeAddress, {
            stubs: ['b-tooltip'],
            localVue,
            methods: {
                notifySuccess: function(message) {
                    return false;
                },
            },
            propsData: propsForTestCorrectlyRenders,
        });
        wrapper.vm.saveYoutubeChannel('foo');

        moxios.stubRequest('token_update', {
            status: 200,
        });

        moxios.wait(() => {
            expect(wrapper.vm.currentChannelId).toBe('foo');
            expect(wrapper.vm.submitting).toBe(false);
            expect(wrapper.emitted('saveYoutube').length).toBe(1);
            done();
        });
    });

    it('do not $axios request when submitting data is true and the function saveYoutubeChannel() is called', () => {
        const wrapper = shallowMount(TokenYoutubeAddress, {
            stubs: ['b-tooltip'],
            mocks: {
                $routing,
            },
            propsData: propsForTestCorrectlyRenders,
        });
        wrapper.vm.submitting = true;
        wrapper.vm.saveYoutubeChannel('foo');
        expect(wrapper.vm.currentChannelId).toBe('testChannelId');
    });

    it('call saveYoutubeChannel(\'\') when deleteChannel() is called', () => {
        const wrapper = shallowMount(TokenYoutubeAddress, {
            stubs: ['b-tooltip'],
            mocks: {
                $routing,
            },
            methods: {
                saveYoutubeChannel: function(channelId) {
                    if (channelId === '') {
                        wrapper.vm.$emit('deleteChannelTest');
                    }
                },
            },
            propsData: propsForTestCorrectlyRenders,
        });
        wrapper.vm.deleteChannel();
        expect(wrapper.emitted('deleteChannelTest').length).toBe(1);
    });
});
