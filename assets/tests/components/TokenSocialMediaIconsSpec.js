import {shallowMount} from '@vue/test-utils';
import TokenSocialMediaIcons from '../../js/components/token/TokenSocialMediaIcons';

const $routing = {generate: (val, params) => val};

let objectForTestCorrectlyMounting = {
    mocks: {$routing},
    propsData: {
        discordUrl: 'testDiscordUrl',
        facebookUrl: 'testFacebookUrl',
        telegramUrl: 'testTelegramUrl',
        tokenName: 'testTokenName',
        websiteUrl: 'testWebsiteUrl',
        youtubeChannelId: 'testYoutubeChannelId',
    },
 };

 let emptyUrls = {
    mocks: {$routing},
    propsData: {
        discordUrl: '',
        facebookUrl: '',
        telegramUrl: '',
        tokenName: '',
        websiteUrl: '',
        youtubeChannelId: '',
    },
 };

describe('TokenSocialMediaIcons', () => {
    it('renders correctly with props', () => {
        const wrapper = shallowMount(TokenSocialMediaIcons, objectForTestCorrectlyMounting);
        expect(wrapper.find('a[href=\'testDiscordUrl\']')).to.exist;
        expect(wrapper.find('a[href=\'testFacebookUrl\']')).to.exist;
        expect(wrapper.find('a[href=\'testTelegramUrl\']')).to.exist;
        expect(wrapper.find('a[href=\'testTokenName\']')).to.exist;
        expect(wrapper.find('a[href=\'testWebsiteUrl\']')).to.exist;
        expect(wrapper.find('a[href=\'https://www.youtube.com/channel/testYoutubeChannelId\']')).to.exist;
    });

    it('should compute description correctly', () => {
        const wrapper = shallowMount(TokenSocialMediaIcons, objectForTestCorrectlyMounting);
        wrapper.vm.twitterDescription = 'foo';
        expect(wrapper.vm.description).to.be.equal('footoken_show');
    });

    it('should compute youtubeUrl correctly', () => {
        const wrapper = shallowMount(TokenSocialMediaIcons, objectForTestCorrectlyMounting);
        expect(wrapper.vm.youtubeUrl).to.be.equal('https://www.youtube.com/channel/testYoutubeChannelId');
    });

    it('doesnt show unsetted urls', () => {
        const wrapper = shallowMount(TokenSocialMediaIcons, emptyUrls);
        expect(wrapper.findAll('a').length).to.be.equal(0);
    });
});
