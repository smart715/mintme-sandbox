<template>
    <div class="dropdown" v-on-clickaway="hideUserNotifications">
        <a
            class="nav-link pl-1 dropdown-toggle c-pointer"
            aria-haspopup="true"
            :aria-expanded="showUserNotifications"
            tabindex="0"
            @click="toggleUserNotifications"
            @keyup.enter="toggleUserNotifications"
        >
            <counter-wrapper :count="unreadNotifications">
                <font-awesome-icon :icon="['far', 'bell']" />
            </counter-wrapper>
        </a>
        <div
            class="dropdown-menu dropdown-menu-right align-self-lg-center bell-notification-dropdown"
            :class="{ 'show': showUserNotifications }"
        >
            <div class="notification-header">
                {{ $t('userNotification.title') }}
            </div>
            <vue-scroll
                ref="notificationsScroll"
                :ops="scrollOps"
                v-bind:class="scrollClass"
                @handle-scroll="handleScroll"
            >
                <div class="notification-container">
                    <template v-if="loaded">
                        <template v-if="hasNotifications">
                            <div
                                v-for="notification in userNotificationsFiltered"
                                :key="notification.id"
                                class="notification-body"
                            >
                                <NotificationType :notification="notification" :currentLocale="currentLocale" />
                            </div>
                        </template>
                        <div v-if="!hasNotifications" class="text-center notification-body">
                            {{ $t('userNotification.no_notifications_yet') }}
                        </div>
                    </template>
                    <template v-else>
                        <div class="text-center notification-body">
                            <font-awesome-icon icon="circle-notch" spin class="loading-spinner" fixed-width />
                        </div>
                    </template>
                </div>
            </vue-scroll>
        </div>
    </div>
</template>

<script>
import {library} from '@fortawesome/fontawesome-svg-core';
import {faCircleNotch} from '@fortawesome/free-solid-svg-icons';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import VueScroll from 'vuescroll';
import {mixin as clickaway} from 'vue-clickaway';
import NotificationType from './NotificationType';
import {notificationTypes, GENERAL} from '../utils/constants';
import moment from 'moment';
import CounterWrapper from './CounterWrapper.vue';
import {faBell} from '@fortawesome/free-regular-svg-icons';

library.add(faCircleNotch, faBell);

const MAX_NOTIFICATIONS = 90;
export default {
    name: 'UserNotification',
    components: {
        VueScroll,
        NotificationType,
        FontAwesomeIcon,
        CounterWrapper,
    },
    mixins: [clickaway],
    props: {
        currentLocale: String,
    },
    data() {
        return {
            notificationTypes,
            showUserNotifications: false,
            userNotifications: null,
            userNotificationsFiltered: [],
            scrollOps: {
                bar: {
                    background: '#D3D3D3',
                },
            },
        };
    },
    created() {
        this.fetchUserNotifications();
    },
    mounted() {
        this.$refs['notificationsScroll'].scrollTo({y: 0}, 0, 'easeInQuad');
    },
    methods: {
        loadNotifications: function() {
            const filtered = this.userNotificationsFiltered;
            let notificationCount = 0;
            this.userNotifications.forEach((item) => {
                const existNotification = filtered.includes(item);
                if (!existNotification && MAX_NOTIFICATIONS > notificationCount) {
                    notificationCount++;
                    filtered.push(item);
                }
            });
            this.userNotificationsFiltered = filtered;
        },
        handleScroll: function(vertical, horizontal, native) {
            if (vertical.scrollTop >= (native.target.scrollHeight - native.target.clientHeight)) {
                this.loadNotifications();
            }
        },
        updateReadNotifications: function() {
            this.$axios.retry.get(this.$routing.generate('update_read_notifications'))
                .catch((err) => {
                    this.$logger.error('Error Updating Notifications', err);
                });
        },
        toggleUserNotifications: function() {
            this.$refs['notificationsScroll'].scrollTo({y: 0}, 0, 'easeInQuad');
            this.showUserNotifications = !this.showUserNotifications;
            if (this.showUserNotifications && 0 < this.unreadNotifications) {
                this.updateReadNotifications();
                this.userNotificationsFiltered.forEach((item) => {
                    this.$set(item, 'viewed', true);
                });
            }
        },
        hideUserNotifications: function() {
            this.showUserNotifications = false;
            this.$refs['notificationsScroll'].scrollTo({y: 0}, 0, 'easeInQuad');
        },
        groupNotificationByType(userNotifications, type, newType, slugFunc) {
            const notifications = {};
            const viewedNotifications = {};
            const notificationsToDelete = [];

            userNotifications.forEach((item) => {
                if (type !== item.type) {
                    return;
                }

                const jsonData = JSON.parse(item.jsonData);

                if (item.viewed) {
                    const tokenSlug = slugFunc(item, jsonData) + ' ' + moment(item.date).format(GENERAL.date);

                    if (undefined === viewedNotifications[tokenSlug]) {
                        item.number = 1;
                        viewedNotifications[tokenSlug] = item;
                    } else {
                        viewedNotifications[tokenSlug].number += 1;
                        viewedNotifications[tokenSlug].type = newType || type;
                    }
                } else {
                    if (undefined === notifications[jsonData.tokenName]) {
                        item.number = 1;
                        notifications[jsonData.tokenName] = item;
                    } else {
                        notifications[jsonData.tokenName].number += 1;
                        notifications[jsonData.tokenName].type = newType || type;
                    }
                }

                notificationsToDelete.push(item);
            });

            userNotifications = userNotifications.filter((post) => !notificationsToDelete.includes(post));

            for (const notification in viewedNotifications) {
                if (viewedNotifications.hasOwnProperty(notification)) {
                    userNotifications.unshift(viewedNotifications[notification]);
                }
            }

            for (const notification in notifications) {
                if (notifications.hasOwnProperty(notification)) {
                    userNotifications.unshift(notifications[notification]);
                }
            }

            userNotifications.sort(function(a, b) {
                return new Date(b.date) - new Date(a.date);
            });

            return userNotifications;
        },
        fetchUserNotifications: function() {
            this.$axios.retry.get(this.$routing.generate('user_notifications'))
                .then((res) => {
                    this.userNotifications = this.groupNotifications(res.data || []);
                    this.loadNotifications();
                })
                .catch((err) => {
                    this.$logger.error('Error loading Notifications', err);
                });
        },
        groupNotifications(notifications) {
            notifications = this.groupNotificationByType(
                notifications,
                notificationTypes.newPost,
                notificationTypes.newPost,
                (notification, jsonData) => jsonData.tokenName,
            );
            notifications = this.groupNotificationByType(
                notifications,
                notificationTypes.reward_new,
                notificationTypes.reward_new_grouped,
                (notification, jsonData) => jsonData.rewardToken,
            );
            notifications = this.groupNotificationByType(
                notifications,
                notificationTypes.bounty_new,
                notificationTypes.bounty_new_grouped,
                (notification, jsonData) => jsonData.rewardToken,
            );

            return notifications;
        },
    },
    computed: {
        hasNotifications: function() {
            return 0 < this.userNotificationsFiltered.length;
        },
        loaded: function() {
            return !!this.userNotifications;
        },
        unreadNotifications: function() {
            return this.userNotifications ?
                this.userNotifications.filter((item) => !item.viewed).length : 0;
        },
        scrollClass: function() {
            return this.userNotificationsFiltered.length
                ? ''
                : 'static-container-notification';
        },
    },
};
</script>
