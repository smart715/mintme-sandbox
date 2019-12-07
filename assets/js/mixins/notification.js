export default {
    methods: {
        notifyError: function(message) {
            this.sendNotification(message, 'error');
        },
        notifyInfo: function(message) {
            this.sendNotification(message, 'info');
        },
        notifySuccess: function(message) {
            this.sendNotification(message, 'success');
        },
        notifyWarning: function(message) {
            this.sendNotification(message, 'warning');
        },
        sendNotification: function(message, type, duration = null) {
            this.$toasted.show(
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
