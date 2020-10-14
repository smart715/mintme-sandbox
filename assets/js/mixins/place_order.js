import NotificationMixin from './notification';

export default {
    mixins: [NotificationMixin],
    methods: {
        handleOrderError: function(error) {
            if (!error.status) {
                this.notifyError(this.$t('toasted.error.network'));
            } else {
                this.showNotification();
            }
        },
        showNotification: function({result, message} = {}) {
            let success = 1 === result;
            message = message ||
                (success ? this.$t('place_order.created') : this.$t('place_order.failed'));
            this.sendNotification(message, success ? 'success' : 'error');
        },
    },
};
