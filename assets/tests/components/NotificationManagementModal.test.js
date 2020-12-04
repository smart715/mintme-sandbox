import {createLocalVue, shallowMount} from '@vue/test-utils';
import NotificationManagementModal from '../../js/components/modal/NotificationsManagementModal';
import moxios from 'moxios';
import VueScroll from 'vuescroll';
import NotificationBell from 'vue-notification-bell';
import Axios from '../../js/axios';
import Vuex from 'vuex';


let userNotificationConfig = {
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
    beforeEach(() => {
        moxios.install();
    });

    afterEach(() => {
        moxios.uninstall();
    });

    const $routing = {generate: () => 'URL'};
    const localVue = createLocalVue();
    localVue.use(Axios);
    localVue.use(Vuex);
    localVue.use(VueScroll, NotificationBell);
    localVue.use({
        install(Vue, options) {
            Vue.prototype.$t = (val) => val;
        },
    });

    const wrapper = shallowMount(NotificationManagementModal, {
        localVue,
        stubs: {
            'font-awesome-icon': true,
        },
        mocks: {
            $routing,
        },
        propsData: {
            loading: false,
            saving: false,
            userConfig: {},
            userConfigModel: {},
        },

    });

    it('show spinner when notification configuration is not loaded yet', () => {
        moxios.stubRequest('user-notifications-config', {
            status: 200,
            response: userNotificationConfig,
        });
        expect(wrapper.find('font-awesome-icon').exists()).toBe(false);
        moxios.wait(() => {
            expect(wrapper.find('font-awesome-icon').exists()).toBe(true);
            done();
        });
    });

    it('emit "close" when the function closeModal() is called', () => {
        wrapper.vm.closeModal();
        expect(wrapper.emitted('close').length).toBe(1);
    });
});
