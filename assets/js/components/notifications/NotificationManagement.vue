<template>
    <div>
        <button @click="notificationModalToggle" class="btn btn-primary btn-block">
            Notification Manage
            <!--{{ $t(userNotification.manage) }}-->
        </button>

        <notifications-management-modal
            :visible="visible"
            :noClose="noClose"
            :userConfig="userConfig"
            :loading="loading"
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
            userConfig: {},
            loading: false,
        };
    },
    methods: {
        fetchUserNotificationsConfig: function() {
            this.loading = true;
            this.$axios.retry.get(this.$routing.generate('user_notifications_config'))
                .then((res) => {
                    this.userConfig = res.data;
                    this.loading = false;
                })
                .catch((err) => {
                    this.sendLogs('error', 'Error loading User Notifications channels', err);
                });
        },
        closeModal: function() {
            this.visible = false;
        },
        notificationModalToggle: function() {
            this.visible = !this.visible;
            if (this.visible) {
                this.fetchUserNotificationsConfig();
            }
        },
    },
};

</script>
