import {shallowMount, createLocalVue} from '@vue/test-utils';
import TokenYoutubeAddressView from '../../js/components/token/youtube/TokenYoutubeAddressView';

const localVue = createLocalVue();
localVue.use({
    install(Vue, options) {
        Vue.prototype.$t = (val) => val;
    },
});

let objectForTestCorrectlyMouning = {
    localVue,
    methods: {
        renderYtSubscribeButton: function(channelId) {
            return channelId;
        },
    },
    propsData: {
        channelId: 'testChannelId',
        clientId: 'testClientId',
    },
    sync: false,
 };

describe('TokenYoutubeAddressView', () => {
    it('renders correctly with assigned props', () => {
        const wrapper = shallowMount(TokenYoutubeAddressView, objectForTestCorrectlyMouning);
        expect(wrapper.find('a').attributes('href')).toContain('testChannelId');
        expect(wrapper.find('a').text()).toContain('testChannelId');
    });

    it('should compute youTubeUrl correctly', () => {
        const wrapper = shallowMount(TokenYoutubeAddressView, objectForTestCorrectlyMouning);
        expect(wrapper.vm.youTubeUrl).toBe('https://www.youtube.com/channel/testChannelId');
    });

    it('should set youtube url correctly when the function buildYoutubeUrl() is called', () => {
        const wrapper = shallowMount(TokenYoutubeAddressView, objectForTestCorrectlyMouning);
        expect(wrapper.vm.buildYoutubeUrl('foo')).toBe('https://www.youtube.com/channel/foo');
    });
});
