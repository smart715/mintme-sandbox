export default {
    data() {
        return {
            notifyDuration: 10000,
        };
    },
    methods: {
        notifyError: function(message, duration = this.notifyDuration) {
            return this.sendNotification(message, 'error', duration);
        },
        notifyInfo: function(message, duration = this.notifyDuration) {
            return this.sendNotification(message, 'info', duration);
        },
        notifySuccess: function(message, duration = this.notifyDuration) {
            return this.sendNotification(message, 'success', duration);
        },
        notifyWarning: function(message, duration = this.notifyDuration) {
            return this.sendNotification(message, 'warning', duration);
        },
        sendNotification: function(message, type, duration = this.notifyDuration ) {
            return this.$toasted.show(
                `<span class="toast-text">${message}</span>`,
                {
                    type,
                    icon: `icon-${type}`,
                    duration,
                }
            );
        },
    },
};
