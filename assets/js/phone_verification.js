import i18n from './utils/i18n/i18n';
import {HTTP_OK} from './utils/constants';
import {NotificationMixin} from './mixins/';

new Vue({
    el: '#phone-verification',
    i18n,
    mixins: [NotificationMixin],
    methods: {
        sendVerificationCode: function() {
            this.$axios.single.get(this.$routing.generate('send_phone_verification_code'))
                .then((response) => {
                    if (HTTP_OK === response.status && response.data.hasOwnProperty('error')) {
                        this.notifyError(response.data.error);
                    }

                    if (HTTP_OK === response.status && !response.data.hasOwnProperty('error')) {
                        this.notifySuccess(this.$t('phone_confirmation.sent'));
                    }
                }, () => {
                    this.notifyError(this.$t('toasted.error.try_later'));
                });
        },
    },
});
