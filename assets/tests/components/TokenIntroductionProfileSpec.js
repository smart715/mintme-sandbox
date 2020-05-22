import {shallowMount} from '@vue/test-utils';
import TokenIntroductionProfile from '../../js/components/token/introduction/TokenIntroductionProfile';

const $routing = {generate: (val, params) => val};

let objectForTestCorrectlyMouning = {
    mocks: {$routing},
    propsData: {
        deploymentStatus: 'deployed',
        discordUrl: 'testDiscordUrl',
        editable: true,
        facebookUrl: 'testFacebookUrl',
        profileName: 'testProfileName',
        profileUrl: 'testProfileUrl',
        telegramUrl: 'testTelegramUrl',
        tokenContractAddress: 'testTokenContractAddress',
        tokenName: 'testTokenName',
        websiteUrl: 'testWebsiteUrl',
        youtubeClientId: 'testYoutubeClientId',
        youtubeChannelId: 'testYoutubeChannelId',
        tokenUrl: 'http://localhost/token/testTokenName',
    },
 };

describe('TokenIntroductionProfile', () => {
    it('renders correctly with assigned props', () => {
        const wrapper = shallowMount(TokenIntroductionProfile, objectForTestCorrectlyMouning);
        expect(wrapper.html()).to.contain('testProfileName');
        expect(wrapper.html()).to.contain('testProfileUrl');
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
        expect(wrapper.find('token-website-address-view-stub').html()).to.contain('testWebsiteUrl');
        expect(wrapper.find('token-youtube-address-view-stub').html()).to.contain('testYoutubeChannelId');
        expect(wrapper.find('token-youtube-address-view-stub').html()).to.contain('testYoutubeClientId');
        expect(wrapper.find('token-facebook-address-view-stub').html()).to.contain('testFacebookUrl');
        expect(wrapper.find('.justify-content-start').html()).to.contain('testDiscordUrl');
        expect(wrapper.find('.truncate-address').html()).to.contain('testTokenContractAddress');
        expect(wrapper.find('copy-link-stub').html()).to.contain('testTokenContractAddress');
    });

    it('should compute description correctly', () => {
        const wrapper = shallowMount(TokenIntroductionProfile, objectForTestCorrectlyMouning);
        wrapper.vm.twitterDescription = 'foo ';
        expect(wrapper.vm.description).to.be.equal('foo http://localhost/token/testTokenName');
    });

    it('should compute editingUrlsIcon correctly', () => {
        const wrapper = shallowMount(TokenIntroductionProfile, objectForTestCorrectlyMouning);
        expect(wrapper.vm.editingUrlsIcon).to.be.equal('edit');

        wrapper.vm.editingUrls = true;
        expect(wrapper.vm.editingUrlsIcon).to.be.equal('times');
    });

    it('should compute showEditIcon correctly', () => {
        const wrapper = shallowMount(TokenIntroductionProfile, objectForTestCorrectlyMouning);
        wrapper.vm.editable = true;
        expect(wrapper.vm.showEditIcon).to.be.true;

        wrapper.vm.editable = false;
        expect(wrapper.vm.showEditIcon).to.be.false;
    });

    it('should compute isTokenDeployed correctly', () => {
        const wrapper = shallowMount(TokenIntroductionProfile, objectForTestCorrectlyMouning);
        expect(wrapper.vm.isTokenDeployed).to.be.true;

        wrapper.vm.deploymentStatus = 'foo';
        expect(wrapper.vm.isTokenDeployed).to.be.false;
    });

    it('should set currentWebsite correctly when the function saveWebsite() is called', () => {
        const wrapper = shallowMount(TokenIntroductionProfile, objectForTestCorrectlyMouning);
        wrapper.vm.saveWebsite('foo');
        expect(wrapper.vm.currentWebsite).to.be.equal('foo');
    });

    it('should set currentDiscord correctly when the function saveDiscord() is called', () => {
        const wrapper = shallowMount(TokenIntroductionProfile, objectForTestCorrectlyMouning);
        wrapper.vm.saveDiscord('foo');
        expect(wrapper.vm.currentDiscord).to.be.equal('foo');
    });

    it('should set currentFacebook correctly when the function saveFacebook() is called', () => {
        const wrapper = shallowMount(TokenIntroductionProfile, objectForTestCorrectlyMouning);
        wrapper.vm.saveFacebook('foo');
        expect(wrapper.vm.currentFacebook).to.be.equal('foo');
    });

    it('should set currentTelegram correctly when the function saveTelegram() is called', () => {
        const wrapper = shallowMount(TokenIntroductionProfile, objectForTestCorrectlyMouning);
        wrapper.vm.saveTelegram('foo');
        expect(wrapper.vm.currentTelegram).to.be.equal('foo');
    });

    it('should set currentYoutube correctly when the function saveYoutube() is called', () => {
        const wrapper = shallowMount(TokenIntroductionProfile, objectForTestCorrectlyMouning);
        wrapper.vm.saveYoutube('foo');
        expect(wrapper.vm.currentYoutube).to.be.equal('foo');
    });

    it('should set editingDiscord, editingTelegram and editingWebsite correctly when the function toggleEdit() is called', () => {
        const wrapper = shallowMount(TokenIntroductionProfile, objectForTestCorrectlyMouning);
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
