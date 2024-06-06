import {shallowMount, createLocalVue} from '@vue/test-utils';
import TokenSocialMediaIcons from '../../js/components/token/TokenSocialMediaIcons';

const localVue = mockVue();

/**
 * @return {Wrapper<Vue>}
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
        facebookUrl: 'testFacebookUrl',
        telegramUrl: 'testTelegramUrl',
        tokenName: 'testTokenName',
        tokenUrl: 'localhost://testTokenName',
        websiteUrl: 'testWebsiteUrl',
        youtubeChannelId: 'testYoutubeChannelId',
        ...props,
    };
}

describe('TokenSocialMediaIcons', () => {
    let wrapper;

    beforeEach(() => {
        wrapper = shallowMount(TokenSocialMediaIcons, {
            localVue: localVue,
            stubs: ['social-sharing', 'network', 'font-awesome-icon', 'b-dropdown'],
            propsData: createSharedTestProps(),
            attachTo: document.body,
        });
    });

    it('renders correctly with props', () => {
        expect(wrapper.findComponent('a[href=\'testDiscordUrl\']').exists()).toBe(true);
        expect(wrapper.findComponent('a[href=\'testFacebookUrl\']').exists()).toBe(true);
        expect(wrapper.findComponent('a[href=\'testTelegramUrl\']').exists()).toBe(true);
        expect(wrapper.findComponent('a[href=\'testWebsiteUrl\']').exists()).toBe(true);
        expect(wrapper.findComponent('a[href=\'https://www.youtube.com/channel/testYoutubeChannelId\']')
            .exists())
            .toBe(true);
    });

    it('should compute description correctly', async () => {
        await wrapper.setData({
            twitterDescription: 'foo',
        });

        expect(wrapper.vm.description).toBe('foolocalhost://testTokenName');
    });

    it('should compute youtubeUrl correctly', () => {
        expect(wrapper.vm.youtubeUrl).toBe('https://www.youtube.com/channel/testYoutubeChannelId');
    });

    it('doesnt show unsetted urls', async () => {
        await wrapper.setProps({
            discordUrl: '',
            facebookUrl: '',
            telegramUrl: '',
            tokenName: '',
            tokenUrl: '',
            websiteUrl: '',
            youtubeChannelId: '',
        });

        expect(wrapper.findAll('#token-social-media-icons > a').length).toBe(0);
    });

    describe('Check that "isNotEnoughLength" works correctly', () => {
        it('When "showSocialMediaMenu" is false', async () => {
            await wrapper.setData({
                showSocialMediaMenu: false,
            });

            wrapper.vm.toggleSocialMediaMenu();

            expect(wrapper.vm.showSocialMediaMenu).toBe(true);
        });

        it('When "showSocialMediaMenu" is true', async () => {
            await wrapper.setData({
                showSocialMediaMenu: true,
            });

            wrapper.vm.toggleSocialMediaMenu();

            expect(wrapper.vm.showSocialMediaMenu).toBe(false);
        });
    });

    it('Check that "hideSocialMediaMenu" works correctly', async () => {
        await wrapper.setData({
            showSocialMediaMenu: true,
        });

        wrapper.vm.hideSocialMediaMenu();

        expect(wrapper.vm.showSocialMediaMenu).toBe(false);
    });
});
