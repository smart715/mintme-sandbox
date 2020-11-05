<template>
    <div>
        <button @click="notificationModalToggle" class="btn btn-primary btn-block">
            Notification Manage
            <!--{{ $t(userNotification.manage) }}-->
        </button>

        <notifications-management-modal
            :notification-types="notificationTypes"
            :notification-channels="notificationChannels"
            :user-notifications-config="userNotificationsConfig"
            :visible="visible"
            :noClose="noClose"
            @close="closeModal"
        >
        </notifications-management-modal>
    </div>
</template>

<script>
import NotificationsManagementModal from '../modal/NotificationsManagementModal.vue';

export default {
    name: 'NotificationManagement',
    components: {
        NotificationsManagementModal,
    },
    data() {
        return {
            visible: false,
            noClose: false,
            notificationTypes: [],
            notificationChannels: [],
            userNotificationsConfig: [],
        };
    },
    created() {
        this.fetchUserNotificationsConfig();
        this.fetchNotificationTypes();
        this.fetchNotificationChannels();
    },
    methods: {
        fetchUserNotificationsConfig: function() {
            this.$axios.retry.get(this.$routing.generate('user_notifications_config'))
                .then((res) => {
                    this.userNotificationsConfig = res.data;
                })
                .catch((err) => {
                    this.sendLogs('error', 'Error loading User Notifications channels', err);
                });
        },
        fetchNotificationChannels: function() {
            this.$axios.retry.get(this.$routing.generate('notification_channels'))
                .then((res) => {
                    this.notificationChannels = res.data;
                })
                .catch((err) => {
                    this.sendLogs('error', 'Error loading Notifications type', err);
                });
        },
        fetchNotificationTypes: function() {
            this.$axios.retry.get(this.$routing.generate('notification_types'))
                .then((res) => {
                    this.notificationTypes = res.data;
                })
                .catch((err) => {
                    this.sendLogs('error', 'Error loading Notifications type', err);
                });
        },
        closeModal: function() {
            this.visible = false;
        },
        notificationModalToggle: function() {
            this.visible = !this.visible;
        },
    },
    computed: {

    },
};

</script>
