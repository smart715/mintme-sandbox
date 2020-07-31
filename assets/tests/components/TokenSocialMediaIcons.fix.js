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
        expect(wrapper.find('a[href=\'testDiscordUrl\']').exists()).toBe(true);
        expect(wrapper.find('a[href=\'testFacebookUrl\']').exists()).toBe(true);
        expect(wrapper.find('a[href=\'testTelegramUrl\']').exists()).toBe(true);
        expect(wrapper.find('a[href=\'testTokenName\']').exists()).toBe(true);
        expect(wrapper.find('a[href=\'testWebsiteUrl\']').exists()).toBe(true);
        expect(wrapper.find('a[href=\'https://www.youtube.com/channel/testYoutubeChannelId\']').exists()).toBe(true);
    });

    it('should compute description correctly', () => {
        const wrapper = shallowMount(TokenSocialMediaIcons, objectForTestCorrectlyMounting);
        wrapper.vm.twitterDescription = 'foo';
        expect(wrapper.vm.description).toBe('footoken_show');
    });

    it('should compute youtubeUrl correctly', () => {
        const wrapper = shallowMount(TokenSocialMediaIcons, objectForTestCorrectlyMounting);
        expect(wrapper.vm.youtubeUrl).toBe('https://www.youtube.com/channel/testYoutubeChannelId');
    });

    it('doesnt show unsetted urls', () => {
        const wrapper = shallowMount(TokenSocialMediaIcons, emptyUrls);
        expect(wrapper.findAll('a').length).toBe(0);
    });
});
