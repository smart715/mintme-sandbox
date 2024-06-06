import {createLocalVue, shallowMount} from '@vue/test-utils';
import NotificationsManagementAdvancedModal from '../../js/components/modal/NotificationsManagementAdvancedModal';
import moxios from 'moxios';
import axios from 'axios';

const localVue = mockVue();

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.use({
        install(Vue) {
            Vue.prototype.$t = (val) => val;
            Vue.prototype.$axios = {retry: axios, single: axios};
            Vue.prototype.$logger = {error: () => {}};
            Vue.prototype.$toasted = {show: () => {}};
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
        notificationAdvancedModalVisible: true,
        configProp: userNotificationConfig,
        ...props,
    };
}

const userTokens = [
    {
        name: 'test1',
        image: 'test1',
        value: true,
    },
    {
        name: 'test2',
        image: 'test2',
        value: true,
    },
    {
        name: 'test3',
        image: 'test3',
        value: true,
    },
];

const getTokenResponse = {
    common: {
        'test1': {},
        'test2': {},
        'test3': {},
    },
    tokensInfo: {
        'test1': {
            'image': 'test1',
            'name': 'test1',
            'value': true,
        },
        'test2': {
            'image': 'test2',
            'name': 'test2',
            'value': true,
        },
        'test3': {
            'image': 'test3',
            'name': 'test3',
            'value': true,
        },
    },
};

const userNotificationConfig = {
    new_post: {
        text: 'Post related to token you own',
        show: true,
        channels: {
            advanced: userTokens,
        },
    },
};

describe('User Token Notifications Configuration', () => {
    let wrapper;

    beforeEach(() => {
        moxios.install();

        wrapper = shallowMount(NotificationsManagementAdvancedModal, {
            localVue: localVue,
            directives: {
                'b-tooltip': {},
            },
            propsData: createSharedTestProps(),
        });
    });

    afterEach(() => {
        moxios.uninstall();
    });

    it('show user tokens when it is already initialized', async (done) => {
        moxios.stubRequest('tokens', {
            status: 200,
            response: getTokenResponse,
        });

        await wrapper.setData({
            config: userNotificationConfig,
            isLoaded: false,
        });

        await wrapper.vm.fetchTokens();

        moxios.wait(() => {
            expect(wrapper.vm.tokens).toEqual(userTokens);
            expect(wrapper.vm.isLoaded).toBe(true);
            done();
        });
    });

    it('Check that "hasTokens" returns the correct value', async () => {
        expect(wrapper.vm.hasTokens).toBe(true);

        await wrapper.setData({
            config: {
                new_post: {
                    channels: {
                        advanced: {},
                    },
                },
            },
        });

        expect(wrapper.vm.hasTokens).toBe(false);
    });

    it('Check that "truncateTokenName" truncate text correctly', async () => {
        const tokenNameLong = 'a'.repeat(13);
        const resultTokenNameLongTruncate = {
            name: 'a'.repeat(12) + '...',
            tooltip: {
                boundary: 'window',
                customClass: 'tooltip-custom',
                title: tokenNameLong,
            },
        };

        await wrapper.setData({
            truncateLimit: 12,
        });

        expect(wrapper.vm.truncateTokenName('TokenName')).toEqual({'name': 'TokenName', 'tooltip': ''});

        expect(wrapper.vm.truncateTokenName(tokenNameLong)).toEqual(resultTokenNameLongTruncate);
    });

    it('Verify that the "close" event is emitted correctly', () => {
        wrapper.vm.closeModal();

        expect(wrapper.emitted('close')).toBeTruthy();
    });

    it('Check that "saveConfig" works correctly', () => {
        const tokenName = 'Token-name-jasm';
        const result = [{
            advancedConfig: userNotificationConfig,
        }];

        wrapper.vm.saveConfig(tokenName);

        expect(wrapper.vm.disabledCheckboxSettings).toBe(true);
        expect(wrapper.vm.currentIdElementSettings).toBe(tokenName);
        expect(wrapper.emitted('save-config')).toEqual([result]);
    });

    it('Check that "resetSearchResultTokens" works correctly', async () => {
        await wrapper.setData({
            searchResultTokens: userTokens,
        });

        expect(wrapper.vm.searchResultTokens).toEqual(userTokens);

        wrapper.vm.resetSearchResultTokens();

        expect(wrapper.vm.searchResultTokens).toEqual({});
    });

    it('Check that "syncConfigTokens" works correctly', async () => {
        await wrapper.setData({
            tokens: userTokens,
        });

        wrapper.vm.syncConfigTokens();

        expect(wrapper.vm.config.new_post.channels.advanced).toEqual(userTokens);
    });

    it('Check that "searchPhraseInvalidLength" works correctly', () => {
        expect(wrapper.vm.searchPhraseInvalidLength('token-name')).toBe(false);
        expect(wrapper.vm.searchPhraseInvalidLength('DA')).toBe(true);
    });
});
