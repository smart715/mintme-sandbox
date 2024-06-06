<template>
    <div>
        <modal
            :visible="visible"
            @close="closeModal"
        >
            <template v-slot:header>
                {{ $t('modal.change_email.title') }}
            </template>
            <template v-if="!showEnterCode" slot="body">
                <div class="mb-5">
                    {{ $t('change_email.mail_confirmation.msg') }}
                </div>
                <div>
                    <m-input
                        :label="$t('change_email.new_email')"
                        v-model="newEmail"
                        :invalid="$v.newEmail.$anyError"
                    >
                        <template v-slot:errors>
                            <div v-if="$v.newEmail.$dirty && !$v.newEmail.required">
                                {{ $t('form.validation.required') }}
                            </div>
                            <div v-if="!$v.newEmail.helpers || !$v.newEmail.length">
                                {{ $t('change_email.error.email.type') }}
                            </div>
                        </template>
                    </m-input>
                </div>
                <div class="pt-2 d-flex align-items-center">
                    <m-button
                        type="primary"
                        :loading="isRequesting"
                        :disabled="changeEmailBtnDisabled"
                        @click="changeEmail"
                    >
                        {{ $t('modal.change_email.verify') }}
                    </m-button>
                    <m-button
                        type="cancel"
                        class="btn btn-cancel ml-2"
                        @click="closeModal"
                    >
                        {{ $t('cancel') }}
                    </m-button>
                </div>
            </template>
            <template v-else slot="body">
                <div>
                    <div class="mb-2">
                        {{ changeEmailConfirmationMsg }}
                    </div>
                    <div
                        class="d-flex flex-column mb-1"
                        v-if="!receiveCurrentEmailCodeMethod"
                    >
                        <a
                            class="c-pointer h5"
                            @click="receiveCurrentEmailCodeByEmail"
                        >
                            {{ $t('change_email.current_email_code.method_email') }}
                        </a>
                        <a
                            class="c-pointer h5"
                            @click="receiveCurrentEmailCodeBySms"
                        >
                            {{ $t('change_email.current_email_code.method_sms') }}
                        </a>
                    </div>
                    <template v-else>
                        <div class="mb-1">{{ changeEmailVerificationCodeMsg }}</div>
                        <div v-if="currentEmailCodeError" class="py-2 mb-2 bg-danger text-white text-center">
                            <ul class="px-3 m-0 list-unstyled">
                                <li>{{ currentEmailCodeError }}</li>
                            </ul>
                        </div>
                        <verify-code
                            ref="verifyCurrentEmailCode"
                            :disabled="isRequesting"
                            @code-entered="onCurrentEmailCodeEntered"
                        />
                        <m-button
                            type="link"
                            class="font-weight-normal px-0 my-1 c-pointer"
                            :loading="isRequestingCurrentEmailCode"
                            :class="resendCurrentEmailCodeSpanClass"
                            @click="requestCurrentEmailCode"
                        >
                            {{ resendCurrentEmailCodeText }}
                        </m-button>
                    </template>
                    <div class="mb-1">{{ $t('change_email.form.verification_code.new_email') }}</div>
                    <div v-if="newEmailCodeError" class="py-2 mb-2 bg-danger text-white text-center">
                        <ul class="px-3 m-0 list-unstyled">
                            <li>{{ newEmailCodeError }}</li>
                        </ul>
                    </div>
                    <verify-code
                        ref="verifyNewEmailCode"
                        :disabled="isRequesting"
                        @code-entered="onNewEmailCodeEntered"
                    />
                    <m-button
                        type="link"
                        class="font-weight-normal px-0 my-1 c-pointer"
                        :loading="isRequestingNewEmailCode"
                        :class="resendNewEmailCodeSpanClass"
                        @click="requestNewEmailCode"
                    >
                        {{ resendNewEmailCodeText }}
                    </m-button>
                    <template v-if="isTwoFactor">
                        <div class="mb-1">{{ $t('2fa_modal.label.2fa') }}</div>
                        <div v-if="tfaCodeError" class="py-2 mb-2 bg-danger text-white text-center">
                            <ul class="px-3 m-0 list-unstyled">
                                <li>{{ tfaCodeError }}</li>
                            </ul>
                        </div>
                        <verify-code
                            :disabled="isRequesting"
                            @code-entered="onTfaCodeEntered"
                        />
                    </template>
                </div>
                <div class="pt-2">
                    <m-button
                        type="primary"
                        :loading="isRequesting"
                        :disabled="btnDisabled"
                        @click="confirmCode"
                    >
                        {{ $t('confirm_modal.confirm') }}
                    </m-button>
                    <m-button
                        type="cancel"
                        @click="closeModal"
                    >
                        {{ $t('cancel') }}
                    </m-button>
                </div>
            </template>
        </modal>
    </div>
</template>

<script>
import {mapGetters} from 'vuex';
import {required, minLength} from 'vuelidate/lib/validators';
import Modal from './Modal';
import VerifyCode from '../VerifyCode';
import {
    NotificationMixin,
    TimerMixin,
    NoBadWordsMixin,
} from '../../mixins';
import {
    email,
    emailLength,
    HTTP_OK,
    HTTP_UNAUTHORIZED,
    EMAIL_VERIF_REQUEST_CODE_INTERVAL,
    TIMERS,
} from '../../utils/constants';
import {MInput, MButton} from '../UI';

const receiveCurrentEmailCodeMethodSms = 'Sms';
const receiveCurrentEmailCodeMethodEmail = 'Email';

export default {
    name: 'ChangeEmailModal',
    components: {
        Modal,
        MButton,
        MInput,
        VerifyCode,
    },
    mixins: [
        NoBadWordsMixin,
        TimerMixin,
        NotificationMixin,
    ],
    props: {
        visible: Boolean,
    },
    data() {
        return {
            newEmail: '',
            tfaCode: '',
            isTwoFactor: false,
            isRequesting: false,
            currentEmailCode: null,
            newEmailCode: null,
            isRequestingCurrentEmailCode: false,
            isRequestingNewEmailCode: false,
            showEnterCode: false,
            currentEmailCodeError: null,
            newEmailCodeError: null,
            tfaCodeError: null,
            receiveCurrentEmailCodeMethod: null,
            receiveCurrentEmailCodeEndpoints: {
                [receiveCurrentEmailCodeMethodSms]: 'send_current_email_sms_verification_code',
                [receiveCurrentEmailCodeMethodEmail]: 'send_current_email_verification_code',
            },
            [TIMERS.SEND_NEW_EMAIL_CODE]: false,
            [TIMERS.SEND_CURRENT_EMAIL_CODE]: false,
        };
    },
    computed: {
        ...mapGetters('user', {
            hasPhoneVerified: 'getHasPhoneVerified',
        }),
        changeEmailConfirmationMsg() {
            return this.isRequestingSmsCode
                ? this.$t('change_email.sms_confirmation.msg')
                : this.$t('change_email.mail_confirmation.msg');
        },
        changeEmailVerificationCodeMsg() {
            return this.isRequestingSmsCode
                ? this.$t('change_email.form.verification_code.sms')
                : this.$t('change_email.form.verification_code.current_email');
        },
        isRequestingSmsCode() {
            return this.receiveCurrentEmailCodeMethod === receiveCurrentEmailCodeMethodSms;
        },
        btnDisabled() {
            return (this.showEnterCode && !this.isConfirmCodeEnabled)
                || this.isRequesting
                || this.isRequestingCurrentEmailCode
                || this.isRequestingNewEmailCode;
        },
        resendCurrentEmailCodeText() {
            return this.isTimerActive(TIMERS.SEND_CURRENT_EMAIL_CODE)
                ? this.$t('change_email_confirmation.resend_code_in_secs', {
                    sec: this.getTimerSeconds(TIMERS.SEND_CURRENT_EMAIL_CODE),
                })
                : this.$t('change_email_confirmation.send_code_again');
        },
        resendCurrentEmailCodeSpanClass() {
            return this.isTimerActive(TIMERS.SEND_CURRENT_EMAIL_CODE) || this.isRequestingCurrentEmailCode
                ? 'btn-disabled'
                : '';
        },
        resendNewEmailCodeText() {
            return this.isTimerActive(TIMERS.SEND_NEW_EMAIL_CODE)
                ? this.$t('change_email_confirmation.resend_code_in_secs', {
                    sec: this.getTimerSeconds(TIMERS.SEND_NEW_EMAIL_CODE),
                })
                : this.$t('change_email_confirmation.send_code_again');
        },
        resendNewEmailCodeSpanClass() {
            return this.isTimerActive(TIMERS.SEND_NEW_EMAIL_CODE) || this.isRequestingNewEmailCode
                ? 'btn-disabled'
                : '';
        },
        isTfaCodeValid() {
            return !this.isTwoFactor || !!this.tfaCode;
        },
        isConfirmCodeEnabled() {
            return !!this.currentEmailCode && !!this.newEmailCode && this.isTfaCodeValid;
        },
        changeEmailBtnDisabled() {
            return this.$v.$invalid || this.isRequesting;
        },
    },
    methods: {
        closeModal() {
            this.$emit('close');
        },
        async changeEmail() {
            if (this.changeEmailBtnDisabled) {
                return;
            }

            this.isRequesting = true;

            try {
                const result = await this.$axios.single.post(
                    this.$routing.generate('change_email'),
                    {newEmail: this.newEmail},
                );
                this.isTwoFactor = !!result.data?.isTwoFactor;

                this.showVerifyCodeView();
                await this.requestNewEmailCode();
            } catch (error) {
                this.notifyError(error.response?.data?.message || this.$t('toasted.error.try_later'));
                this.$logger.error('error while adding change email', error);
            } finally {
                this.isRequesting = false;
            }
        },
        requestCurrentEmailCode: async function() {
            if (this.isRequestingCurrentEmailCode || this.isTimerActive(TIMERS.SEND_CURRENT_EMAIL_CODE)) {
                return;
            }

            if (!this.receiveCurrentEmailCodeMethod) {
                this.notifyInfo(this.$t('change_email.current_email.method_empty.message'));

                return;
            }

            this.isRequestingCurrentEmailCode = true;

            try {
                this.handleSendCodeResponse(await this.$axios.single.post(this.$routing.generate(
                    this.getSendCurrentEmailEndpoint()
                )));

                if (this.$refs['verifyCurrentEmailCode']) {
                    this.$refs['verifyCurrentEmailCode'].clearInput();
                    this.$refs['verifyCurrentEmailCode'].focus();
                }
            } catch (error) {
                this.handleSendCodeError(error);
            } finally {
                this.startTimer(TIMERS.SEND_CURRENT_EMAIL_CODE, EMAIL_VERIF_REQUEST_CODE_INTERVAL);
                this.isRequestingCurrentEmailCode = false;
            }
        },
        requestNewEmailCode: async function() {
            if (this.isRequestingNewEmailCode || this.isTimerActive(TIMERS.SEND_NEW_EMAIL_CODE)) {
                return;
            }

            this.isRequestingNewEmailCode = true;

            try {
                this.handleSendCodeResponse(await this.$axios.single.post(
                    this.$routing.generate('send_new_email_verification_code')
                ));

                if (this.$refs['verifyNewEmailCode']) {
                    this.$refs['verifyNewEmailCode'].clearInput();
                }
            } catch (error) {
                this.handleSendCodeError(error);
            } finally {
                this.startTimer(TIMERS.SEND_NEW_EMAIL_CODE, EMAIL_VERIF_REQUEST_CODE_INTERVAL);
                this.isRequestingNewEmailCode = false;
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
        onNewEmailCodeEntered(code) {
            this.newEmailCode = code;
            this.confirmCode();
        },
        onCurrentEmailCodeEntered(code) {
            this.currentEmailCode = code;
            this.confirmCode();
        },
        onTfaCodeEntered(code) {
            this.tfaCode = code;
            this.confirmCode();
        },
        async confirmCode() {
            if (this.isRequesting || !this.isConfirmCodeEnabled) {
                return;
            }

            this.isRequesting = true;

            try {
                await this.$axios.single.put(
                    this.$routing.generate('new_email_verification'),
                    {
                        newEmailCode: this.newEmailCode,
                        currentEmailCode: this.currentEmailCode,
                        tfaCode: this.tfaCode,
                    },
                );

                this.notifySuccess(this.$t('change_email.verify_success'));
                this.$emit('email-changed');
            } catch (error) {
                const errorData = error.response?.data;

                if (errorData?.message) {
                    this.notifyError(errorData.message);
                }

                this.currentEmailCodeError = errorData?.currentEmailCode ?? null;
                this.newEmailCodeError = errorData?.newEmailCode ?? null;
                this.tfaCodeError = errorData?.tfaCode ?? null;
                this.$logger.error('error while verifying new email', error);
            } finally {
                this.isRequesting = false;
            }
        },
        getSendCurrentEmailEndpoint() {
            return this.receiveCurrentEmailCodeEndpoints[this.receiveCurrentEmailCodeMethod];
        },
        chooseReceiveCurrentEmailCodeMethod(method) {
            this.receiveCurrentEmailCodeMethod = method;

            this.requestCurrentEmailCode();
        },
        receiveCurrentEmailCodeBySms() {
            if (!this.hasPhoneVerified) {
                this.notifyInfo(this.$t('change_email.phone_number.required'));

                return;
            }

            this.chooseReceiveCurrentEmailCodeMethod(receiveCurrentEmailCodeMethodSms);
        },
        receiveCurrentEmailCodeByEmail() {
            this.chooseReceiveCurrentEmailCodeMethod(receiveCurrentEmailCodeMethodEmail);
        },
        showVerifyCodeView() {
            this.showEnterCode = true;
        },
    },
    validations() {
        return {
            newEmail: {
                required,
                helpers: email,
                length: emailLength,
                minLength: minLength(2),
            },
        };
    },
};
</script>
