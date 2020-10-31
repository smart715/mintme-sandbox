<template>
    <div>
        <button @click="notificationModalToggle" class="btn btn-primary btn-block">
            Notification Manage
            <!--{{ $t(userNotification.manage) }}-->
        </button>

        <notifications-management-modal
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
        };
    },
    methods: {
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
