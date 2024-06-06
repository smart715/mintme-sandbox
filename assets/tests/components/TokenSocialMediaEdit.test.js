import {shallowMount, createLocalVue} from '@vue/test-utils';
import TokenSocialMediaEdit from '../../js/components/token/TokenSocialMediaEdit';

const localVue = mockVue();
/**
 * @return {VueConstructor}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.use({
        install(Vue) {
            Vue.prototype.$t = (val) => val;
            Vue.prototype.$routing = {generate: (val) => val};
        },
    });

    return localVue;
}

/**
 * @param {Object} props
 * @return {Object}
 */
function createSharedTestProps(props = {}) {
    return {
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
        ...props,
    };
}

/**
 * @param {Object} props
 * @return {Wrapper<Vue>}
 */
function mockTokenSocialMediaEdit(props = {}) {
    return shallowMount(TokenSocialMediaEdit, {
        localVue: localVue,
        propsData: createSharedTestProps(props),
    });
}

describe('TokenSocialMediaEdit', () => {
    it('renders correctly with assigned props', () => {
        const wrapper = mockTokenSocialMediaEdit();
        expect(wrapper.findComponent('token-website-address-stub').html()).toContain('testWebsiteUrl');
        expect(wrapper.findComponent('token-website-address-stub').html()).toContain('testTokenName');
        expect(wrapper.findComponent('token-youtube-address-stub').html()).toContain('testYoutubeChannelId');
        expect(wrapper.findComponent('token-youtube-address-stub').html()).toContain('testYoutubeClientId');
        expect(wrapper.findComponent('token-youtube-address-stub').html()).toContain('testTokenName');
        expect(wrapper.findComponent('token-facebook-address-stub').html()).toContain('testFacebookUrl');
        expect(wrapper.findComponent('token-facebook-address-stub').html()).toContain('testTokenName');
        expect(wrapper.findComponent('token-telegram-channel-stub').html()).toContain('testTelegramUrl');
        expect(wrapper.findComponent('token-telegram-channel-stub').html()).toContain('testTokenName');
        expect(wrapper.findComponent('token-discord-channel-stub').html()).toContain('testDiscordUrl');
        expect(wrapper.findComponent('token-discord-channel-stub').html()).toContain('testTokenName');
    });

    it('should set currentWebsite correctly when the function saveWebsite() is called', () => {
        const wrapper = mockTokenSocialMediaEdit();
        wrapper.vm.saveWebsite('foo');
        expect(wrapper.vm.currentWebsite).toBe('foo');
        expect(wrapper.vm.reRenderTokenWebsite).toBe(1);
        expect(wrapper.vm.editingWebsite).toBe(false);
        expect(wrapper.emitted('updated-website')).toBeTruthy();
    });

    it('should set currentDiscord correctly when the function saveDiscord() is called', () => {
        const wrapper = mockTokenSocialMediaEdit();
        wrapper.vm.saveDiscord('foo');
        expect(wrapper.vm.currentDiscord).toBe('foo');
        expect(wrapper.emitted('updated-discord')).toBeTruthy();
    });

    it('should set currentFacebook correctly when the function saveFacebook() is called', () => {
        const wrapper = mockTokenSocialMediaEdit();
        wrapper.vm.saveFacebook('foo');
        expect(wrapper.vm.currentFacebook).toBe('foo');
        expect(wrapper.emitted('updated-facebook')).toBeTruthy();
    });

    it('should set currentTelegram correctly when the function saveTelegram() is called', () => {
        const wrapper = mockTokenSocialMediaEdit();
        wrapper.vm.saveTelegram('foo');
        expect(wrapper.vm.currentTelegram).toBe('foo');
        expect(wrapper.emitted('updated-telegram')).toBeTruthy();
    });

    it('should set currentYoutube correctly when the function saveYoutube() is called', () => {
        const wrapper = mockTokenSocialMediaEdit();
        wrapper.vm.saveYoutube('foo');
        expect(wrapper.vm.currentYoutube).toBe('foo');
        expect(wrapper.emitted('updated-youtube')).toBeTruthy();
    });

    it(
        `should set editingDiscord, editingTelegram and editingWebsite correctly 
        when the function toggleEdit() is called`,
        async () => {
            const wrapper = mockTokenSocialMediaEdit();
            await wrapper.setData({
                editingDiscord: true,
                editingTelegram: true,
                editingWebsite: true,
            });

            wrapper.vm.toggleEdit();
            expect(wrapper.vm.editingDiscord).toBe(false);
            expect(wrapper.vm.editingTelegram).toBe(false);
            expect(wrapper.vm.editingWebsite).toBe(false);

            await wrapper.setData({
                editingDiscord: false,
                editingTelegram: true,
                editingWebsite: true,
            });

            wrapper.vm.toggleEdit('discord');
            expect(wrapper.vm.editingDiscord).toBe(true);
            expect(wrapper.vm.editingTelegram).toBe(false);
            expect(wrapper.vm.editingWebsite).toBe(false);

            await wrapper.setData({
                editingDiscord: true,
                editingTelegram: false,
                editingWebsite: true,
            });

            wrapper.vm.toggleEdit('telegram');
            expect(wrapper.vm.editingDiscord).toBe(false);
            expect(wrapper.vm.editingTelegram).toBe(true);
            expect(wrapper.vm.editingWebsite).toBe(false);

            await wrapper.setData({
                editingDiscord: true,
                editingTelegram: true,
                editingWebsite: false,
            });

            wrapper.vm.toggleEdit('website');
            expect(wrapper.vm.editingDiscord).toBe(false);
            expect(wrapper.vm.editingTelegram).toBe(false);
            expect(wrapper.vm.editingWebsite).toBe(true);
        }
    );
});
