import NotificationMixin from './notification';
import {HTTP_ACCESS_DENIED, HTTP_INTERNAL_SERVER_ERROR} from '../utils/constants.js';

export default {
    mixins: [NotificationMixin],
    methods: {
        handleOrderError: function(error) {
            if (!error.status) {
                this.notifyError(this.$t('toasted.error.network'));
            } else if (HTTP_INTERNAL_SERVER_ERROR === error.response.status && error.response.data.error) {
                this.notifyError(error.response.data.error);
            } else if (HTTP_ACCESS_DENIED === error.response.status) {
                this.notifyError(error.response.data.message);
            } else {
                this.showNotification();
            }
        },
        showNotification: function({result, message} = {}) {
            const success = 1 === result;
            message = message ||
                (success ? this.$t('place_order.created') : this.$t('place_order.failed'));
            this.sendNotification(message, success ? 'success' : 'error');
        },
        showTokenNotDeployedNotification: function(tokenName, alreadyNotified = false) {
            if (alreadyNotified) {
                return this.notifyInfo(this.$t('token.not_deployed_response.already_notified'));
            }

            return this.$toasted.show(
                `<span class="toast-text">${this.$t('token.not_deployed_response.p_1')}</span>`,
                {
                    type: 'info',
                    icon: `icon-info`,
                    duration: '10000',
                    action: {
                        text: this.$t('token.not_deployed_response.p_2'),
                        class: 'notify-button',
                        onClick: (e, toastObject) => {
                            this.$axios.single.post(this.$routing.generate('send_deploy_notification', {
                                tokenName,
                            }))
                                .then(() => {
                                    toastObject.text(this.$t('token.not_deployed_response.sent'));
                                    toastObject.goAway(5000);
                                })
                                .catch((error) => {
                                    this.$logger.error('Can not send deploy notification', error);
                                });
                        },
                    },
                }
            );
        },
    },
};
