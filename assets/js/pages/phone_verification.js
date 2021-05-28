import i18n from '../utils/i18n/i18n';
import {HTTP_OK} from '../utils/constants';
import {NotificationMixin, LoggerMixin} from '../mixins/';
import {minLength, maxLength} from 'vuelidate/lib/validators';
import {phoneVerificationCode} from '../utils/constants';
const disabledResendFor = 1000 * 60;

new Vue({
    el: '#phone-verification',
    i18n,
    mixins: [
        NotificationMixin,
        LoggerMixin,
    ],
    data() {
        return {
            code: '',
            resendCodeDisabled: true,
            sendCode: false,
        };
    },
    mounted() {
        this.sendCode = !!this.$refs.sendCode.value;

        const errorsCount = parseInt(
            this.$refs.resendCode.getAttribute('data-errors-count')
        );

        const limitReached = parseInt(
            this.$refs.resendCode.getAttribute('data-limit-reached')
        );

        const failedAttempts = parseInt(
            this.$refs.resendCode.getAttribute('data-failed-attempts')
        );

        if (limitReached && errorsCount) {
            this.notifyError(this.$t('phone_confirmation.limit_reached', {
                limit: failedAttempts,
            }));

            return;
        }

        if (!this.sendCode) {
            this.handleResendCodeDesabled();
        } else if (!errorsCount) {
            this.resendCodeDisabled = false;
            this.sendVerificationCode();
        }

        this.code = this.$refs.code.getAttribute('value');
    },
    computed: {
        disableSave: function() {
            return this.$v.$invalid;
        },
    },
    methods: {
        sendVerificationCode: function() {
            if (this.resendCodeDisabled) {
                return;
            }

            this.resendCodeDisabled = true;
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
                }).then(() => setTimeout(() => this.resendCodeDisabled = false, disabledResendFor));
        },
        handleResendCodeDesabled: function() {
            this.$axios.single.get(this.$routing.generate('is_able_send_code_disabled'))
                .then((response) => {
                    let sendCodeDiffModel = response.data;
                    this.resendCodeDisabled = !sendCodeDiffModel.sendCodeEnabled;

                    if (sendCodeDiffModel.sendCodeDiff >= 0 ) {
                        setTimeout(
                            () => this.resendCodeDisabled = false,
                            sendCodeDiffModel.sendCodeDiff * 1000
                        );
                    }
                })
                .catch((error) => this.sendLogs('error', 'Can not load crypto balance.', error));
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
