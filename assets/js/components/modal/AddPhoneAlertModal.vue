<template>
    <div>
        <modal
            :visible="visible"
            :no-close="noClose"
            :embeded="embeded"
            ref="modal"
            @close="closeModal"
        >
            <template v-slot:header>
                {{ $t('modal.add_phone_alert.title') }}
            </template>
            <template v-if="!showEnterCode" slot="body">
                <div>
                    <div class="pb-2" v-html-sanitize="body"></div>
                    <div class="d-flex align-items-center">
                        <span class="mr-1">{{ $t('page.profile.form.phone_number') }}</span>
                        <guide class="mb-2">
                            <template slot="header">
                                {{ $t('phone.guide.header') }}
                            </template>
                            <template slot="body">
                                {{ $t('phone.guide.body') }}
                            </template>
                        </guide>
                    </div>
                    <phone-number
                        inline
                        @phone-change="phoneChange"
                        @is-valid-phone="validPhone"
                    />
                </div>
                <div class="pt-2 d-flex align-items-center">
                    <button
                        class="btn btn-primary mr-1 d-flex align-items-center justify-content-center"
                        :disabled="btnDisabled"
                        @click="verifyNumber"
                    >
                        {{ confirmText }}
                        <div
                            v-if="isRequesting"
                            class="spinner-border spinner-border-sm mx-2 my-1"
                            role="status"
                        ></div>
                    </button>
                    <button
                        class="btn btn-cancel ml-2"
                        @click="closeModal"
                    >
                        {{ $t('cancel') }}
                    </button>
                </div>
            </template>
            <template v-else slot="body">
                <div>
                    <div class="mb-2">
                        {{ enterCodeText }}
                    </div>
                    <div class="mb-1">{{ $t('2fa.verification_code') }}</div>
                    <div v-if="phoneCodeError" class="py-2 mb-2 bg-danger text-white text-center">
                        <ul class="px-3 m-0 list-unstyled">
                            <li>{{ phoneCodeError }}</li>
                        </ul>
                    </div>
                    <verify-code
                        ref="verifyPhoneCode"
                        :disabled="isRequesting"
                        @code-entered="onPhoneCodeEntered"
                    />
                    <span
                        class="btn-cancel text-left px-0 my-1 c-pointer d-flex align-items-center"
                        :class="resendPhoneCodeSpanClass"
                        @click="requestPhoneCode"
                    >
                        {{ resendPhoneCodeText }}
                        <div
                            v-if="isRequestingPhoneCode"
                            class="spinner-border spinner-border-sm ml-2"
                            role="status"
                        ></div>
                    </span>
                    <div class="mb-1">{{ $t('2fa.email_code') }}</div>
                    <div v-if="emailCodeError" class="py-2 mb-2 bg-danger text-white text-center">
                        <ul class="px-3 m-0 list-unstyled">
                            <li>{{ emailCodeError }}</li>
                        </ul>
                    </div>
                    <verify-code
                        ref="verifyEmailCode"
                        :disabled="isRequesting"
                        @code-entered="onEmailCodeEntered"
                    />
                    <span
                        class="btn-cancel text-left px-0 my-1 c-pointer d-flex align-items-center"
                        :class="resendEmailCodeSpanClass"
                        @click="requestEmailCode"
                    >
                        {{ resendEmailCodeText }}
                        <div
                            v-if="isRequestingEmailCode"
                            class="spinner-border spinner-border-sm ml-2"
                            role="status"
                        ></div>
                    </span>
                </div>
                <div class="pt-2">
                    <button
                        class="btn btn-primary"
                        :disabled="btnDisabled"
                        @click="confirmCode"
                    >
                        {{ confirmCodeSubmitText }}
                        <div
                            v-if="isRequesting"
                            class="spinner-border spinner-border-sm mx-2 my-1"
                            role="status"
                        ></div>
                    </button>
                    <button
                        class="btn btn-cancel"
                        @click="closeModal"
                    >
                        {{ $t('cancel') }}
                    </button>
                </div>
            </template>
        </modal>
    </div>
</template>

<script>
import Modal from './Modal';
import PhoneNumber from '../profile/PhoneNumber';
import Guide from '../Guide';
import VerifyCode from '../VerifyCode';
import {NotificationMixin, TimerMixin} from '../../mixins';
import {
    HTTP_OK,
    HTTP_UNAUTHORIZED,
    PHONE_VERIF_REQUEST_CODE_INTERVAL,
    TIMERS,
} from '../../utils/constants';
import {mapGetters, mapMutations} from 'vuex';

export default {
    name: 'AddPhoneAlertModal',
    mixins: [NotificationMixin, TimerMixin],
    components: {
        Modal,
        PhoneNumber,
        Guide,
        VerifyCode,
    },
    props: {
        visible: Boolean,
        message: String,
        embeded: {
            type: Boolean,
            default: false,
        },
        noClose: {
            type: Boolean,
            default: false,
        },
    },
    data: function() {
        return {
            phoneNumber: '',
            isPhoneValid: false,
            isRequesting: false,
            phoneCode: null,
            emailCode: null,
            isRequestingPhoneCode: false,
            isRequestingEmailCode: false,
            showEnterCode: false,
            justSentCode: false,
            phoneCodeError: null,
            emailCodeError: null,
            [TIMERS.SEND_PHONE_CODE]: false,
            [TIMERS.SEND_EMAIL_CODE]: false,
        };
    },
    mounted() {
        this.checkIsActiveSession();
    },
    computed: {
        ...mapGetters('user', {
            isPhoneVerificationPending: 'getIsPhoneVerificationPending',
        }),
        confirmText() {
            if (this.isRequesting) {
                return '';
            }

            return this.embeded ? this.$t('page.reload') : this.$t('modal.add_phone_alert.verify');
        },
        body() {
            return this.message;
        },
        btnDisabled() {
            return (!this.showEnterCode && !this.isPhoneValid)
                || (this.showEnterCode && !this.isConfirmCodeEnabled)
                || this.isRequesting
                || this.isRequestingPhoneCode
                || this.isRequestingEmailCode;
        },
        enterCodeText() {
            return this.justSentCode
                ? this.$t('phone_confirmation.msg')
                : this.$t('phone_confirmation.enter_received_early');
        },
        resendPhoneCodeText() {
            return this.isTimerActive(TIMERS.SEND_PHONE_CODE)
                ? this.$t('phone_confirmation.resend_code_in_secs', {sec: this.getTimerSeconds(TIMERS.SEND_PHONE_CODE)})
                : this.$t('phone_confirmation.send_code_again');
        },
        resendPhoneCodeSpanClass() {
            return this.isTimerActive(TIMERS.SEND_PHONE_CODE) || this.isRequestingPhoneCode
                ? 'btn-disabled'
                : '';
        },
        resendEmailCodeText() {
            return this.isTimerActive(TIMERS.SEND_EMAIL_CODE)
                ? this.$t('phone_confirmation.resend_code_in_secs', {sec: this.getTimerSeconds(TIMERS.SEND_EMAIL_CODE)})
                : this.$t('phone_confirmation.send_code_again');
        },
        resendEmailCodeSpanClass() {
            return this.isTimerActive(TIMERS.SEND_EMAIL_CODE) || this.isRequestingEmailCode
                ? 'btn-disabled'
                : '';
        },
        confirmCodeSubmitText() {
            return this.isRequesting ? '' : this.$t('confirm_modal.confirm');
        },
        isConfirmCodeEnabled() {
            return !!this.phoneCode && !!this.emailCode;
        },
    },
    watch: {
        visible() {
            this.checkIsActiveSession();
        },
    },
    methods: {
        ...mapMutations('user', [
            'setHasPhoneVerified',
            'setIsPhoneVerificationPending',
        ]),
        closeModal() {
            if (this.embeded) {
                return location.reload();
            }
            this.$emit('close');
        },
        phoneChange(event) {
            this.phoneNumber = event;
        },
        validPhone(event) {
            this.isPhoneValid = event;
        },
        verifyNumber() {
            if (this.isRequesting) {
                return;
            }

            this.isRequesting = true;

            this.$axios.single.post(this.$routing.generate('add_phone_number'), {phoneNumber: this.phoneNumber})
                .then(() => {
                    this.requestPhoneCode();
                    this.requestEmailCode();
                })
                .catch((error) => {
                    this.notifyError(error.response?.data?.message || this.$t('toasted.error.try_later'));
                    this.$logger.error('error while adding phone number', error);
                })
                .finally(() => this.isRequesting = false);
        },
        requestPhoneCode: async function() {
            if (this.isRequestingPhoneCode || this.isTimerActive(TIMERS.SEND_PHONE_CODE)) {
                return;
            }

            this.isRequestingPhoneCode = true;

            try {
                this.handleSendCodeResponse(
                    await this.$axios.single.post(this.$routing.generate('send_phone_verification_code'))
                );

                this.setIsPhoneVerificationPending(true);
                this.justSentCode = true;

                if (this.$refs['verifyPhoneCode']) {
                    this.$refs['verifyPhoneCode'].clearInput();
                    this.$refs['verifyPhoneCode'].focus();
                }

                this.showVerifyCodeView();
            } catch (error) {
                this.handleSendCodeError(error);
            } finally {
                this.startTimer(TIMERS.SEND_PHONE_CODE, PHONE_VERIF_REQUEST_CODE_INTERVAL);
                this.isRequestingPhoneCode = false;
            }
        },
        requestEmailCode: async function() {
            if (this.isRequestingEmailCode || this.isTimerActive(TIMERS.SEND_EMAIL_CODE)) {
                return;
            }

            this.isRequestingEmailCode = true;

            try {
                this.handleSendCodeResponse(
                    await this.$axios.single.post(this.$routing.generate('send_mail_phone_verification_code'))
                );
                this.justSentCode = true;

                if (this.$refs['verifyEmailCode']) {
                    this.$refs['verifyEmailCode'].clearInput();
                }

                this.showVerifyCodeView();
            } catch (error) {
                this.handleSendCodeError(error);
            } finally {
                this.startTimer(TIMERS.SEND_EMAIL_CODE, PHONE_VERIF_REQUEST_CODE_INTERVAL);
                this.isRequestingEmailCode = false;
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

                return;
            }

            const errorMsg = error.data?.error ?? error.response?.data.message;
            this.notifyError(errorMsg ?? this.$t('toasted.error.try_later'));
        },
        onPhoneCodeEntered(code) {
            this.phoneCode = code;
            this.confirmCode();
        },
        onEmailCodeEntered(code) {
            this.emailCode = code;
            this.confirmCode();
        },
        confirmCode() {
            if (this.isRequesting || !this.isConfirmCodeEnabled) {
                return;
            }

            this.isRequesting = true;
            this.$axios.single.post(
                this.$routing.generate('verify_phone_number'),
                {smsCode: this.phoneCode, mailCode: this.emailCode},
            )
                .then(() => {
                    this.notifySuccess(this.$t('phone_number.verify_success'));
                    this.setIsPhoneVerificationPending(false);
                    this.setHasPhoneVerified(true);
                    this.$emit('phone-verified');
                })
                .catch((error) => {
                    const errorData = error.response?.data;

                    if (errorData?.message) {
                        this.notifyError(errorData.message);
                    }

                    this.phoneCodeError = errorData?.smsCode ?? null;
                    this.emailCodeError = errorData?.emailCode ?? null;
                    this.$logger.error('error while verifying phone number', error);
                })
                .finally(() => {
                    this.isRequesting = false;
                });
        },
        showVerifyCodeView() {
            this.showEnterCode = true;
        },
        checkIsActiveSession() {
            if (this.visible && this.isPhoneVerificationPending) {
                this.showVerifyCodeView();
            }
        },
    },
};
</script>
