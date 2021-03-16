import i18n from './utils/i18n/i18n';
import {HTTP_OK} from './utils/constants';
import {NotificationMixin} from './mixins/';
import {minLength, maxLength} from 'vuelidate/lib/validators';
import {phoneVerificationCode} from './utils/constants';

new Vue({
    el: '#phone-verification',
    i18n,
    mixins: [NotificationMixin],
    data() {
        return {
            code: '',
        };
    },
    mounted() {
        this.code = this.$refs.code.getAttribute('value');
    },
    computed: {
        disableSave: function() {
            return this.$v.$invalid;
        },
    },
    methods: {
        sendVerificationCode: function() {
            this.$axios.single.get(this.$routing.generate('send_phone_verification_code'))
                .then((response) => {
                    if (HTTP_OK === response.status && response.data.hasOwnProperty('code')) {
                        this.notifySuccess(response.data.code);
                    } else if (HTTP_OK === response.status && response.data.hasOwnProperty('error')) {
                        this.notifyError(response.data.error);
                    } else if (HTTP_OK === response.status && !response.data.hasOwnProperty('error')) {
                        this.notifySuccess(this.$t('phone_confirmation.sent'));
                    }
                }, () => {
                    this.notifyError(this.$t('toasted.error.try_later'));
                });
        },
    },
    validations() {
        return {
            code: {
                helpers: phoneVerificationCode,
                minLength: minLength(6),
                maxLength: maxLength(6),
            },
        };
    },
});
