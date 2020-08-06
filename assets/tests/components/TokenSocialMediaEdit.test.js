import {shallowMount} from '@vue/test-utils';
import TokenSocialMediaEdit from '../../js/components/token/TokenSocialMediaEdit';

const $routing = {generate: (val, params) => val};

let objectForTestCorrectlyMouning = {
    mocks: {$routing},
    propsData: {
        discordUrl: 'testDiscordUrl',
        editable: true,
        facebookUrl: 'testFacebookUrl',
        facebookAppId: 'testFacebookAppId',
        telegramUrl: 'testTelegramUrl',
        tokenName: 'testTokenName',
        websiteUrl: 'testWebsiteUrl',
        youtubeClientId: 'testYoutubeClientId',
        youtubeChannelId: 'testYoutubeChannelId',
        tokenUrl: 'http://localhost/token/testTokenName',
    },
 };

describe('TokenSocialMediaEdit', () => {
    it('renders correctly with assigned props', () => {
        const wrapper = shallowMount(TokenSocialMediaEdit, objectForTestCorrectlyMouning);
        expect(wrapper.find('token-website-address-stub').html()).toContain('testWebsiteUrl');
        expect(wrapper.find('token-website-address-stub').html()).toContain('testTokenName');
        expect(wrapper.find('token-youtube-address-stub').html()).toContain('testYoutubeChannelId');
        expect(wrapper.find('token-youtube-address-stub').html()).toContain('testYoutubeClientId');
        expect(wrapper.find('token-youtube-address-stub').html()).toContain('testTokenName');
        expect(wrapper.find('token-facebook-address-stub').html()).toContain('testFacebookUrl');
        expect(wrapper.find('token-facebook-address-stub').html()).toContain('testTokenName');
        expect(wrapper.find('token-telegram-channel-stub').html()).toContain('testTelegramUrl');
        expect(wrapper.find('token-telegram-channel-stub').html()).toContain('testTokenName');
        expect(wrapper.find('token-discord-channel-stub').html()).toContain('testDiscordUrl');
        expect(wrapper.find('token-discord-channel-stub').html()).toContain('testTokenName');
    });

    it('should set currentWebsite correctly when the function saveWebsite() is called', () => {
        const wrapper = shallowMount(TokenSocialMediaEdit, objectForTestCorrectlyMouning);
        wrapper.vm.saveWebsite('foo');
        expect(wrapper.vm.currentWebsite).toBe('foo');
    });

    it('should set currentDiscord correctly when the function saveDiscord() is called', () => {
        const wrapper = shallowMount(TokenSocialMediaEdit, objectForTestCorrectlyMouning);
        wrapper.vm.saveDiscord('foo');
        expect(wrapper.vm.currentDiscord).toBe('foo');
    });

    it('should set currentFacebook correctly when the function saveFacebook() is called', () => {
        const wrapper = shallowMount(TokenSocialMediaEdit, objectForTestCorrectlyMouning);
        wrapper.vm.saveFacebook('foo');
        expect(wrapper.vm.currentFacebook).toBe('foo');
    });

    it('should set currentTelegram correctly when the function saveTelegram() is called', () => {
        const wrapper = shallowMount(TokenSocialMediaEdit, objectForTestCorrectlyMouning);
        wrapper.vm.saveTelegram('foo');
        expect(wrapper.vm.currentTelegram).toBe('foo');
    });

    it('should set currentYoutube correctly when the function saveYoutube() is called', () => {
        const wrapper = shallowMount(TokenSocialMediaEdit, objectForTestCorrectlyMouning);
        wrapper.vm.saveYoutube('foo');
        expect(wrapper.vm.currentYoutube).toBe('foo');
    });

    it('should set editingDiscord, editingTelegram and editingWebsite correctly when the function toggleEdit() is called', () => {
        const wrapper = shallowMount(TokenSocialMediaEdit, objectForTestCorrectlyMouning);
        wrapper.vm.editingDiscord = true;
        wrapper.vm.editingTelegram = true;
        wrapper.vm.editingWebsite = true;
        wrapper.vm.toggleEdit();
        expect(wrapper.vm.editingDiscord).toBe(false);
        expect(wrapper.vm.editingTelegram).toBe(false);
        expect(wrapper.vm.editingWebsite).toBe(false);

        wrapper.vm.editingDiscord = false;
        wrapper.vm.editingTelegram = true;
        wrapper.vm.editingWebsite = true;
        wrapper.vm.toggleEdit('discord');
        expect(wrapper.vm.editingDiscord).toBe(true);
        expect(wrapper.vm.editingTelegram).toBe(false);
        expect(wrapper.vm.editingWebsite).toBe(false);

        wrapper.vm.editingDiscord = true;
        wrapper.vm.editingTelegram = false;
        wrapper.vm.editingWebsite = true;
        wrapper.vm.toggleEdit('telegram');
        expect(wrapper.vm.editingDiscord).toBe(false);
        expect(wrapper.vm.editingTelegram).toBe(true);
        expect(wrapper.vm.editingWebsite).toBe(false);

        wrapper.vm.editingDiscord = true;
        wrapper.vm.editingTelegram = true;
        wrapper.vm.editingWebsite = false;
        wrapper.vm.toggleEdit('website');
        expect(wrapper.vm.editingDiscord).toBe(false);
        expect(wrapper.vm.editingTelegram).toBe(false);
        expect(wrapper.vm.editingWebsite).toBe(true);
    });
});
