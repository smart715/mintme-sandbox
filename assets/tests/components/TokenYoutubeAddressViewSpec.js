import {shallowMount} from '@vue/test-utils';
import TokenYoutubeAddressView from '../../js/components/token/youtube/TokenYoutubeAddressView';

let objectForTestCorrectlyMouning = {
    methods: {
        renderYtSubscribeButton: function(channelId) {
            return channelId;
        },
    },
    propsData: {
        channelId: 'testChannelId',
        clientId: 'testClientId',
    },
 };

describe('TokenYoutubeAddressView', () => {
    it('renders correctly with assigned props', () => {
        const wrapper = shallowMount(TokenYoutubeAddressView, objectForTestCorrectlyMouning);
        expect(wrapper.find('a').attributes('href')).to.contain('testChannelId');
        expect(wrapper.find('a').text()).to.contain('testChannelId');
    });

    it('should compute youTubeUrl correctly', () => {
        const wrapper = shallowMount(TokenYoutubeAddressView, objectForTestCorrectlyMouning);
        expect(wrapper.vm.youTubeUrl).to.be.equal('https://www.youtube.com/channel/testChannelId');
    });

    it('should set youtube url correctly when the function buildYoutubeUrl() is called', () => {
        const wrapper = shallowMount(TokenYoutubeAddressView, objectForTestCorrectlyMouning);
        expect(wrapper.vm.buildYoutubeUrl('foo')).to.be.equal('https://www.youtube.com/channel/foo');
    });
});
