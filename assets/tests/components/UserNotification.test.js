import {shallowMount, createLocalVue} from '@vue/test-utils';
import UserNotification from '../../js/components/UserNotification';
import NotificationType from '../../js/components/NotificationType';
import Axios from '../../js/axios';
import moxios from 'moxios';
import Vuex from 'vuex';
import VueScroll from 'vuescroll';
import NotificationBell from 'vue-notification-bell';


describe('UserNotification', () => {
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


    const wrapper = shallowMount(UserNotification, {
        localVue,
        stubs: {
            'vue-scroll': VueScroll,
            'notification-bell': NotificationBell,
            'font-awesome-icon': true,
            'notification-type': NotificationType,
        },
        mocks: {
            $routing,
        },
        propsData: {
            showUserNotifications: true,
            userNotifications: null,
            userNotificationsFiltered: [],
            scrollOps: {
                bar: {
                    background: '#D3D3D3',
                },
            },
        },

    });

    let notification = [{
        id: 65,
        type: 'withdrawal',
        viewed: true,
        extraData: null,
    }];

    it('show spinner when notification is not loaded yet', () => {
        moxios.stubRequest('user-notifications', {
            status: 200,
            response: [],
        });
        moxios.wait(() => {
            expect(wrapper.find('font-awesome-icon').exists()).toBe(true);
            done();
        });
    });

    it('show "No notification yet" message', () => {
        wrapper.vm.userNotifications = [];
        expect(wrapper.html().includes('userNotification.no_notifications_yet')).toBe(true);
    });

    it('show user notifications', () => {
        wrapper.setData({userNotifications: notification});
        wrapper.setData({userNotificationsFiltered: notification});
        expect(wrapper.find(NotificationType).exists()).toBe(true);
    });
});
