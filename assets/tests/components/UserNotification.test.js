import {shallowMount, createLocalVue} from '@vue/test-utils';
import UserNotification from '../../js/components/UserNotification';
import NotificationType from '../../js/components/NotificationType';
import moxios from 'moxios';
import Vuex from 'vuex';
import VueScroll from 'vuescroll';
import {notificationTypes} from '../../js/utils/constants';
import moment from 'moment';
import axios from 'axios';

const localVue = mockVue();

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.use(Vuex);
    localVue.use({
        install(Vue, options) {
            Vue.prototype.$t = (val) => val;
            Vue.prototype.$axios = {retry: axios, single: axios};
            Vue.prototype.$routing = {generate: () => 'URL'};
            Vue.prototype.$logger = {error: () => {}};
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
        showUserNotifications: true,
        userNotifications: null,
        userNotificationsFiltered: [],
        scrollOps: {
            bar: {
                background: '#D3D3D3',
            },
        },
        ...props,
    };
};


const getNotificationJsonData = (tokenName) => {
    return JSON.stringify({
        tokenName,
        rewardToken: tokenName,
    });
};

const generateTestNotificationsData = (notificationType) => {
    return [
        // unread notifications that should be grouped
        {type: notificationType, date: moment().format(), jsonData: getNotificationJsonData('test')},
        {type: notificationType, date: moment().format(), jsonData: getNotificationJsonData('test')},
        {
            type: notificationType,
            date: moment().subtract(2, 'days').format(),
            jsonData: getNotificationJsonData('test'),
        },

        // already read notifications that should be grouped
        {
            type: notificationType,
            viewed: true,
            date: moment().subtract(1, 'minute').format(),
            jsonData: getNotificationJsonData('test'),
        },
        {
            type: notificationType,
            viewed: true,
            date: moment().subtract(1, 'minute').format(),
            jsonData: getNotificationJsonData('test'),
        },

        // unread notifications that should not be grouped because of date
        {
            type: notificationType,
            viewed: true,
            date: moment().subtract(2, 'days').format(),
            jsonData: getNotificationJsonData('test'),
        },

        // unread notifications that should not be grouped because of different token
        {
            type: notificationType,
            date: moment().subtract(2, 'minutes').format(),
            jsonData: getNotificationJsonData('test2'),
        },
    ];
};

const generateTestCorrectNotificationsData = (notificationType, newNotificationsType) => {
    return [
        // unread notifications that should be grouped
        {type: newNotificationsType, date: expect.any(String), jsonData: getNotificationJsonData('test'), number: 3},

        // already read notifications that should be grouped
        {
            type: newNotificationsType,
            viewed: true,
            date: expect.any(String),
            jsonData: getNotificationJsonData('test'),
            number: 2,
        },

        // unread notifications that should not be grouped because of different token
        {type: notificationType, date: expect.any(String), jsonData: getNotificationJsonData('test2'), number: 1},

        // unread notifications that should not be grouped because of date
        {
            type: notificationType,
            viewed: true,
            date: expect.any(String),
            jsonData: getNotificationJsonData('test'),
            number: 1,
        },
    ];
};

describe('UserNotification', () => {
    let wrapper;

    beforeEach(() => {
        wrapper = shallowMount(UserNotification, {
            localVue: localVue,
            stubs: {
                'vue-scroll': VueScroll,
                'font-awesome-icon': true,
                'notification-type': NotificationType,
            },
            propsData: createSharedTestProps(),

        });

        moxios.install();
    });

    afterEach(() => {
        moxios.uninstall();
        wrapper = null;
    });

    const notification = [{
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
            expect(wrapper.findComponent('font-awesome-icon').exists()).toBe(true);
            done();
        });
    });

    it('show "No notification yet" message', async () => {
        await wrapper.setData({
            userNotifications: [],
        });
        expect(wrapper.html().includes('userNotification.no_notifications_yet')).toBe(true);
    });

    it('show user notifications', async () => {
        await wrapper.setData({userNotifications: notification});
        await wrapper.setData({userNotificationsFiltered: notification});
        expect(wrapper.findComponent(NotificationType).exists()).toBe(true);
    });

    it('should group new post notifications properly', () => {
        expect(wrapper.vm.groupNotifications(generateTestNotificationsData(notificationTypes.newPost)))
            .toStrictEqual(generateTestCorrectNotificationsData(notificationTypes.newPost, notificationTypes.newPost));
    });

    it('should group new reward notification correctly', () => {
        expect(wrapper.vm.groupNotifications(generateTestNotificationsData(notificationTypes.reward_new)))
            .toStrictEqual(generateTestCorrectNotificationsData(
                notificationTypes.reward_new,
                notificationTypes.reward_new_grouped,
            ));
    });

    it('should group new bounty notification correctly', () => {
        expect(wrapper.vm.groupNotifications(generateTestNotificationsData(notificationTypes.bounty_new)))
            .toStrictEqual(generateTestCorrectNotificationsData(
                notificationTypes.bounty_new,
                notificationTypes.bounty_new_grouped,
            ));
    });
});
