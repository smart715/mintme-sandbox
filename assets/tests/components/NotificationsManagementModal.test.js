import {createLocalVue, shallowMount} from '@vue/test-utils';
import NotificationsManagementModal from '../../js/components/modal/NotificationsManagementModal';
import moxios from 'moxios';
import VueScroll from 'vuescroll';
import axios from 'axios';
import Vuex from 'vuex';

const localVue = mockVue();

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.use(VueScroll);
    localVue.use(Vuex);
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
        loading: false,
        saving: false,
        userConfig: {},
        userConfigModel: {},
        ...props,
    };
}

const userNotificationConfig = {
    deposit: {
        text: 'Deposits',
        show: true,
        channels: {
            email: {
                text: 'Email',
                value: true,
            },
            website: {
                text: 'Website',
                value: true,
            },
        },
    },
    withdrawal: {
        text: 'Withdrawals',
        show: true,
        channels: {
            email: {
                text: 'Email',
                value: true,
            },
            website: {
                text: 'Website',
                value: true,
            },
        },
    },
    new_investor: {
        text: 'New investors',
        show: true,
        channels: {
            email: {
                text: 'Email',
                value: true,
            },
            website: {
                text: 'Website',
                value: true,
            },
        },
    },
    new_post: {
        text: 'Post related to token you own',
        show: true,
        channels: {
            email: {
                text: 'Email',
                value: true,
            },
            website: {
                text: 'Website',
                value: true,
            },
        },
    },
    deployed: {
        text: 'Newly deployed tokens you own',
        show: true,
        channels: {
            email: {
                text: 'Email',
                value: true,
            },
            website: {
                text: 'Website',
                value: true,
            },
        },
    },
};

describe('User Notifications Configuration', () => {
    let wrapper;

    beforeEach(() => {
        moxios.install();

        wrapper = shallowMount(NotificationsManagementModal, {
            localVue,
            stubs: {
                'font-awesome-icon': true,
            },
            propsData: createSharedTestProps(),
        });
    });

    afterEach(() => {
        moxios.uninstall();
    });

    it('show spinner when notification configuration is not loaded yet', () => {
        moxios.stubRequest('user-notifications-config', {
            status: 200,
            response: userNotificationConfig,
        });

        expect(wrapper.findComponent('font-awesome-icon').exists()).toBe(false);

        moxios.wait(() => {
            expect(wrapper.findComponent('font-awesome-icon').exists()).toBe(true);
            done();
        });
    });

    it('emit "close" when the function closeModal() is called', () => {
        wrapper.vm.closeModal();
        expect(wrapper.emitted('close').length).toBe(1);
    });

    it('Check if tooltipNewInvestor returns correct boolean value', async () => {
        const config = {
            text: 'Withdrawals',
        };

        await wrapper.setData({
            userConfigModel: userNotificationConfig,
        });

        expect(wrapper.vm.tooltipNewInvestor(config)).toBe(false);

        config.text = 'New investors';

        expect(wrapper.vm.tooltipNewInvestor(config)).toBe(true);
    });

    it('Verify that the "close" event is emitted correctly', async () => {
        await wrapper.setProps({
            notificationConfigModalVisibleProp: true,
        });

        wrapper.vm.closeModal();

        expect(wrapper.emitted('close')).toBeTruthy();
        expect(wrapper.vm.notificationAdvancedModalVisible).toBe(false);
    });

    it('Verify that the "isNewPostSettings" event is emitted correctly', async () => {
        const configTest = userNotificationConfig.new_post.text;

        await wrapper.setData({
            userConfigModel: userNotificationConfig,
        });

        expect(wrapper.vm.isNewPostSettings(configTest)).toBe(false);
    });

    it('Verify that the "openAdvancedModal" works correctly', async () => {
        await wrapper.setProps({
            notificationConfigModalVisibleProp: false,
        });

        wrapper.vm.openAdvancedModal();

        expect(wrapper.vm.notificationAdvancedModalVisible).toBe(true);
    });
});
