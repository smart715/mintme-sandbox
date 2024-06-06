<template>
    <div ref="content" class="d-none">
        <slot></slot>
    </div>
</template>
<script>
import {NotificationMixin} from '../mixins';
import {AUTO_LOGOUT_TOAST_DURATION, notificationType, toastType, USER_ACTIVITY_EVENTS} from '../utils/constants';

export default {
    name: 'Notification',
    mixins: [NotificationMixin],
    props: {
        typeProp: String,
        duration: {
            type: Number,
            default: 10000,
        },
    },
    data() {
        return {
            type: this.typeProp,
            toast: null,
        };
    },
    mounted: function() {
        this.setUp();
    },
    methods: {
        setUp: function() {
            this.type = notificationType.DANGER === this.type ? toastType.ERROR : this.type;
            this.type = notificationType.PRIMARY === this.type ? toastType.INFO : this.type;

            if (notificationType.AUTO_LOGOUT === this.type) {
                this.userIsAutoLoggedOut();

                return;
            }

            this.sendNotification(this.$refs.content.innerHTML, this.type, this.duration);
        },
        userIsAutoLoggedOut: function() {
            this.toast = this.sendNotification(
                this.$refs.content.innerHTML,
                toastType.ERROR,
                AUTO_LOGOUT_TOAST_DURATION
            );

            USER_ACTIVITY_EVENTS.forEach((event) => {
                document.addEventListener(event, this.userIsBack);
            });
        },
        userIsBack: function() {
            USER_ACTIVITY_EVENTS.forEach((event) => {
                document.removeEventListener(event, this.userIsBack);
            });

            setTimeout(() => {
                this.toast.goAway();
            }, this.duration);
        },
    },
};
</script>
