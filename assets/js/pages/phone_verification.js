import i18n from '../utils/i18n/i18n';
import {
    HTTP_LOCKED,
    HTTP_OK,
    HTTP_UNAUTHORIZED,
    PHONE_VERIF_REQUEST_CODE_INTERVAL,
    EMAIL_VERIF_REQUEST_CODE_INTERVAL,
    TIMERS,
} from '../utils/constants';
import {NotificationMixin, OpenPageMixin, TimerMixin} from '../mixins/';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import VerifyCode from '../components/VerifyCode';
import store from '../storage';
import {mapMutations} from 'vuex';

new Vue({
    el: '#phone-verification',
    i18n,
    mixins: [
        NotificationMixin,
        OpenPageMixin,
        TimerMixin,
    ],
    components: {
        FontAwesomeIcon,
        VerifyCode,
    },
    data() {
        return {
            isRequestingMailCode: true,
            isRequestingSmsCode: true,
            sendCode: false,
            smsCode: false,
            mailCode: false,
            isFormSending: false,
            [TIMERS.SEND_PHONE_CODE]: false,
            [TIMERS.SEND_EMAIL_CODE]: false,
        };
    },
    mounted() {
        this.sendCode = !!this.$refs.sendCode.value;

        this.setIsPhoneVerificationPending(true);

        const limit = parseInt(this.$refs.resendMailCode.getAttribute('data-failed-attempts'));

        if (this.isCodetryLimitReach('resendMailCode')) {
            this.notifyError(this.$t('phone_confirmation.mail_limit_reached', {limit}));
        }

        if (this.isCodetryLimitReach('resendSmsCode')) {
            this.notifyError(this.$t('phone_confirmation.sms_limit_reached', {limit}));
        }

        if (!this.sendCode) {
            this.handleResendCodesDisabled();

            return;
        }

        this.requestSmsCode();
        this.requestMailCode();
    },
    computed: {
        resendSmsCodeText() {
            return this.isTimerActive(TIMERS.SEND_PHONE_CODE)
                ? this.$t('phone_confirmation.resend_code_in_secs', {
                    sec: this.getTimerSeconds(TIMERS.SEND_PHONE_CODE),
                })
                : this.$t('phone_confirmation.send_code_again');
        },
        resendSmsCodeSpanClass() {
            return this.isTimerActive(TIMERS.SEND_PHONE_CODE) || this.isRequestingSmsCode
                ? 'btn-disabled'
                : '';
        },
        resendMailCodeText() {
            return this.isTimerActive(TIMERS.SEND_EMAIL_CODE)
                ? this.$t('phone_confirmation.resend_code_in_secs', {
                    sec: this.getTimerSeconds(TIMERS.SEND_EMAIL_CODE),
                })
                : this.$t('phone_confirmation.send_code_again');
        },
        resendMailCodeSpanClass() {
            return this.isTimerActive(TIMERS.SEND_EMAIL_CODE) || this.isRequestingMailCode
                ? 'btn-disabled'
                : '';
        },
        isSubmitEnabled: function() {
            return this.smsCode && this.mailCode;
        },
    },
    methods: {
        ...mapMutations('user', [
            'setIsPhoneVerificationPending',
        ]),
        isCodetryLimitReach: function(code) {
            if (!Object.hasOwnProperty.call(this.$refs, code)) {
                return;
            }

            const errorsCount = parseInt(
                this.$refs[code].getAttribute('data-errors-count')
            );

            const limitReached = parseInt(
                this.$refs[code].getAttribute('data-limit-reached')
            );

            return limitReached && errorsCount;
        },
        requestSmsCode: async function() {
            if (this.isTimerActive(TIMERS.SEND_PHONE_CODE)) {
                return;
            }

            this.isRequestingSmsCode = true;

            try {
                this.handleSendCodeResponse(
                    await this.$axios.single.post(this.$routing.generate('send_phone_verification_code'))
                );
            } catch (error) {
                this.handleSendCodeError(error);
            } finally {
                this.startTimer(TIMERS.SEND_PHONE_CODE, PHONE_VERIF_REQUEST_CODE_INTERVAL);
                this.isRequestingSmsCode = false;
            }
        },
        requestMailCode: async function() {
            if (this.isTimerActive(TIMERS.SEND_EMAIL_CODE)) {
                return;
            }

            this.isRequestingMailCode = true;

            try {
                this.handleSendCodeResponse(
                    await this.$axios.single.post(this.$routing.generate('send_mail_phone_verification_code'))
                );
            } catch (error) {
                this.handleSendCodeError(error);
            } finally {
                this.startTimer(TIMERS.SEND_EMAIL_CODE, EMAIL_VERIF_REQUEST_CODE_INTERVAL);
                this.isRequestingMailCode = false;
            }
        },
        handleSendCodeResponse: function(response) {
            if (HTTP_OK !== response.status || response.data.hasOwnProperty('error')) {
                throw response;
            }

            this.notifySuccess(response.data.code ?? this.$t('phone_confirmation.sent'));
        },
        handleSendCodeError: function(error) {
            if (HTTP_UNAUTHORIZED === error.response?.status) {
                this.notifyError(this.$t('phone_confirmation.send_code_time_limit'));
            } else if (HTTP_LOCKED === error.response?.status) {
                this.notifyError(error.response.data.message);
                setTimeout(() => this.goToPage(this.$routing.generate('profile')), 5000);
            } else {
                this.notifyError(error.data?.error ?? this.$t('toasted.error.try_later'));
            }
        },
        handleResendCodesDisabled: function() {
            this.$axios.single.get(this.$routing.generate('can_send_phone_code'))
                .then((response) => {
                    const sendCodeDiffModel = response.data;
                    const smsSeconds = sendCodeDiffModel.sms.sendCodeDiff;
                    const mailSeconds = sendCodeDiffModel.mail.sendCodeDiff;

                    if (0 <= smsSeconds) {
                        this.startTimer(TIMERS.SEND_PHONE_CODE, smsSeconds);
                    }

                    if (0 <= mailSeconds) {
                        this.startTimer(TIMERS.SEND_EMAIL_CODE, mailSeconds);
                    }
                })
                .catch(() => {
                    this.startTimer(TIMERS.SEND_PHONE_CODE, PHONE_VERIF_REQUEST_CODE_INTERVAL);
                    this.startTimer(TIMERS.SEND_EMAIL_CODE, EMAIL_VERIF_REQUEST_CODE_INTERVAL);
                })
                .finally(() => {
                    this.isRequestingMailCode = false;
                    this.isRequestingSmsCode = false;
                });
        },
        submit: function() {
            if (!this.isSubmitEnabled) {
                return;
            }

            this.setIsPhoneVerificationPending(false);
            const submit = document.getElementById('phone_verification_submit');
            document.getElementById('loading').classList.remove('d-none');
            this.isFormSending = true;
            submit.disabled = false;
            submit.hidden = true;
            submit.click();
        },
        onSmsCodeEntered: function(code) {
            this.$refs['sms_code'].value = code;
            this.smsCode = !!code;
            this.submit();
        },
        onMailCodeEntered: function(code) {
            this.$refs['mail_code'].value = code;
            this.mailCode = !!code;
            this.submit();
        },
    },
    store,
});
