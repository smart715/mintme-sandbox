<template>
    <div>
        <button @click="notificationModalToggle" class="btn btn-primary btn-block">
            Notification Manage
            <!--{{ $t(userNotification.manage) }}-->
        </button>

        <notifications-management-modal
            :notifications-type="notificationsType"
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
            notificationsType: [
                {
                    id: 1,
                    type: 'deployed',
                    email: true,
                    website: true,
                },
                {
                    id: 2,
                    type: 'withdrawal',
                    email: true,
                    website: false,
                },
                {
                    id: 3,
                    type: 'deposit',
                    email: false,
                    website: true,
                },
            ],
        };
    },
    mounted() {
        this.fetchNotificationsTypes();
    },
    methods: {
        fetchNotificationsTypes: function() {
            this.$axios.retry.get(this.$routing.generate('notifications_type'))
                .then((res) => {
                    console.log(res);
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
