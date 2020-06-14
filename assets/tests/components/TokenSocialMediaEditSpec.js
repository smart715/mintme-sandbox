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
        expect(wrapper.find('token-website-address-stub').html()).to.contain('testWebsiteUrl');
        expect(wrapper.find('token-website-address-stub').html()).to.contain('testTokenName');
        expect(wrapper.find('token-youtube-address-stub').html()).to.contain('testYoutubeChannelId');
        expect(wrapper.find('token-youtube-address-stub').html()).to.contain('testYoutubeClientId');
        expect(wrapper.find('token-youtube-address-stub').html()).to.contain('testTokenName');
        expect(wrapper.find('token-facebook-address-stub').html()).to.contain('testFacebookUrl');
        expect(wrapper.find('token-facebook-address-stub').html()).to.contain('testTokenName');
        expect(wrapper.find('token-telegram-channel-stub').html()).to.contain('testTelegramUrl');
        expect(wrapper.find('token-telegram-channel-stub').html()).to.contain('testTokenName');
        expect(wrapper.find('token-discord-channel-stub').html()).to.contain('testDiscordUrl');
        expect(wrapper.find('token-discord-channel-stub').html()).to.contain('testTokenName');
    });

    it('should set currentWebsite correctly when the function saveWebsite() is called', () => {
        const wrapper = shallowMount(TokenSocialMediaEdit, objectForTestCorrectlyMouning);
        wrapper.vm.saveWebsite('foo');
        expect(wrapper.vm.currentWebsite).to.be.equal('foo');
    });

    it('should set currentDiscord correctly when the function saveDiscord() is called', () => {
        const wrapper = shallowMount(TokenSocialMediaEdit, objectForTestCorrectlyMouning);
        wrapper.vm.saveDiscord('foo');
        expect(wrapper.vm.currentDiscord).to.be.equal('foo');
    });

    it('should set currentFacebook correctly when the function saveFacebook() is called', () => {
        const wrapper = shallowMount(TokenSocialMediaEdit, objectForTestCorrectlyMouning);
        wrapper.vm.saveFacebook('foo');
        expect(wrapper.vm.currentFacebook).to.be.equal('foo');
    });

    it('should set currentTelegram correctly when the function saveTelegram() is called', () => {
        const wrapper = shallowMount(TokenSocialMediaEdit, objectForTestCorrectlyMouning);
        wrapper.vm.saveTelegram('foo');
        expect(wrapper.vm.currentTelegram).to.be.equal('foo');
    });

    it('should set currentYoutube correctly when the function saveYoutube() is called', () => {
        const wrapper = shallowMount(TokenSocialMediaEdit, objectForTestCorrectlyMouning);
        wrapper.vm.saveYoutube('foo');
        expect(wrapper.vm.currentYoutube).to.be.equal('foo');
    });

    it('should set editingDiscord, editingTelegram and editingWebsite correctly when the function toggleEdit() is called', () => {
        const wrapper = shallowMount(TokenSocialMediaEdit, objectForTestCorrectlyMouning);
        wrapper.vm.editingDiscord = true;
        wrapper.vm.editingTelegram = true;
        wrapper.vm.editingWebsite = true;
        wrapper.vm.toggleEdit();
        expect(wrapper.vm.editingDiscord).to.be.false;
        expect(wrapper.vm.editingTelegram).to.be.false;
        expect(wrapper.vm.editingWebsite).to.be.false;

        wrapper.vm.editingDiscord = false;
        wrapper.vm.editingTelegram = true;
        wrapper.vm.editingWebsite = true;
        wrapper.vm.toggleEdit('discord');
        expect(wrapper.vm.editingDiscord).to.be.true;
        expect(wrapper.vm.editingTelegram).to.be.false;
        expect(wrapper.vm.editingWebsite).to.be.false;

        wrapper.vm.editingDiscord = true;
        wrapper.vm.editingTelegram = false;
        wrapper.vm.editingWebsite = true;
        wrapper.vm.toggleEdit('telegram');
        expect(wrapper.vm.editingDiscord).to.be.false;
        expect(wrapper.vm.editingTelegram).to.be.true;
        expect(wrapper.vm.editingWebsite).to.be.false;

        wrapper.vm.editingDiscord = true;
        wrapper.vm.editingTelegram = true;
        wrapper.vm.editingWebsite = false;
        wrapper.vm.toggleEdit('website');
        expect(wrapper.vm.editingDiscord).to.be.false;
        expect(wrapper.vm.editingTelegram).to.be.false;
        expect(wrapper.vm.editingWebsite).to.be.true;
    });
});
