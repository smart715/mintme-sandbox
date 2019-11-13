import NotificationMixin from './notification';

export default {
    mixins: [NotificationMixin],
    methods: {
        handleOrderError: function(error) {
            if (!error.status) {
                this.sendNotification('Network Error!', 'error');
            } else {
                this.showNotification();
            }
        },
        showNotification: function({result, message} = {}) {
            let success = 1 === result;
            message = message ? message : (success ? 'Order Created' : 'Order Failed');
            this.sendNotification(message, success ? 'success' : 'error');
        },
    },
};
