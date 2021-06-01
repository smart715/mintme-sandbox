<template>
  <div class="dropdown" v-on-clickaway="hideUserNotifications">
      <a
          class="nav-link pl-1 dropdown-toggle c-pointer"
          aria-haspopup="true"
          :aria-expanded="showUserNotifications"
          @click="toggleUserNotifications"
          tabindex="0"
          @keyup.enter="toggleUserNotifications"
      >
          <notification-bell
              class="bell-notification"
              :size="23"
              :count="unreadNotifications"
              counterStyle="round"
              counterBackgroundColor="#FF0000"
              counterTextColor="#FFFFFF"
              iconColor="#ebebeb"
          />
      </a>
      <div
          class="dropdown-menu dropdown-menu-right align-self-lg-center bell-notification-dropdown"
          :class="{ 'show': showUserNotifications }"
      >
          <div class="notification-header">
              <span> {{ $t('userNotification.title') }}</span>
          </div>
          <vue-scroll
              ref="notificationsScroll"
              :ops="scrollOps"
              @handle-scroll="handleScroll"
              v-bind:class="scrollClass"
          >
              <div class="notification-container">
                  <template v-if="loaded">
                      <template v-if="hasNotifications">
                          <div
                              v-for="notification in userNotificationsFiltered"
                              :key="notification.id"
                              class="notification-body"
                          >
                              <NotificationType :notification="notification"/>
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
import NotificationBell from 'vue-notification-bell';
import VueScroll from 'vuescroll';
import {mixin as clickaway} from 'vue-clickaway';
import NotificationType from './NotificationType';
import {LoggerMixin} from '../mixins';

library.add(faCircleNotch);

const MAX_NOTIFICATIONS = 90;
export default {
    components: {
        NotificationBell,
        VueScroll,
        NotificationType,
        FontAwesomeIcon,
    },
    mixins: [clickaway, LoggerMixin],
    data() {
        return {
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
            let filtered = this.userNotificationsFiltered;
            let notificationCount = 0;
            this.userNotifications.forEach((item) => {
                let existNotification = filtered.includes(item);
                if (!existNotification && MAX_NOTIFICATIONS > notificationCount) {
                    notificationCount++;
                    filtered.push(item);
                }
            });
            this.userNotificationsFiltered = filtered;
        },
        handleScroll: function(vertical, horizontal, native) {
            if (vertical.scrollTop >= ( native.explicitOriginalTarget.scrollHeight - native.target.clientHeight)) {
                this.loadNotifications();
            }
        },
        updateReadNotifications: function() {
            this.$axios.retry.get(this.$routing.generate('update_read_notifications'))
                .catch((err) => {
                    this.sendLogs('error', 'Error Updating Notifications', err);
                });
        },
        toggleUserNotifications: function() {
            this.$refs['notificationsScroll'].scrollTo({y: 0}, 0, 'easeInQuad');
            this.showUserNotifications = !this.showUserNotifications;
            if (this.showUserNotifications && this.unreadNotifications > 0) {
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
        fetchUserNotifications: function() {
            this.$axios.retry.get(this.$routing.generate('user_notifications'))
                .then((res) => {
                    this.userNotifications = res.data;
                    this.loadNotifications();
                })
                .catch((err) => {
                    this.sendLogs('error', 'Error loading Notifications', err);
                });
        },
    },
    computed: {
        hasNotifications: function() {
            return this.userNotificationsFiltered.length > 0;
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
