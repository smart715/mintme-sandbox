<template>
    <div v-if="visible">
        <add-phone-alert-modal
            v-if="!havePhoneNumber"
            :visible="!havePhoneNumber"
            :message="addPhoneModalMessage"
            :no-close="true"
            @close="closeModal"
            @phone-verified="onPhoneVerification"
        />
        <modal
            v-else
            ref="modal"
            :visible="havePhoneNumber"
            :no-close="noClose"
            @close="closeModal"
        >
            <template v-slot:header>
                {{ $t('2fa.backup_codes.download.title') }}
            </template>
            <template slot="body">
                <div class="pb-3">
                    {{ $t('2fa.backup_codes.download.phone_confirmation.msg') }}
                </div>
            </template>
            <template v-if="!showEnterCode" slot="body">
                <div>
                    <div class="col-12 pb-2" v-html-sanitize="body"></div>
                </div>
                <div class="pt-2 d-flex justify-content-center">
                    <button
                        class="btn btn-primary d-flex align-items-center justify-content-center"
                        :disabled="isRequestingPhoneCode"
                        @click="requestPhoneCode"
                    >
                        {{ confirmText }}
                        <span
                            v-if="isRequestingPhoneCode"
                            class="spinner-border spinner-border-sm mx-2 my-1"
                            role="status"
                        ></span>
                    </button>
                    <button
                        class="btn pl-3 btn-cancel ml-2"
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
                    <div class="mb-1">
                        {{ $t('2fa.verification_code') }}
                    </div>
                    <div v-if="phoneCodeError" class="py-2 mb-2 bg-danger text-white text-center">
                        <ul class="px-3 m-0 list-unstyled">
                            <li>
                                {{ phoneCodeError }}
                            </li>
                        </ul>
                    </div>
                    <verify-code
                        ref="verifyPhoneCode"
                        :disabled="isRequesting"
                        @code-entered="onPhoneCodeEntered"
                    />
                    <span
                        class="btn-cancel text-left px-0 my-1 c-pointer"
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
                </div>
                <div class="col-12 pt-2 text-center">
                    <button
                        v-if="!isRequesting"
                        class="btn btn-primary"
                        :disabled="!isCodeValid"
                        @click="onVerify"
                    >
                        {{ confirmCodeSubmitText }}
                    </button>
                    <button
                        v-if="!isRequesting"
                        class="btn-cancel pl-3 bg-transparent"
                        @click="closeModal"
                    >
                        <slot name="cancel">
                            {{ $t('confirm_modal.cancel') }}
                        </slot>
                    </button>
                    <font-awesome-icon
                        v-if="isRequesting"
                        icon="circle-notch"
                        class="loading-spinner"
                        fixed-width
                        spin
                    />
                </div>
            </template>
    </modal>
    </div>
</template>

<script>
import Modal from './Modal.vue';
import {required} from 'vuelidate/lib/validators';
import {AddPhoneAlertMixin, NotificationMixin, TimerMixin} from '../../mixins';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import VerifyCode from '../VerifyCode';
import {
    HTTP_OK,
    HTTP_UNAUTHORIZED,
    PHONE_VERIF_REQUEST_CODE_INTERVAL,
    TIMERS,
    CODE_LENGTH,
} from '../../utils/constants';
import AddPhoneAlertModal from './AddPhoneAlertModal.vue';

export default {
    name: 'BackupCodesModal',
    mixins: [
        NotificationMixin,
        TimerMixin,
        AddPhoneAlertMixin,
    ],
    components: {
        AddPhoneAlertModal,
        Modal,
        FontAwesomeIcon,
        VerifyCode,
    },
    props: {
        noClose: {
            type: Boolean,
            default: true,
        },
        regenerate: {
            type: Boolean,
            default: true,
        },
        visible: Boolean,
        havePhoneNumberProp: Boolean,
    },
    data() {
        return {
            smsCodeLength: CODE_LENGTH.sms,
            isRequesting: false,
            phoneCode: null,
            isRequestingPhoneCode: false,
            showEnterCode: false,
            phoneCodeError: null,
            addPhoneModalMessageType: 'download_backup',
            havePhoneNumber: this.havePhoneNumberProp,
        };
    },
    methods: {
        closeModal: function() {
            this.phoneCode = '';
            this.$emit('close');
        },
        onPhoneVerification: function() {
            this.havePhoneNumber = true;
        },
        onVerify: function() {
            this.$v.$touch();
            if (this.$v.$error) {
                this.notifyError(this.$t('2fa_modal.require'));
                return;
            }
            this.confirmCode();
        },
        checkCode: function(code) {
            return code.length === this.smsCodeLength;
        },
        requestPhoneCode: async function() {
            if (this.isRequestingPhoneCode || this.isTimerActive(TIMERS.SEND_PHONE_CODE)) {
                return;
            }

            this.isRequestingPhoneCode = true;

            try {
                this.handleSendCodeResponse(
                    await this.$axios.single.post(this.$routing.generate('send_2fa_sms_verification_code'))
                );

                if (this.$refs['verifyPhoneCode']) {
                    this.$refs['verifyPhoneCode'].clearInput();
                    this.$refs['verifyPhoneCode'].focus();
                }
                this.showVerifyCodeView();
                this.startTimer(TIMERS.SEND_PHONE_CODE, PHONE_VERIF_REQUEST_CODE_INTERVAL);
            } catch (error) {
                this.handleSendCodeError(error);
            } finally {
                this.isRequestingPhoneCode = false;
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
        onPhoneCodeEntered: function(code) {
            this.phoneCode = code;
            this.confirmCode();
        },
        downloadFile: function(data) {
            if (!data.name || !data.file) {
                this.notifyError(this.$t('toasted.error.try_later'));
                return;
            }
            this.notifySuccess(this.$t('2fa.notification.download_backup_code'));
            const blob = new Blob([data.file], {type: 'text/plain'});
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = data.name;
            link.click();
            URL.revokeObjectURL(link.href);
        },
        handleConfirmCodeError: function(error) {
            const errorData = error.response?.data;

            if (errorData.message) {
                this.notifyError(errorData.message);
            }

            this.phoneCodeError = errorData?.smsCode ?? null;
            this.$logger.error('error while verifying phone number', error);
        },
        confirmCode: async function() {
            if (this.isRequesting || !this.isConfirmCodeEnabled) {
                return;
            }

            this.isRequesting = true;
            try {
                const response = await this.$axios.single.post(
                    this.$routing.generate('download_two_factor_backup_code'),
                    {smsCode: this.phoneCode, regenerate: this.regenerate},
                );
                this.downloadFile(response.data);
                this.closeModal();
            } catch (error) {
                this.handleConfirmCodeError(error);
            } finally {
                this.isRequesting = false;
            }
        },
        showVerifyCodeView() {
            this.showEnterCode = true;
        },
    },
    validations() {
        return {
            phoneCode: {
                required,
            },
        };
    },
    computed: {
        isCodeValid() {
            return this.phoneCode
                ? this.checkCode(this.phoneCode)
                : false;
        },
        confirmText() {
            return this.isRequesting
                ? ''
                : this.$t('modal.set_two_factor_alert.send_code');
        },
        body() {
            return this.message;
        },
        enterCodeText() {
            return this.$t('2fa.backup_codes.download.phone_confirmation.enter_received_early');
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
        confirmCodeSubmitText() {
            return this.isRequesting
                ? ''
                : this.$t('confirm_modal.confirm');
        },
        isConfirmCodeEnabled() {
            return !!this.phoneCode;
        },
    },
    watch: {
        phoneCode: function(val) {
            if (val && this.checkCode(val)) {
                this.onVerify();
            }
        },
    },
};
</script>

